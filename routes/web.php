<?php

use App\Http\Controllers\PlanController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('plan.import'));

Route::prefix('plan')->name('plan.')->group(function () {
    // Step 1: Import UI
    Route::get('/import', [PlanController::class, 'showImport'])->name('import');
    Route::post('/import', [PlanController::class, 'handleImport'])->name('import.handle');

    // Step 2: Generate (runs jar later)
    Route::post('/generate', [PlanController::class, 'generate'])->name('generate');

    // Step 3: Preview agenda (reads plan_events.json later)
    Route::get('/preview', [PlanController::class, 'preview'])->name('preview');

    // Step 4: Download (serves StudyPlan.ics later)
    Route::get('/download', [PlanController::class, 'download'])->name('download');

    Route::get('/run/{runId}/canvas.ics', [PlanController::class, 'serveCanvasIcs'])->name('run.canvas');

});




