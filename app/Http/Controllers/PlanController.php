<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportPlanRequest;
use App\Services\EffortRedistributor;
use App\Services\IcsGenerator;
use App\Services\IcsParser;
use App\Services\PlanEventsBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function showImport()
    {
        $run = session('plan.run');

        return view('plan.import', [
            'run' => $run,
        ]);
    }

    public function handleImport(ImportPlanRequest $request)
    {
        $runId = session('plan.run.id') ?? (string) Str::uuid();
        $token = Str::random(40);
        $baseDir = "plans/{$runId}";
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

            if (!$res->successful()) {
                return back()
                    ->withErrors(['canvas_url' => 'Could not fetch the Canvas .ics from the provided URL.'])
                    ->withInput();
            }

            $canvasPath = "{$inputDir}/canvas.ics";
            Storage::put($canvasPath, $res->body());
        }

        Cache::put("plan_run:{$runId}", [
            'token' => $token,
            'canvas' => $canvasPath,
        ], now()->addHours(2));

        // 2) Busy ICS optional upload
        $busyPath = null;
        if ($request->hasFile('busy_ics')) {
            $busyPath = $request->file('busy_ics')->storeAs($inputDir, 'busy.ics');
        }

        // 3) Settings (store normalized)
        $settings = [
            'horizon'       => (int) ($request->input('horizon', 30)),
            'soft_cap'      => (int) ($request->input('soft_cap', 4)),
            'hard_cap'      => (int) ($request->input('hard_cap', 5)),
            'skip_weekends' => (bool) ($request->boolean('skip_weekends')),
            'busy_weight'   => (float) ($request->input('busy_weight', 1)),
        ];

        session([
            'plan.run' => [
                'id' => $runId,
                'token' => $token,
                'paths' => [
                    'canvas' => $canvasPath,
                    'busy'   => $busyPath,
                    'out_dir'=> $baseDir . '/out',
                ],
                'settings' => $settings,
            ],
        ]);

        return redirect()->route('plan.import')
            ->with('status', "Import saved. Run ID: {$runId}");
    }

    public function serveCanvasIcs(string $runId)
    {
        $t = request()->query('t');

        $run = Cache::get("plan_run:{$runId}");
        if (!$run) {
            abort(404);
        }

        if (!hash_equals($run['token'] ?? '', (string)$t)) {
            abort(404);
        }

        $canvasRel = $run['canvas'] ?? null;
        if (!$canvasRel || !Storage::exists($canvasRel)) {
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
        $run = session('plan.run');

        if (!$run) {
            return redirect()->route('plan.import')
                ->withErrors(['import' => 'No active plan run. Please import a Canvas calendar first.']);
        }

        $runId = $run['id'];
        $canvasRel = $run['paths']['canvas'] ?? null;
        $busyRel   = $run['paths']['busy'] ?? null;

        if (!$canvasRel || !Storage::exists($canvasRel)) {
            return redirect()->route('plan.import')
                ->withErrors(['import' => 'Canvas .ics is missing. Please re-import.']);
        }

        $baseRel = "plans/{$runId}";
        $outRel  = $run['paths']['out_dir'] ?? "{$baseRel}/out";
        Storage::makeDirectory($outRel);

        $java = config('schoolplan.java_bin');
        $jar  = config('schoolplan.jar_path');

        if (!file_exists($java)) {
            return redirect()->route('plan.import')
                ->withErrors(['engine' => "Java not found at: {$java}"]);
        }

        if (!file_exists($jar)) {
            return redirect()->route('plan.import')
                ->withErrors(['engine' => "Jar not found at: {$jar}"]);
        }

        $settings = $run['settings'] ?? [];

// Use the static file server (separate process) to avoid deadlock with artisan serve
        $canvasRel = $run['paths']['canvas']; // like: plans/<runId>/inputs/canvas.ics
        $canvasUrl = "http://127.0.0.1:8001/{$canvasRel}";

        $props = [
            "ICAL_URLS={$canvasUrl}",
            "horizon=" . (int)($settings['horizon'] ?? 30),
            "softCap=" . (int)($settings['soft_cap'] ?? 4),
            "hardCap=" . (int)($settings['hard_cap'] ?? 5),
            "skipWeekends=" . (!empty($settings['skip_weekends']) ? "true" : "false"),
            "busyWeight=" . (float)($settings['busy_weight'] ?? 1),
        ];


        // Write per-run config
        $configRel = "{$baseRel}/local.properties";
        Storage::put($configRel, implode(PHP_EOL, $props) . PHP_EOL);

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

        // Run with cwd set to the run folder (helps if engine expects project-root-ish behavior)
        $cwd = Storage::path($baseRel);

        // On Windows, Java needs SystemRoot to initialize Sockets properly.
        // Process::run usually inherits environment, but we'll be explicit to be safe.
        $env = array_merge($_ENV, $_SERVER, [
            'SystemRoot'  => getenv('SystemRoot') ?: 'C:\\Windows',
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

        if (!$result->successful()) {
            return redirect()->route('plan.import')
                ->withErrors(['engine' => "Engine failed:\n" . $result->errorOutput()]);
        }

        // Expected outputs (engine may write to /out or default /exports)
        $icsRel  = $outRel . '/StudyPlan.ics';
        $jsonRel = $outRel . '/plan_events.json';

        $fallbackDir  = "{$baseRel}/exports";
        $fallbackJson = "{$fallbackDir}/plan_events.json";

// If the engine names the ICS dynamically, pick the newest .ics in exports
        if (!Storage::exists($icsRel) && Storage::exists($fallbackDir)) {
            $files = collect(Storage::files($fallbackDir))
                ->filter(fn ($f) => str_ends_with(strtolower($f), '.ics'))
                ->sortByDesc(fn ($f) => Storage::lastModified($f));

            $latest = $files->first();
            if ($latest) {
                $icsRel = $latest;
            }
        }

// Must have ICS at minimum
        if (!Storage::exists($icsRel)) {
            return redirect()->route('plan.import')
                ->withErrors(['engine' =>
                    "Engine ran, but no .ics output was found. Check: "
                    . Storage::path($outRel) . " and " . Storage::path($fallbackDir)
                ]);
        }

// JSON is optional for now
        if (!Storage::exists($jsonRel) && Storage::exists($fallbackJson)) {
            $jsonRel = $fallbackJson;
        }

        $run['paths']['studyplan_ics'] = $icsRel;
        $run['paths']['plan_events_json'] = Storage::exists($jsonRel) ? $jsonRel : null;
        $run['paths']['config'] = $configRel;

        // Build preview state from ICS files
        $previewState = $this->buildPreviewState($run);
        if ($previewState) {
            $run['preview_state'] = $previewState;
        }

        session(['plan.run' => $run]);

        if ($run['preview_state'] ?? null) {
            return redirect()->route('plan.preview')->with('status', 'Generated plan successfully.');
        }

        return redirect()->route('plan.import')->with('status', 'Generated ICS successfully. Preview not available.');
    } // <-- closes generate()

    /**
     * Build preview state from Canvas and engine ICS files.
     */
    private function buildPreviewState(array $run): ?array
    {
        $canvasPath = $run['paths']['canvas'] ?? null;
        $icsPath = $run['paths']['studyplan_ics'] ?? null;

        if (!$canvasPath || !Storage::exists($canvasPath)) {
            return null;
        }

        if (!$icsPath || !Storage::exists($icsPath)) {
            return null;
        }

        $canvasIcs = Storage::get($canvasPath);
        $engineIcs = Storage::get($icsPath);
        $settings = $run['settings'] ?? [];

        $parser = new IcsParser();
        $builder = new PlanEventsBuilder($parser);

        return $builder->build($canvasIcs, $engineIcs, $settings);
    }

    public function preview()
    {
        $run = session('plan.run');

        if (!$run || !isset($run['preview_state'])) {
            return redirect()->route('plan.import')
                ->withErrors(['preview' => 'No preview data available. Please generate a plan first.']);
        }

        return view('plan.preview', ['run' => $run]);
    }

    /**
     * Return preview state as JSON for the interactive UI.
     */
    public function previewData(): JsonResponse
    {
        $run = session('plan.run');

        if (!$run || !isset($run['preview_state'])) {
            return response()->json(['error' => 'No preview data available'], 404);
        }

        return response()->json($run['preview_state']);
    }

    /**
     * Update a work block (move/resize).
     */
    public function updateBlock(string $blockId): JsonResponse
    {
        $run = session('plan.run');

        if (!$run || !isset($run['preview_state'])) {
            return response()->json(['error' => 'No preview data available'], 404);
        }

        $updates = request()->validate([
            'date' => 'sometimes|date_format:Y-m-d',
            'start_time' => 'sometimes|date_format:H:i',
            'duration_minutes' => 'sometimes|integer|min:15|max:240',
        ]);

        $redistributor = new EffortRedistributor();
        $newState = $redistributor->afterBlockUpdate($run['preview_state'], $blockId, $updates);

        $run['preview_state'] = $newState;
        session(['plan.run' => $run]);

        return response()->json($newState);
    }

    /**
     * Delete a work block and redistribute its effort.
     */
    public function deleteBlock(string $blockId): JsonResponse
    {
        $run = session('plan.run');

        if (!$run || !isset($run['preview_state'])) {
            return response()->json(['error' => 'No preview data available'], 404);
        }

        $redistributor = new EffortRedistributor();
        $newState = $redistributor->afterBlockDelete($run['preview_state'], $blockId);

        $run['preview_state'] = $newState;
        session(['plan.run' => $run]);

        return response()->json($newState);
    }

    /**
     * Update assignment settings (e.g., allow_work_on_due_date).
     */
    public function updateAssignmentSettings(string $assignmentId): JsonResponse
    {
        $run = session('plan.run');

        if (!$run || !isset($run['preview_state'])) {
            return response()->json(['error' => 'No preview data available'], 404);
        }

        $settings = request()->validate([
            'allow_work_on_due_date' => 'sometimes|boolean',
        ]);

        $previewState = $run['preview_state'];
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
        $run['preview_state'] = $previewState;
        session(['plan.run' => $run]);

        return response()->json($previewState);
    }

    /**
     * Finalize the preview and generate the downloadable ICS file.
     */
    public function finalizeCalendar(): JsonResponse
    {
        $run = session('plan.run');

        if (!$run || !isset($run['preview_state'])) {
            return response()->json(['error' => 'No preview data available'], 404);
        }

        $generator = new IcsGenerator();
        $icsContent = $generator->generate($run['preview_state']);

        // Save to storage
        $runId = $run['id'];
        $timestamp = now()->format('Ymd_His');
        $filename = "StudyPlan_{$timestamp}.ics";
        $path = "plans/{$runId}/exports/{$filename}";

        Storage::put($path, $icsContent);

        // Update session with new ICS path
        $run['paths']['studyplan_ics'] = $path;
        session(['plan.run' => $run]);

        return response()->json([
            'success' => true,
            'download_url' => route('plan.download'),
        ]);
    }

    public function download()
    {
        $run = session('plan.run');
        $icsRel = $run['paths']['studyplan_ics'] ?? null;

        if (!$icsRel || !Storage::exists($icsRel)) {
            return redirect()->route('plan.import')
                ->withErrors(['download' => 'No generated .ics found. Please Generate first.']);
        }

        return Storage::download($icsRel, 'StudyPlan.ics', [
            'Content-Type' => 'text/calendar; charset=utf-8',
        ]);
    }}

