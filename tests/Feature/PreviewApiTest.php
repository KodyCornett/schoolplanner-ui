<?php

namespace Tests\Feature;

use App\Models\PlanRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreviewApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function createPlanRunWithPreviewState(): PlanRun
    {
        return PlanRun::create([
            'id' => 'test-run-123',
            'user_id' => $this->user->id,
            'token' => 'test-token',
            'paths' => [
                'canvas' => 'plans/1/test-run-123/inputs/canvas.ics',
                'studyplan_ics' => 'plans/1/test-run-123/out/StudyPlan.ics',
            ],
            'settings' => [
                'horizon' => 30,
                'soft_cap' => 4,
                'hard_cap' => 5,
                'skip_weekends' => false,
            ],
            'preview_state' => $this->getSamplePreviewState(),
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
        $run = $this->createPlanRunWithPreviewState();

        $response = $this->actingAs($this->user)
            ->withSession(['current_plan_run_id' => $run->id])
            ->getJson('/plan/preview/data');

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

    public function test_preview_data_returns_404_without_plan_run(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/plan/preview/data');

        $response->assertStatus(404)
            ->assertJson(['error' => 'No preview data available']);
    }

    public function test_update_block_changes_duration(): void
    {
        $run = $this->createPlanRunWithPreviewState();

        $response = $this->actingAs($this->user)
            ->withSession(['current_plan_run_id' => $run->id])
            ->putJson('/plan/preview/blocks/block-001', [
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
        $run = $this->createPlanRunWithPreviewState();

        $response = $this->actingAs($this->user)
            ->withSession(['current_plan_run_id' => $run->id])
            ->putJson('/plan/preview/blocks/block-001', [
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
        $run = $this->createPlanRunWithPreviewState();

        $response = $this->actingAs($this->user)
            ->withSession(['current_plan_run_id' => $run->id])
            ->putJson('/plan/preview/blocks/block-001', [
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
        $run = $this->createPlanRunWithPreviewState();

        $response = $this->actingAs($this->user)
            ->withSession(['current_plan_run_id' => $run->id])
            ->putJson('/plan/preview/blocks/block-001', [
                'duration_minutes' => 5, // below minimum of 15
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('duration_minutes');
    }

    public function test_update_block_validates_duration_maximum(): void
    {
        $run = $this->createPlanRunWithPreviewState();

        $response = $this->actingAs($this->user)
            ->withSession(['current_plan_run_id' => $run->id])
            ->putJson('/plan/preview/blocks/block-001', [
                'duration_minutes' => 300, // above maximum of 240
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('duration_minutes');
    }

    public function test_update_block_validates_date_format(): void
    {
        $run = $this->createPlanRunWithPreviewState();

        $response = $this->actingAs($this->user)
            ->withSession(['current_plan_run_id' => $run->id])
            ->putJson('/plan/preview/blocks/block-001', [
                'date' => 'invalid-date',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('date');
    }

    public function test_update_block_validates_time_format(): void
    {
        $run = $this->createPlanRunWithPreviewState();

        $response = $this->actingAs($this->user)
            ->withSession(['current_plan_run_id' => $run->id])
            ->putJson('/plan/preview/blocks/block-001', [
                'start_time' => '25:99', // invalid time
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('start_time');
    }

    public function test_update_block_returns_404_without_plan_run(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/plan/preview/blocks/block-001', [
                'duration_minutes' => 90,
            ]);

        $response->assertStatus(404)
            ->assertJson(['error' => 'No preview data available']);
    }

    public function test_delete_block_removes_block(): void
    {
        $run = $this->createPlanRunWithPreviewState();

        $response = $this->actingAs($this->user)
            ->withSession(['current_plan_run_id' => $run->id])
            ->deleteJson('/plan/preview/blocks/block-002');

        $response->assertStatus(200);

        $data = $response->json();
        $blockIds = collect($data['work_blocks'])->pluck('id')->all();

        $this->assertNotContains('block-002', $blockIds);
        $this->assertContains('block-001', $blockIds);
        $this->assertContains('block-003', $blockIds);
    }

    public function test_delete_block_redistributes_effort(): void
    {
        $run = $this->createPlanRunWithPreviewState();

        $response = $this->actingAs($this->user)
            ->withSession(['current_plan_run_id' => $run->id])
            ->deleteJson('/plan/preview/blocks/block-002');

        $response->assertStatus(200);

        $data = $response->json();
        $block1 = collect($data['work_blocks'])->firstWhere('id', 'block-001');
        $block3 = collect($data['work_blocks'])->firstWhere('id', 'block-003');

        // 60 minutes from deleted block should be split between remaining blocks
        $this->assertEquals(90, $block1['duration_minutes']);
        $this->assertEquals(90, $block3['duration_minutes']);
    }

    public function test_delete_block_returns_404_without_plan_run(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/plan/preview/blocks/block-001');

        $response->assertStatus(404)
            ->assertJson(['error' => 'No preview data available']);
    }

    public function test_update_assignment_settings_changes_allow_work_on_due_date(): void
    {
        $run = $this->createPlanRunWithPreviewState();

        $response = $this->actingAs($this->user)
            ->withSession(['current_plan_run_id' => $run->id])
            ->putJson('/plan/preview/assignments/assignment-abc/settings', [
                'allow_work_on_due_date' => false,
            ]);

        $response->assertStatus(200);

        $data = $response->json();
        $assignment = collect($data['assignments'])->firstWhere('id', 'assignment-abc');

        $this->assertFalse($assignment['allow_work_on_due_date']);
    }

    public function test_update_assignment_settings_returns_404_without_plan_run(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/plan/preview/assignments/assignment-abc/settings', [
                'allow_work_on_due_date' => false,
            ]);

        $response->assertStatus(404)
            ->assertJson(['error' => 'No preview data available']);
    }

    public function test_regenerate_resets_preview_state(): void
    {
        $run = PlanRun::create([
            'id' => 'test-run-456',
            'user_id' => $this->user->id,
            'token' => 'test-token',
            'paths' => [
                'canvas' => 'nonexistent/canvas.ics',
                'studyplan_ics' => 'nonexistent/StudyPlan.ics',
            ],
            'settings' => [],
            'preview_state' => $this->getSamplePreviewState(),
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_plan_run_id' => $run->id])
            ->postJson('/plan/preview/regenerate');

        // Without valid ICS files, should return error
        $response->assertStatus(500)
            ->assertJson(['error' => 'Could not regenerate preview']);
    }

    public function test_regenerate_returns_404_without_plan_run(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/plan/preview/regenerate');

        $response->assertStatus(404)
            ->assertJson(['error' => 'No active plan run']);
    }

    public function test_finalize_returns_download_url(): void
    {
        $run = $this->createPlanRunWithPreviewState();

        $response = $this->actingAs($this->user)
            ->withSession(['current_plan_run_id' => $run->id])
            ->postJson('/plan/preview/finalize');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'download_url',
            ])
            ->assertJson(['success' => true]);
    }

    public function test_finalize_returns_404_without_plan_run(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/plan/preview/finalize');

        $response->assertStatus(404)
            ->assertJson(['error' => 'No preview data available']);
    }

    public function test_preview_page_redirects_without_plan_run(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/plan/preview');

        $response->assertRedirect('/plan/import')
            ->assertSessionHasErrors('preview');
    }

    public function test_preview_page_renders_with_plan_run(): void
    {
        $run = $this->createPlanRunWithPreviewState();

        $response = $this->actingAs($this->user)
            ->withSession(['current_plan_run_id' => $run->id])
            ->get('/plan/preview');

        $response->assertStatus(200)
            ->assertSee('Preview Your Study Plan');
    }

    public function test_unauthenticated_user_redirects_to_login(): void
    {
        $response = $this->get('/plan/preview');

        $response->assertRedirect('/login');
    }

    public function test_user_cannot_access_another_users_plan_run(): void
    {
        $otherUser = User::factory()->create();
        $run = PlanRun::create([
            'id' => 'other-user-run',
            'user_id' => $otherUser->id,
            'token' => 'test-token',
            'paths' => [
                'canvas' => 'plans/2/other-user-run/inputs/canvas.ics',
            ],
            'settings' => [],
            'preview_state' => $this->getSamplePreviewState(),
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_plan_run_id' => $run->id])
            ->getJson('/plan/preview/data');

        // Should not find run because user_id doesn't match
        $response->assertStatus(404);
    }
}
