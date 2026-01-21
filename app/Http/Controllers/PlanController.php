<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportPlanRequest;
use Illuminate\Support\Facades\Http;
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
        // Commit #4: actually run jar here (Process)
        return redirect()->route('plan.preview')->with('status', 'Generated plan (stub).');
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
