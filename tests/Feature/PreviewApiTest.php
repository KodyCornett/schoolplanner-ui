<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreviewApiTest extends TestCase
{
    private function setPreviewSession(): void
    {
        session([
            'plan.run' => [
                'id' => 'test-run-123',
                'token' => 'test-token',
                'paths' => [
                    'canvas' => 'plans/test-run-123/inputs/canvas.ics',
                    'studyplan_ics' => 'plans/test-run-123/out/StudyPlan.ics',
                ],
                'settings' => [
                    'horizon' => 30,
                    'soft_cap' => 4,
                    'hard_cap' => 5,
                    'skip_weekends' => false,
                ],
                'preview_state' => $this->getSamplePreviewState(),
            ],
        ]);
    }

    private function getSamplePreviewState(): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'settings' => [
                'horizon' => 30,
                'soft_cap' => 4,
                'hard_cap' => 5,
                'skip_weekends' => false,
                'min_block_minutes' => 15,
                'max_block_minutes' => 240,
            ],
            'assignments' => [
                [
                    'id' => 'assignment-abc',
                    'title' => 'Android CRUD Client',
                    'course' => 'CIS218',
                    'due_date' => '2026-01-26',
                    'total_effort_minutes' => 180,
                    'allow_work_on_due_date' => true,
                ],
                [
                    'id' => 'assignment-xyz',
                    'title' => 'Essay Assignment',
                    'course' => 'ENG101',
                    'due_date' => '2026-01-30',
                    'total_effort_minutes' => 120,
                    'allow_work_on_due_date' => true,
                ],
            ],
            'work_blocks' => [
                [
                    'id' => 'block-001',
                    'assignment_id' => 'assignment-abc',
                    'date' => '2026-01-22',
                    'start_time' => '09:00',
                    'duration_minutes' => 60,
                    'label' => '[requirements]',
                    'is_anchored' => false,
                    'original_duration_minutes' => 60,
                ],
                [
                    'id' => 'block-002',
                    'assignment_id' => 'assignment-abc',
                    'date' => '2026-01-23',
                    'start_time' => '09:00',
                    'duration_minutes' => 60,
                    'label' => '[implementation]',
                    'is_anchored' => false,
                    'original_duration_minutes' => 60,
                ],
                [
                    'id' => 'block-003',
                    'assignment_id' => 'assignment-abc',
                    'date' => '2026-01-24',
                    'start_time' => '09:00',
                    'duration_minutes' => 60,
                    'label' => '[testing]',
                    'is_anchored' => false,
                    'original_duration_minutes' => 60,
                ],
                [
                    'id' => 'block-004',
                    'assignment_id' => 'assignment-xyz',
                    'date' => '2026-01-25',
                    'start_time' => '10:00',
                    'duration_minutes' => 60,
                    'label' => '[research]',
                    'is_anchored' => false,
                    'original_duration_minutes' => 60,
                ],
                [
                    'id' => 'block-005',
                    'assignment_id' => 'assignment-xyz',
                    'date' => '2026-01-27',
                    'start_time' => '10:00',
                    'duration_minutes' => 60,
                    'label' => '[writing]',
                    'is_anchored' => false,
                    'original_duration_minutes' => 60,
                ],
            ],
            'busy_times' => [],
        ];
    }

    public function test_preview_data_returns_preview_state(): void
    {
        $this->setPreviewSession();

        $response = $this->getJson('/plan/preview/data');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'generated_at',
                'settings' => ['horizon', 'soft_cap', 'hard_cap', 'skip_weekends'],
                'assignments' => [
                    '*' => ['id', 'title', 'course', 'due_date', 'total_effort_minutes', 'allow_work_on_due_date'],
                ],
                'work_blocks' => [
                    '*' => ['id', 'assignment_id', 'date', 'start_time', 'duration_minutes', 'label', 'is_anchored'],
                ],
                'busy_times',
            ]);
    }

    public function test_preview_data_returns_404_without_session(): void
    {
        $response = $this->getJson('/plan/preview/data');

        $response->assertStatus(404)
            ->assertJson(['error' => 'No preview data available']);
    }

    public function test_update_block_changes_duration(): void
    {
        $this->setPreviewSession();

        $response = $this->postJson('/plan/preview/block/block-001', [
            'duration_minutes' => 90,
        ]);

        $response->assertStatus(200);

        $data = $response->json();
        $block = collect($data['work_blocks'])->firstWhere('id', 'block-001');

        $this->assertEquals(90, $block['duration_minutes']);
        $this->assertTrue($block['is_anchored']);
    }

    public function test_update_block_changes_date(): void
    {
        $this->setPreviewSession();

        $response = $this->postJson('/plan/preview/block/block-001', [
            'date' => '2026-01-25',
        ]);

        $response->assertStatus(200);

        $data = $response->json();
        $block = collect($data['work_blocks'])->firstWhere('id', 'block-001');

        $this->assertEquals('2026-01-25', $block['date']);
        $this->assertTrue($block['is_anchored']);
    }

    public function test_update_block_changes_start_time(): void
    {
        $this->setPreviewSession();

        $response = $this->postJson('/plan/preview/block/block-001', [
            'start_time' => '14:30',
        ]);

        $response->assertStatus(200);

        $data = $response->json();
        $block = collect($data['work_blocks'])->firstWhere('id', 'block-001');

        $this->assertEquals('14:30', $block['start_time']);
        $this->assertTrue($block['is_anchored']);
    }

    public function test_update_block_validates_duration_minimum(): void
    {
        $this->setPreviewSession();

        $response = $this->postJson('/plan/preview/block/block-001', [
            'duration_minutes' => 5, // below minimum of 15
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('duration_minutes');
    }

    public function test_update_block_validates_duration_maximum(): void
    {
        $this->setPreviewSession();

        $response = $this->postJson('/plan/preview/block/block-001', [
            'duration_minutes' => 300, // above maximum of 240
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('duration_minutes');
    }

    public function test_update_block_validates_date_format(): void
    {
        $this->setPreviewSession();

        $response = $this->postJson('/plan/preview/block/block-001', [
            'date' => 'invalid-date',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('date');
    }

    public function test_update_block_validates_time_format(): void
    {
        $this->setPreviewSession();

        $response = $this->postJson('/plan/preview/block/block-001', [
            'start_time' => '25:99', // invalid time
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('start_time');
    }

    public function test_update_block_returns_404_without_session(): void
    {
        $response = $this->postJson('/plan/preview/block/block-001', [
            'duration_minutes' => 90,
        ]);

        $response->assertStatus(404)
            ->assertJson(['error' => 'No preview data available']);
    }

    public function test_delete_block_removes_block(): void
    {
        $this->setPreviewSession();

        $response = $this->deleteJson('/plan/preview/block/block-002');

        $response->assertStatus(200);

        $data = $response->json();
        $blockIds = collect($data['work_blocks'])->pluck('id')->all();

        $this->assertNotContains('block-002', $blockIds);
        $this->assertContains('block-001', $blockIds);
        $this->assertContains('block-003', $blockIds);
    }

    public function test_delete_block_redistributes_effort(): void
    {
        $this->setPreviewSession();

        // Before delete: block-001, block-002, block-003 each have 60 minutes (assignment-abc)
        $response = $this->deleteJson('/plan/preview/block/block-002');

        $response->assertStatus(200);

        $data = $response->json();
        $block1 = collect($data['work_blocks'])->firstWhere('id', 'block-001');
        $block3 = collect($data['work_blocks'])->firstWhere('id', 'block-003');

        // 60 minutes from deleted block should be split between remaining blocks
        // 60 / 2 = 30 extra each
        $this->assertEquals(90, $block1['duration_minutes']);
        $this->assertEquals(90, $block3['duration_minutes']);
    }

    public function test_delete_block_returns_404_without_session(): void
    {
        $response = $this->deleteJson('/plan/preview/block/block-001');

        $response->assertStatus(404)
            ->assertJson(['error' => 'No preview data available']);
    }

    public function test_update_assignment_settings_changes_allow_work_on_due_date(): void
    {
        $this->setPreviewSession();

        $response = $this->postJson('/plan/preview/assignment/assignment-abc/settings', [
            'allow_work_on_due_date' => false,
        ]);

        $response->assertStatus(200);

        $data = $response->json();
        $assignment = collect($data['assignments'])->firstWhere('id', 'assignment-abc');

        $this->assertFalse($assignment['allow_work_on_due_date']);
    }

    public function test_update_assignment_settings_returns_404_without_session(): void
    {
        $response = $this->postJson('/plan/preview/assignment/assignment-abc/settings', [
            'allow_work_on_due_date' => false,
        ]);

        $response->assertStatus(404)
            ->assertJson(['error' => 'No preview data available']);
    }

    public function test_regenerate_resets_preview_state(): void
    {
        // This test requires a more complex setup with actual ICS files
        // For now, test that the endpoint exists and returns appropriate error without files
        session([
            'plan.run' => [
                'id' => 'test-run-123',
                'paths' => [
                    'canvas' => 'nonexistent/canvas.ics',
                    'studyplan_ics' => 'nonexistent/StudyPlan.ics',
                ],
                'settings' => [],
                'preview_state' => $this->getSamplePreviewState(),
            ],
        ]);

        $response = $this->postJson('/plan/preview/regenerate');

        // Without valid ICS files, should return error
        $response->assertStatus(500)
            ->assertJson(['error' => 'Could not regenerate preview']);
    }

    public function test_regenerate_returns_404_without_session(): void
    {
        $response = $this->postJson('/plan/preview/regenerate');

        $response->assertStatus(404)
            ->assertJson(['error' => 'No active plan run']);
    }

    public function test_finalize_returns_download_url(): void
    {
        $this->setPreviewSession();

        $response = $this->postJson('/plan/preview/finalize');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'download_url',
            ])
            ->assertJson(['success' => true]);
    }

    public function test_finalize_returns_404_without_session(): void
    {
        $response = $this->postJson('/plan/preview/finalize');

        $response->assertStatus(404)
            ->assertJson(['error' => 'No preview data available']);
    }

    public function test_preview_page_redirects_without_session(): void
    {
        $response = $this->get('/plan/preview');

        $response->assertRedirect('/plan/import')
            ->assertSessionHasErrors('preview');
    }

    public function test_preview_page_renders_with_session(): void
    {
        $this->setPreviewSession();

        $response = $this->get('/plan/preview');

        $response->assertStatus(200)
            ->assertSee('Preview Your Study Plan');
    }
}
