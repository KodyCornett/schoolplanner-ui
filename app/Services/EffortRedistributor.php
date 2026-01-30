<?php

namespace App\Services;

class EffortRedistributor
{
    private const MIN_BLOCK_DURATION = 15;

    private const MAX_BLOCK_DURATION = 240;

    /**
     * Redistribute effort after a block is updated.
     *
     * When a block is edited, it becomes "anchored" and its duration is fixed.
     * The remaining effort for the assignment is redistributed across non-anchored blocks.
     *
     * @param  array  $previewState  Current preview state
     * @param  string  $updatedBlockId  The block that was updated
     * @param  array  $updates  The updates applied to the block (date, start_time, duration_minutes)
     * @return array Updated preview state
     */
    public function afterBlockUpdate(array $previewState, string $updatedBlockId, array $updates): array
    {
        $blocks = $previewState['work_blocks'] ?? [];

        // Find and update the block
        $updatedBlock = null;
        foreach ($blocks as $index => $block) {
            if ($block['id'] === $updatedBlockId) {
                // Apply updates
                if (isset($updates['date'])) {
                    $blocks[$index]['date'] = $updates['date'];
                }
                if (isset($updates['start_time'])) {
                    $blocks[$index]['start_time'] = $updates['start_time'];
                }
                if (isset($updates['duration_minutes'])) {
                    $blocks[$index]['duration_minutes'] = (int) $updates['duration_minutes'];
                }

                // Mark as anchored (user has edited it)
                $blocks[$index]['is_anchored'] = true;

                $updatedBlock = $blocks[$index];
                break;
            }
        }

        if (! $updatedBlock) {
            return $previewState;
        }

        // Redistribute effort for the assignment
        $blocks = $this->redistributeForAssignment($blocks, $updatedBlock['assignment_id']);

        $previewState['work_blocks'] = $blocks;

        // Recalculate total effort per assignment
        $previewState = $this->recalculateAssignmentEffort($previewState);

        return $previewState;
    }

    /**
     * Redistribute effort after a block is deleted.
     *
     * The deleted block's effort is distributed across remaining non-anchored blocks
     * for the same assignment.
     *
     * @param  array  $previewState  Current preview state
     * @param  string  $deletedBlockId  The block to delete
     * @return array Updated preview state
     */
    public function afterBlockDelete(array $previewState, string $deletedBlockId): array
    {
        $blocks = $previewState['work_blocks'] ?? [];

        // Find the block to delete
        $deletedBlock = null;
        $deletedIndex = null;
        foreach ($blocks as $index => $block) {
            if ($block['id'] === $deletedBlockId) {
                $deletedBlock = $block;
                $deletedIndex = $index;
                break;
            }
        }

        if ($deletedBlock === null) {
            return $previewState;
        }

        $assignmentId = $deletedBlock['assignment_id'];
        $effortToRedistribute = $deletedBlock['duration_minutes'];

        // Remove the block
        array_splice($blocks, $deletedIndex, 1);

        // Find non-anchored blocks for the same assignment
        $flexibleBlocks = [];
        foreach ($blocks as $index => $block) {
            if ($block['assignment_id'] === $assignmentId && ! $block['is_anchored']) {
                $flexibleBlocks[] = $index;
            }
        }

        // Distribute the effort
        if (count($flexibleBlocks) > 0) {
            $effortPerBlock = (int) floor($effortToRedistribute / count($flexibleBlocks));
            $remainder = $effortToRedistribute % count($flexibleBlocks);

            foreach ($flexibleBlocks as $i => $blockIndex) {
                $extraEffort = $effortPerBlock + ($i === 0 ? $remainder : 0);
                $newDuration = $blocks[$blockIndex]['duration_minutes'] + $extraEffort;

                // Clamp to min/max
                $blocks[$blockIndex]['duration_minutes'] = max(
                    self::MIN_BLOCK_DURATION,
                    min(self::MAX_BLOCK_DURATION, $newDuration)
                );
            }
        }

        $previewState['work_blocks'] = $blocks;

        // Recalculate total effort per assignment
        $previewState = $this->recalculateAssignmentEffort($previewState);

        return $previewState;
    }

    /**
     * Redistribute effort for an assignment after a block change.
     *
     * This maintains the total effort for the assignment by adjusting non-anchored blocks.
     */
    private function redistributeForAssignment(array $blocks, ?string $assignmentId): array
    {
        if (! $assignmentId) {
            return $blocks;
        }

        // Get all blocks for this assignment
        $assignmentBlocks = [];
        foreach ($blocks as $index => $block) {
            if ($block['assignment_id'] === $assignmentId) {
                $assignmentBlocks[$index] = $block;
            }
        }

        if (count($assignmentBlocks) === 0) {
            return $blocks;
        }

        // Calculate original total effort (from original_duration_minutes)
        $totalOriginalEffort = 0;
        foreach ($assignmentBlocks as $block) {
            $totalOriginalEffort += $block['original_duration_minutes'] ?? $block['duration_minutes'];
        }

        // Calculate anchored effort
        $anchoredEffort = 0;
        $flexibleIndices = [];
        foreach ($assignmentBlocks as $index => $block) {
            if ($block['is_anchored']) {
                $anchoredEffort += $block['duration_minutes'];
            } else {
                $flexibleIndices[] = $index;
            }
        }

        // Calculate remaining effort to distribute
        $remainingEffort = max(0, $totalOriginalEffort - $anchoredEffort);

        // Distribute remaining effort across flexible blocks
        if (count($flexibleIndices) > 0 && $remainingEffort > 0) {
            $effortPerBlock = (int) floor($remainingEffort / count($flexibleIndices));
            $remainder = $remainingEffort % count($flexibleIndices);

            foreach ($flexibleIndices as $i => $blockIndex) {
                $newDuration = $effortPerBlock + ($i === 0 ? $remainder : 0);

                // Clamp to min/max
                $blocks[$blockIndex]['duration_minutes'] = max(
                    self::MIN_BLOCK_DURATION,
                    min(self::MAX_BLOCK_DURATION, $newDuration)
                );
            }
        } elseif (count($flexibleIndices) > 0 && $remainingEffort === 0) {
            // All effort is anchored, set flexible blocks to minimum
            foreach ($flexibleIndices as $blockIndex) {
                $blocks[$blockIndex]['duration_minutes'] = self::MIN_BLOCK_DURATION;
            }
        }

        return $blocks;
    }

    /**
     * Recalculate total_effort_minutes for each assignment based on current blocks.
     */
    private function recalculateAssignmentEffort(array $previewState): array
    {
        $blocks = $previewState['work_blocks'] ?? [];
        $assignments = $previewState['assignments'] ?? [];

        // Sum effort per assignment
        $effortByAssignment = [];
        foreach ($blocks as $block) {
            $assignmentId = $block['assignment_id'];
            if ($assignmentId) {
                $effortByAssignment[$assignmentId] = ($effortByAssignment[$assignmentId] ?? 0) + $block['duration_minutes'];
            }
        }

        // Update assignments
        foreach ($assignments as $index => $assignment) {
            $assignments[$index]['total_effort_minutes'] = $effortByAssignment[$assignment['id']] ?? 0;
        }

        $previewState['assignments'] = $assignments;

        return $previewState;
    }
}
