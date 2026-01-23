<?php

namespace App\Services;

use Illuminate\Support\Str;

class PlanEventsBuilder
{
    private IcsParser $parser;

    private const DEFAULT_BLOCK_DURATION = 60; // minutes
    private const DEFAULT_START_TIME = '09:00';
    private const MIN_BLOCK_DURATION = 15;
    private const MAX_BLOCK_DURATION = 240;

    public function __construct(IcsParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Build preview state from Canvas ICS and engine output ICS.
     *
     * @param string $canvasIcs Canvas calendar ICS content
     * @param string $engineIcs Engine-generated work blocks ICS content
     * @param array $settings User settings from session
     * @return array Preview state structure
     */
    public function build(string $canvasIcs, string $engineIcs, array $settings = []): array
    {
        // Parse both ICS files
        $canvasAssignments = $this->parser->parseCanvasCalendar($canvasIcs);
        $engineBlocks = $this->parser->parseEngineOutput($engineIcs);

        // Build assignment lookup by title (normalized)
        $assignments = $this->buildAssignments($canvasAssignments, $engineBlocks);

        // Build work blocks with assignment references
        $workBlocks = $this->buildWorkBlocks($engineBlocks, $assignments);

        // Calculate total effort per assignment
        $assignments = $this->calculateTotalEffort($assignments, $workBlocks);

        return [
            'generated_at' => now()->toIso8601String(),
            'settings' => [
                'horizon' => $settings['horizon'] ?? 30,
                'soft_cap' => $settings['soft_cap'] ?? 4,
                'hard_cap' => $settings['hard_cap'] ?? 5,
                'skip_weekends' => $settings['skip_weekends'] ?? false,
                'min_block_minutes' => self::MIN_BLOCK_DURATION,
                'max_block_minutes' => self::MAX_BLOCK_DURATION,
            ],
            'assignments' => array_values($assignments),
            'work_blocks' => $workBlocks,
            'busy_times' => [], // TODO: Parse from busy ICS if provided
        ];
    }

    /**
     * Build assignments array from Canvas data, enriched with engine block info.
     */
    private function buildAssignments(array $canvasAssignments, array $engineBlocks): array
    {
        $assignments = [];

        // First, create assignments from Canvas data
        foreach ($canvasAssignments as $assignment) {
            $key = $this->normalizeTitle($assignment['title']);
            $id = 'assignment-' . Str::random(8);

            $assignments[$key] = [
                'id' => $id,
                'title' => $assignment['title'],
                'course' => $assignment['course'],
                'due_date' => $assignment['due_date'],
                'total_effort_minutes' => 0, // Calculated later
                'allow_work_on_due_date' => true,
                'canvas_url' => $assignment['url'] ?? null,
                'description' => $assignment['description'] ?? '',
            ];
        }

        // Add any assignments from engine blocks that weren't in Canvas
        foreach ($engineBlocks as $block) {
            $key = $this->normalizeTitle($block['assignment_title']);

            if (!isset($assignments[$key])) {
                $id = 'assignment-' . Str::random(8);
                $assignments[$key] = [
                    'id' => $id,
                    'title' => $block['assignment_title'],
                    'course' => $block['course'],
                    'due_date' => null, // Unknown from engine output alone
                    'total_effort_minutes' => 0,
                    'allow_work_on_due_date' => true,
                    'canvas_url' => null,
                    'description' => '',
                ];
            }
        }

        return $assignments;
    }

    /**
     * Build work blocks array with assignment references.
     */
    private function buildWorkBlocks(array $engineBlocks, array $assignments): array
    {
        $workBlocks = [];
        $blockIndex = 0;

        foreach ($engineBlocks as $block) {
            $titleKey = $this->normalizeTitle($block['assignment_title']);
            $assignmentId = $assignments[$titleKey]['id'] ?? null;

            $workBlocks[] = [
                'id' => 'block-' . str_pad(++$blockIndex, 3, '0', STR_PAD_LEFT),
                'assignment_id' => $assignmentId,
                'date' => $block['date'],
                'start_time' => self::DEFAULT_START_TIME,
                'duration_minutes' => self::DEFAULT_BLOCK_DURATION,
                'label' => $block['label'],
                'is_anchored' => false,
                'original_duration_minutes' => self::DEFAULT_BLOCK_DURATION,
            ];
        }

        return $workBlocks;
    }

    /**
     * Calculate total effort per assignment from work blocks.
     */
    private function calculateTotalEffort(array $assignments, array $workBlocks): array
    {
        // Sum duration_minutes per assignment
        $effort = [];
        foreach ($workBlocks as $block) {
            $assignmentId = $block['assignment_id'];
            if ($assignmentId) {
                $effort[$assignmentId] = ($effort[$assignmentId] ?? 0) + $block['duration_minutes'];
            }
        }

        // Update assignments with total effort
        foreach ($assignments as $key => $assignment) {
            $assignments[$key]['total_effort_minutes'] = $effort[$assignment['id']] ?? 0;
        }

        return $assignments;
    }

    /**
     * Normalize title for matching (lowercase, trimmed, collapsed whitespace).
     */
    private function normalizeTitle(string $title): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $title)));
    }
}
