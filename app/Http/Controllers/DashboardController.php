<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $planRuns = $user->planRuns()->take(3)->get();

        return view('dashboard', [
            'planRuns' => $planRuns,
        ]);
    }
}
