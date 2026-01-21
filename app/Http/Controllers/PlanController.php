<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportPlanRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
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

        // Write per-run config (we'll match keys to your engine after we inspect the init template)
        $configRel = "{$baseRel}/local.properties";
        $props = [
            "horizon=" . (int)($settings['horizon'] ?? 30),
            "softCap=" . (int)($settings['soft_cap'] ?? 4),
            "hardCap=" . (int)($settings['hard_cap'] ?? 5),
            "skipWeekends=" . (!empty($settings['skip_weekends']) ? "true" : "false"),
            "busyWeight=" . (float)($settings['busy_weight'] ?? 1),
        ];
        Storage::put($configRel, implode(PHP_EOL, $props) . PHP_EOL);

        $args = [
            $java,
            '-jar',
            $jar,
            'run',

            '--ical', Storage::path($canvasRel),
            '--out', Storage::path($outRel),
            '--config', Storage::path($configRel),
        ];

        if ($busyRel && Storage::exists($busyRel)) {
            $args[] = '--busy';
            $args[] = Storage::path($busyRel);
        }

        // Run with cwd set to the run folder (helps if engine expects project-root-ish behavior)
        $cwd = Storage::path($baseRel);

        $result = Process::timeout(120)->path($cwd)->run($args);

        if (!$result->successful()) {
            return redirect()->route('plan.import')
                ->withErrors(['engine' => "Engine failed:\n" . $result->errorOutput()]);
        }

        // Expected outputs
        $icsRel  = $outRel . '/StudyPlan.ics';
        $jsonRel = $outRel . '/plan_events.json';

        if (!Storage::exists($icsRel) || !Storage::exists($jsonRel)) {
            return redirect()->route('plan.import')
                ->withErrors(['engine' => "Engine ran, but outputs were not found. Check: " . Storage::path($outRel)]);
        }

        // Save output paths in session for preview/download
        $run['paths']['studyplan_ics'] = $icsRel;
        $run['paths']['plan_events_json'] = $jsonRel;
        $run['paths']['config'] = $configRel;
        session(['plan.run' => $run]);

        return redirect()->route('plan.preview')->with('status', 'Generated plan successfully.');
    }

    public function preview()
    {
        return view('plan.preview', ['run' => session('plan.run')]);
    }

    public function download()
    {
        abort(501, 'Not implemented yet.');
    }
}
