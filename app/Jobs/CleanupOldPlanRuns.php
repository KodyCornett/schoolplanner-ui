<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class CleanupOldPlanRuns implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $userId
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        // Keep only the last 3 runs
        $oldRuns = $user->planRuns()
            ->orderByDesc('created_at')
            ->skip(3)
            ->take(100)
            ->get();

        foreach ($oldRuns as $run) {
            // Delete files from storage
            Storage::deleteDirectory("plans/{$this->userId}/{$run->id}");
            $run->delete();
        }
    }
}
