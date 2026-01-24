<?php

namespace Tests\Unit;

use App\Services\EffortRedistributor;
use PHPUnit\Framework\TestCase;

class EffortRedistributorTest extends TestCase
{
    private EffortRedistributor $redistributor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redistributor = new EffortRedistributor();
    }

    private function createPreviewState(array $assignments, array $workBlocks): array
    {
        return [
            'assignments' => $assignments,
            'work_blocks' => $workBlocks,
            'settings' => [],
            'busy_times' => [],
        ];
    }

    private function createBlock(string $id, string $assignmentId, int $duration, bool $anchored = false): array
    {
        return [
            'id' => $id,
            'assignment_id' => $assignmentId,
            'date' => '2026-01-25',
            'start_time' => '09:00',
            'duration_minutes' => $duration,
            'label' => '[phase]',
            'is_anchored' => $anchored,
            'original_duration_minutes' => $duration,
        ];
    }

    public function test_update_block_marks_as_anchored(): void
    {
        $state = $this->createPreviewState(
            [['id' => 'assignment-1', 'total_effort_minutes' => 180]],
            [
                $this->createBlock('block-1', 'assignment-1', 60),
                $this->createBlock('block-2', 'assignment-1', 60),
                $this->createBlock('block-3', 'assignment-1', 60),
            ]
        );

        $newState = $this->redistributor->afterBlockUpdate($state, 'block-1', [
            'duration_minutes' => 90,
        ]);

        $blocks = $newState['work_blocks'];
        $block1 = collect($blocks)->firstWhere('id', 'block-1');

        $this->assertTrue($block1['is_anchored']);
        $this->assertEquals(90, $block1['duration_minutes']);
    }

    public function test_update_block_redistributes_to_flexible_blocks(): void
    {
        $state = $this->createPreviewState(
            [['id' => 'assignment-1', 'total_effort_minutes' => 180]],
            [
                $this->createBlock('block-1', 'assignment-1', 60),
                $this->createBlock('block-2', 'assignment-1', 60),
                $this->createBlock('block-3', 'assignment-1', 60),
            ]
        );

        // Increase block-1 from 60 to 120 minutes (anchored)
        // Remaining 60 minutes should be split between block-2 and block-3
        $newState = $this->redistributor->afterBlockUpdate($state, 'block-1', [
            'duration_minutes' => 120,
        ]);

        $blocks = $newState['work_blocks'];
        $block1 = collect($blocks)->firstWhere('id', 'block-1');
        $block2 = collect($blocks)->firstWhere('id', 'block-2');
        $block3 = collect($blocks)->firstWhere('id', 'block-3');

        $this->assertEquals(120, $block1['duration_minutes']);
        $this->assertTrue($block1['is_anchored']);

        // Remaining 60 minutes split between 2 blocks = 30 each
        $this->assertEquals(30, $block2['duration_minutes']);
        $this->assertEquals(30, $block3['duration_minutes']);
    }

    public function test_update_block_respects_minimum_duration(): void
    {
        $state = $this->createPreviewState(
            [['id' => 'assignment-1', 'total_effort_minutes' => 90]],
            [
                $this->createBlock('block-1', 'assignment-1', 60),
                $this->createBlock('block-2', 'assignment-1', 30),
            ]
        );

        // Anchor block-1 at 80 minutes, leaving only 10 for block-2
        // But min is 15, so block-2 should be clamped
        $newState = $this->redistributor->afterBlockUpdate($state, 'block-1', [
            'duration_minutes' => 80,
        ]);

        $blocks = $newState['work_blocks'];
        $block2 = collect($blocks)->firstWhere('id', 'block-2');

        // 90 - 80 = 10, but minimum is 15
        $this->assertGreaterThanOrEqual(15, $block2['duration_minutes']);
    }

    public function test_update_block_respects_maximum_duration(): void
    {
        $state = $this->createPreviewState(
            [['id' => 'assignment-1', 'total_effort_minutes' => 600]],
            [
                $this->createBlock('block-1', 'assignment-1', 60),
                $this->createBlock('block-2', 'assignment-1', 540),
            ]
        );

        // Delete a lot of effort from block-1 by anchoring at 15
        // This would try to push 585 to block-2, but max is 240
        $newState = $this->redistributor->afterBlockUpdate($state, 'block-1', [
            'duration_minutes' => 15,
        ]);

        $blocks = $newState['work_blocks'];
        $block2 = collect($blocks)->firstWhere('id', 'block-2');

        $this->assertLessThanOrEqual(240, $block2['duration_minutes']);
    }

    public function test_update_only_redistributes_within_same_assignment(): void
    {
        $state = $this->createPreviewState(
            [
                ['id' => 'assignment-1', 'total_effort_minutes' => 120],
                ['id' => 'assignment-2', 'total_effort_minutes' => 60],
            ],
            [
                $this->createBlock('block-1', 'assignment-1', 60),
                $this->createBlock('block-2', 'assignment-1', 60),
                $this->createBlock('block-3', 'assignment-2', 60),
            ]
        );

        $newState = $this->redistributor->afterBlockUpdate($state, 'block-1', [
            'duration_minutes' => 90,
        ]);

        $blocks = $newState['work_blocks'];
        $block3 = collect($blocks)->firstWhere('id', 'block-3');

        // block-3 belongs to assignment-2 and should not be affected
        $this->assertEquals(60, $block3['duration_minutes']);
        $this->assertFalse($block3['is_anchored']);
    }

    public function test_delete_block_removes_block(): void
    {
        $state = $this->createPreviewState(
            [['id' => 'assignment-1', 'total_effort_minutes' => 180]],
            [
                $this->createBlock('block-1', 'assignment-1', 60),
                $this->createBlock('block-2', 'assignment-1', 60),
                $this->createBlock('block-3', 'assignment-1', 60),
            ]
        );

        $newState = $this->redistributor->afterBlockDelete($state, 'block-2');

        $blocks = $newState['work_blocks'];
        $blockIds = collect($blocks)->pluck('id')->all();

        $this->assertCount(2, $blocks);
        $this->assertNotContains('block-2', $blockIds);
        $this->assertContains('block-1', $blockIds);
        $this->assertContains('block-3', $blockIds);
    }

    public function test_delete_block_redistributes_effort(): void
    {
        $state = $this->createPreviewState(
            [['id' => 'assignment-1', 'total_effort_minutes' => 180]],
            [
                $this->createBlock('block-1', 'assignment-1', 60),
                $this->createBlock('block-2', 'assignment-1', 60),
                $this->createBlock('block-3', 'assignment-1', 60),
            ]
        );

        $newState = $this->redistributor->afterBlockDelete($state, 'block-2');

        $blocks = $newState['work_blocks'];
        $block1 = collect($blocks)->firstWhere('id', 'block-1');
        $block3 = collect($blocks)->firstWhere('id', 'block-3');

        // 60 minutes from deleted block-2 redistributed to block-1 and block-3
        // 60 / 2 = 30 extra each
        $this->assertEquals(90, $block1['duration_minutes']);
        $this->assertEquals(90, $block3['duration_minutes']);
    }

    public function test_delete_block_skips_anchored_blocks(): void
    {
        $state = $this->createPreviewState(
            [['id' => 'assignment-1', 'total_effort_minutes' => 180]],
            [
                $this->createBlock('block-1', 'assignment-1', 60, true), // anchored
                $this->createBlock('block-2', 'assignment-1', 60),
                $this->createBlock('block-3', 'assignment-1', 60),
            ]
        );

        $newState = $this->redistributor->afterBlockDelete($state, 'block-2');

        $blocks = $newState['work_blocks'];
        $block1 = collect($blocks)->firstWhere('id', 'block-1');
        $block3 = collect($blocks)->firstWhere('id', 'block-3');

        // block-1 is anchored, so all 60 minutes go to block-3
        $this->assertEquals(60, $block1['duration_minutes']); // unchanged
        $this->assertEquals(120, $block3['duration_minutes']); // got all 60
    }

    public function test_delete_nonexistent_block_returns_unchanged_state(): void
    {
        $state = $this->createPreviewState(
            [['id' => 'assignment-1', 'total_effort_minutes' => 60]],
            [
                $this->createBlock('block-1', 'assignment-1', 60),
            ]
        );

        $newState = $this->redistributor->afterBlockDelete($state, 'nonexistent-block');

        $this->assertEquals($state, $newState);
    }

    public function test_update_nonexistent_block_returns_unchanged_state(): void
    {
        $state = $this->createPreviewState(
            [['id' => 'assignment-1', 'total_effort_minutes' => 60]],
            [
                $this->createBlock('block-1', 'assignment-1', 60),
            ]
        );

        $newState = $this->redistributor->afterBlockUpdate($state, 'nonexistent-block', [
            'duration_minutes' => 90,
        ]);

        $this->assertEquals($state, $newState);
    }

    public function test_recalculates_assignment_total_effort_after_update(): void
    {
        $state = $this->createPreviewState(
            [['id' => 'assignment-1', 'total_effort_minutes' => 120]],
            [
                $this->createBlock('block-1', 'assignment-1', 60),
                $this->createBlock('block-2', 'assignment-1', 60),
            ]
        );

        $newState = $this->redistributor->afterBlockUpdate($state, 'block-1', [
            'duration_minutes' => 90,
        ]);

        // Total should stay roughly the same (original_duration is preserved)
        // 90 (anchored) + some redistribution to block-2
        $totalEffort = 0;
        foreach ($newState['work_blocks'] as $block) {
            $totalEffort += $block['duration_minutes'];
        }

        // Should be close to original 120, allowing for min/max clamping
        $this->assertGreaterThanOrEqual(100, $totalEffort);
    }

    public function test_recalculates_assignment_total_effort_after_delete(): void
    {
        $state = $this->createPreviewState(
            [['id' => 'assignment-1', 'total_effort_minutes' => 180]],
            [
                $this->createBlock('block-1', 'assignment-1', 60),
                $this->createBlock('block-2', 'assignment-1', 60),
                $this->createBlock('block-3', 'assignment-1', 60),
            ]
        );

        $newState = $this->redistributor->afterBlockDelete($state, 'block-2');

        $assignment = $newState['assignments'][0];

        // After redistribution, total effort should be maintained
        $totalBlockEffort = 0;
        foreach ($newState['work_blocks'] as $block) {
            $totalBlockEffort += $block['duration_minutes'];
        }

        $this->assertEquals($totalBlockEffort, $assignment['total_effort_minutes']);
    }

    public function test_update_date_preserves_block_data(): void
    {
        $state = $this->createPreviewState(
            [['id' => 'assignment-1', 'total_effort_minutes' => 60]],
            [
                $this->createBlock('block-1', 'assignment-1', 60),
            ]
        );

        $newState = $this->redistributor->afterBlockUpdate($state, 'block-1', [
            'date' => '2026-02-01',
        ]);

        $block = $newState['work_blocks'][0];

        $this->assertEquals('2026-02-01', $block['date']);
        $this->assertEquals(60, $block['duration_minutes']);
        $this->assertEquals('[phase]', $block['label']);
        $this->assertTrue($block['is_anchored']);
    }

    public function test_update_start_time_preserves_block_data(): void
    {
        $state = $this->createPreviewState(
            [['id' => 'assignment-1', 'total_effort_minutes' => 60]],
            [
                $this->createBlock('block-1', 'assignment-1', 60),
            ]
        );

        $newState = $this->redistributor->afterBlockUpdate($state, 'block-1', [
            'start_time' => '14:30',
        ]);

        $block = $newState['work_blocks'][0];

        $this->assertEquals('14:30', $block['start_time']);
        $this->assertEquals(60, $block['duration_minutes']);
        $this->assertTrue($block['is_anchored']);
    }
}
