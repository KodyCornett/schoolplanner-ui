<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function showImport()
    {
        // TODO: return import form (upload or URL) + optional busy ICS + settings
        return view('plan.import');
    }

    public function handleImport(Request $request)
    {
        // TODO: validate inputs
        // TODO: store uploaded files (or fetch URL content later)
        // TODO: stash paths/settings in session for generate step

        return redirect()->route('plan.import')
            ->with('status', 'Import received (stub). Next: Generate.');
    }

    public function generate(Request $request)
    {
        // TODO:
        // - read session inputs/settings
        // - build CLI args
        // - run jar via Process
        // - store outputs somewhere (storage/app/plans/{uuid}/...)

        return redirect()->route('plan.preview')
            ->with('status', 'Generated plan (stub).');
    }

    public function preview()
    {
        // TODO: read plan_events.json and render agenda
        return view('plan.preview');
    }

    public function download()
    {
        // TODO: return StudyPlan.ics as download response
        abort(501, 'Not implemented yet.');
    }
}
