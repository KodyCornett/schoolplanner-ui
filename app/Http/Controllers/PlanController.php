<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportPlanRequest;
use App\Jobs\CleanupOldPlanRuns;
use App\Models\PlanRun;
use App\Services\EffortRedistributor;
use App\Services\IcsGenerator;
use App\Services\IcsParser;
use App\Services\PlanEventsBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function showImport()
    {
        $user = auth()->user();
        $run = $user->planRuns()->first();

        return view('plan.import', [
            'run' => $run,
        ]);
    }

    public function handleImport(ImportPlanRequest $request)
    {
        $user = auth()->user();
        $userId = $user->id;
        $runId = (string) Str::uuid();
        $token = Str::random(40);
        $baseDir = "plans/{$userId}/{$runId}";
        $inputDir = "{$baseDir}/inputs";

        Storage::makeDirectory($inputDir);

        // 1) Canvas ICS (upload or URL)
        $canvasPath = null;

        if ($request->hasFile('canvas_ics')) {
            $canvasPath = $request->file('canvas_ics')->storeAs($inputDir, 'canvas.ics');
        } else {
            // Fetch URL and store
            $url = $request->input('canvas_url');
            $res = Http::timeout(15)->get($url);

            if (! $res->successful()) {
                return back()
                    ->withErrors(['canvas_url' => 'Could not fetch the Canvas .ics from the provided URL.'])
                    ->withInput();
            }

            $canvasPath = "{$inputDir}/canvas.ics";
            Storage::put($canvasPath, $res->body());
        }

        // 2) Busy ICS optional upload
        $busyPath = null;
        if ($request->hasFile('busy_ics')) {
            $busyPath = $request->file('busy_ics')->storeAs($inputDir, 'busy.ics');
        }

        // 3) Settings (store normalized)
        $settings = [
            'horizon' => (int) ($request->input('horizon', 30)),
            'soft_cap' => (int) ($request->input('soft_cap', 4)),
            'hard_cap' => (int) ($request->input('hard_cap', 5)),
            'skip_weekends' => (bool) ($request->boolean('skip_weekends')),
            'busy_weight' => (float) ($request->input('busy_weight', 1)),
        ];

        // Create PlanRun record
        $run = PlanRun::create([
            'id' => $runId,
            'user_id' => $userId,
            'token' => $token,
            'paths' => [
                'canvas' => $canvasPath,
                'busy' => $busyPath,
                'out_dir' => $baseDir.'/out',
            ],
            'settings' => $settings,
        ]);

        // Store current run ID in session for convenience
        session(['current_plan_run_id' => $runId]);

        // Cleanup old runs (keep last 3)
        CleanupOldPlanRuns::dispatch($userId)->delay(now()->addMinutes(1));

        // Automatically proceed to generate the plan
        return $this->generateForRun($run);
    }

    public function serveCanvasIcs(string $runId)
    {
        $t = request()->query('t');

        $run = PlanRun::find($runId);
        if (! $run) {
            abort(404);
        }

        if (! hash_equals($run->token ?? '', (string) $t)) {
            abort(403);
        }

        $canvasRel = $run->paths['canvas'] ?? null;
        if (! $canvasRel || ! Storage::exists($canvasRel)) {
            abort(404);
        }

        return response(Storage::get($canvasRel), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="canvas.ics"',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function generate()
    {
        $runId = session('current_plan_run_id');
        $run = $runId ? PlanRun::where('user_id', auth()->id())->find($runId) : null;

        if (! $run) {
            // Try latest run
            $run = auth()->user()->planRuns()->first();
        }

        if (! $run) {
            return redirect()->route('plan.import')
                ->withErrors(['import' => 'No active plan run. Please import a Canvas calendar first.']);
        }

        return $this->generateForRun($run);
    }

    private function generateForRun(PlanRun $run)
    {
        $runId = $run->id;
        $userId = $run->user_id;
        $paths = $run->paths;
        $canvasRel = $paths['canvas'] ?? null;
        $busyRel = $paths['busy'] ?? null;

        if (! $canvasRel || ! Storage::exists($canvasRel)) {
            return redirect()->route('plan.import')
                ->withErrors(['import' => 'Canvas .ics is missing. Please re-import.']);
        }

        $baseRel = "plans/{$userId}/{$runId}";
        $outRel = $paths['out_dir'] ?? "{$baseRel}/out";
        Storage::makeDirectory($outRel);

        $java = config('schoolplan.java_bin');
        $jar = config('schoolplan.jar_path');

        if (! file_exists($java)) {
            return redirect()->route('plan.import')
                ->withErrors(['engine' => "Java not found at: {$java}"]);
        }

        if (! file_exists($jar)) {
            return redirect()->route('plan.import')
                ->withErrors(['engine' => "Jar not found at: {$jar}"]);
        }

        $settings = $run->settings ?? [];

        // Use the static file server (separate process) to avoid deadlock with artisan serve
        $canvasUrl = "http://127.0.0.1:8001/{$canvasRel}";

        $props = [
            "ICAL_URLS={$canvasUrl}",
            'horizon='.(int) ($settings['horizon'] ?? 30),
            'softCap='.(int) ($settings['soft_cap'] ?? 4),
            'hardCap='.(int) ($settings['hard_cap'] ?? 5),
            'skipWeekends='.(! empty($settings['skip_weekends']) ? 'true' : 'false'),
            'busyWeight='.(float) ($settings['busy_weight'] ?? 1),
        ];

        // Write per-run config
        $configRel = "{$baseRel}/local.properties";
        Storage::put($configRel, implode(PHP_EOL, $props).PHP_EOL);

        $args = [
            $java,
            '-jar',
            $jar,
            'run',
            '--out', Storage::path($outRel),
            '--config', Storage::path($configRel),
        ];

        if ($busyRel && Storage::exists($busyRel)) {
            $args[] = '--busy';
            $args[] = Storage::path($busyRel);
        }

        // Run with cwd set to the run folder
        $cwd = Storage::path($baseRel);

        // On Windows, Java needs SystemRoot to initialize Sockets properly.
        $env = array_merge($_ENV, $_SERVER, [
            'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
            'SystemDrive' => getenv('SystemDrive') ?: 'C:',
        ]);

        $result = Process::timeout(120)
            ->path($cwd)
            ->env($env)
            ->run($args);

        if (str_contains($result->output(), 'ERROR:')) {
            return redirect()->route('plan.import')
                ->withErrors(['engine' => trim($result->output())]);
        }

        \Log::info('SchoolPlan engine run', [
            'args' => $args,
            'cwd' => $cwd,
            'exitCode' => $result->exitCode(),
            'output' => $result->output(),
            'errorOutput' => $result->errorOutput(),
        ]);

        if (! $result->successful()) {
            return redirect()->route('plan.import')
                ->withErrors(['engine' => "Engine failed:\n".$result->errorOutput()]);
        }

        // Expected outputs (engine may write to /out or default /exports)
        $icsRel = $outRel.'/StudyPlan.ics';
        $jsonRel = $outRel.'/plan_events.json';

        $fallbackDir = "{$baseRel}/exports";
        $fallbackJson = "{$fallbackDir}/plan_events.json";

        // If the engine names the ICS dynamically, pick the newest .ics in exports
        if (! Storage::exists($icsRel) && Storage::exists($fallbackDir)) {
            $files = collect(Storage::files($fallbackDir))
                ->filter(fn ($f) => str_ends_with(strtolower($f), '.ics'))
                ->sortByDesc(fn ($f) => Storage::lastModified($f));

            $latest = $files->first();
            if ($latest) {
                $icsRel = $latest;
            }
        }

        // Must have ICS at minimum
        if (! Storage::exists($icsRel)) {
            return redirect()->route('plan.import')
                ->withErrors(['engine' => 'Engine ran, but no .ics output was found. Check: '
                    .Storage::path($outRel).' and '.Storage::path($fallbackDir),
                ]);
        }

        // JSON is optional for now
        if (! Storage::exists($jsonRel) && Storage::exists($fallbackJson)) {
            $jsonRel = $fallbackJson;
        }

        $paths['studyplan_ics'] = $icsRel;
        $paths['plan_events_json'] = Storage::exists($jsonRel) ? $jsonRel : null;
        $paths['config'] = $configRel;

        $run->paths = $paths;

        // Build preview state from ICS files
        $previewState = $this->buildPreviewState($run);
        if ($previewState) {
            $run->preview_state = $previewState;
        }

        $run->save();

        session(['current_plan_run_id' => $run->id]);

        if ($run->preview_state) {
            return redirect()->route('plan.preview')->with('status', 'Generated plan successfully.');
        }

        return redirect()->route('plan.import')->with('status', 'Generated ICS successfully. Preview not available.');
    }

    private function buildPreviewState(PlanRun $run): ?array
    {
        $paths = $run->paths;
        $canvasPath = $paths['canvas'] ?? null;
        $icsPath = $paths['studyplan_ics'] ?? null;
        $busyPath = $paths['busy'] ?? null;

        if (! $canvasPath || ! Storage::exists($canvasPath)) {
            return null;
        }

        if (! $icsPath || ! Storage::exists($icsPath)) {
            return null;
        }

        $canvasIcs = Storage::get($canvasPath);
        $engineIcs = Storage::get($icsPath);
        $settings = $run->settings ?? [];

        // Load busy ICS if available
        $busyIcs = null;
        if ($busyPath && Storage::exists($busyPath)) {
            $busyIcs = Storage::get($busyPath);
        }

        $parser = new IcsParser;
        $builder = new PlanEventsBuilder($parser);

        return $builder->build($canvasIcs, $engineIcs, $settings, $busyIcs);
    }

    public function preview()
    {
        $run = $this->getCurrentRun();

        if (! $run || ! $run->preview_state) {
            return redirect()->route('plan.import')
                ->withErrors(['preview' => 'No preview data available. Please generate a plan first.']);
        }

        return view('plan.preview', ['run' => $run]);
    }

    public function previewData(): JsonResponse
    {
        $run = $this->getCurrentRun();

        if (! $run || ! $run->preview_state) {
            return response()->json(['error' => 'No preview data available'], 404);
        }

        return response()->json($run->preview_state);
    }

    public function updateBlock(string $blockId): JsonResponse
    {
        $run = $this->getCurrentRun();

        if (! $run || ! $run->preview_state) {
            return response()->json(['error' => 'No preview data available'], 404);
        }

        $updates = request()->validate([
            'date' => 'sometimes|date_format:Y-m-d',
            'start_time' => 'sometimes|date_format:H:i',
            'duration_minutes' => 'sometimes|integer|min:15|max:240',
        ]);

        $redistributor = new EffortRedistributor;
        $newState = $redistributor->afterBlockUpdate($run->preview_state, $blockId, $updates);

        $run->preview_state = $newState;
        $run->save();

        return response()->json($newState);
    }

    public function deleteBlock(string $blockId): JsonResponse
    {
        $run = $this->getCurrentRun();

        if (! $run || ! $run->preview_state) {
            return response()->json(['error' => 'No preview data available'], 404);
        }

        $redistributor = new EffortRedistributor;
        $newState = $redistributor->afterBlockDelete($run->preview_state, $blockId);

        $run->preview_state = $newState;
        $run->save();

        return response()->json($newState);
    }

    public function updateAssignmentSettings(string $assignmentId): JsonResponse
    {
        $run = $this->getCurrentRun();

        if (! $run || ! $run->preview_state) {
            return response()->json(['error' => 'No preview data available'], 404);
        }

        $settings = request()->validate([
            'allow_work_on_due_date' => 'sometimes|boolean',
        ]);

        $previewState = $run->preview_state;
        $assignments = $previewState['assignments'] ?? [];

        // Find and update the assignment
        foreach ($assignments as $index => $assignment) {
            if ($assignment['id'] === $assignmentId) {
                if (isset($settings['allow_work_on_due_date'])) {
                    $assignments[$index]['allow_work_on_due_date'] = (bool) $settings['allow_work_on_due_date'];
                }
                break;
            }
        }

        $previewState['assignments'] = $assignments;
        $run->preview_state = $previewState;
        $run->save();

        return response()->json($previewState);
    }

    public function createBlock(string $assignmentId): JsonResponse
    {
        $run = $this->getCurrentRun();

        if (! $run || ! $run->preview_state) {
            return response()->json(['error' => 'No preview data available'], 404);
        }

        $data = request()->validate([
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'duration_minutes' => 'sometimes|integer|min:15|max:240',
        ]);

        $previewState = $run->preview_state;
        $assignments = $previewState['assignments'] ?? [];
        $workBlocks = $previewState['work_blocks'] ?? [];

        // Find the assignment
        $assignment = collect($assignments)->firstWhere('id', $assignmentId);
        if (! $assignment) {
            return response()->json(['error' => 'Assignment not found'], 404);
        }

        // Generate a new block ID
        $maxBlockNum = 0;
        foreach ($workBlocks as $block) {
            if (preg_match('/^block-(\d+)$/', $block['id'], $m)) {
                $maxBlockNum = max($maxBlockNum, (int) $m[1]);
            }
        }
        $newBlockId = 'block-'.str_pad($maxBlockNum + 1, 3, '0', STR_PAD_LEFT);

        // Create the new block
        $durationMinutes = $data['duration_minutes'] ?? 60;
        $newBlock = [
            'id' => $newBlockId,
            'assignment_id' => $assignmentId,
            'date' => $data['date'],
            'start_time' => $data['start_time'],
            'duration_minutes' => $durationMinutes,
            'label' => '[added]',
            'is_anchored' => true,
            'original_duration_minutes' => $durationMinutes,
        ];

        $workBlocks[] = $newBlock;
        $previewState['work_blocks'] = $workBlocks;

        // Recalculate total effort for the assignment
        $totalEffort = 0;
        foreach ($workBlocks as $block) {
            if ($block['assignment_id'] === $assignmentId) {
                $totalEffort += $block['duration_minutes'];
            }
        }

        foreach ($previewState['assignments'] as $index => $a) {
            if ($a['id'] === $assignmentId) {
                $previewState['assignments'][$index]['total_effort_minutes'] = $totalEffort;
                break;
            }
        }

        $run->preview_state = $previewState;
        $run->save();

        return response()->json($previewState);
    }

    public function regenerate(): JsonResponse
    {
        $run = $this->getCurrentRun();

        if (! $run) {
            return response()->json(['error' => 'No active plan run'], 404);
        }

        // Re-build preview state from original ICS files
        $previewState = $this->buildPreviewState($run);

        if (! $previewState) {
            return response()->json(['error' => 'Could not regenerate preview'], 500);
        }

        $run->preview_state = $previewState;
        $run->save();

        return response()->json($previewState);
    }

    public function finalizeCalendar(): JsonResponse
    {
        $run = $this->getCurrentRun();

        if (! $run || ! $run->preview_state) {
            return response()->json(['error' => 'No preview data available'], 404);
        }

        $generator = new IcsGenerator;
        $icsContent = $generator->generate($run->preview_state);

        // Save to storage
        $userId = $run->user_id;
        $runId = $run->id;
        $timestamp = now()->format('Ymd_His');
        $filename = "StudyPlan_{$timestamp}.ics";
        $path = "plans/{$userId}/{$runId}/exports/{$filename}";

        Storage::put($path, $icsContent);

        // Update model with new ICS path
        $paths = $run->paths;
        $paths['studyplan_ics'] = $path;
        $run->paths = $paths;
        $run->save();

        return response()->json([
            'success' => true,
            'download_url' => route('plan.download'),
        ]);
    }

    public function download()
    {
        $run = $this->getCurrentRun();

        if (! $run) {
            return redirect()->route('plan.import')
                ->withErrors(['download' => 'No plan run found.']);
        }

        $icsRel = $run->paths['studyplan_ics'] ?? null;

        if (! $icsRel || ! Storage::exists($icsRel)) {
            return redirect()->route('plan.import')
                ->withErrors(['download' => 'No generated .ics found. Please Generate first.']);
        }

        return Storage::download($icsRel, 'StudyPlan.ics', [
            'Content-Type' => 'text/calendar; charset=utf-8',
        ]);
    }

    private function getCurrentRun(): ?PlanRun
    {
        $runId = session('current_plan_run_id');

        if ($runId) {
            $run = PlanRun::where('user_id', auth()->id())->find($runId);
            if ($run) {
                return $run;
            }
        }

        // Fallback to latest run
        return auth()->user()->planRuns()->first();
    }
}
