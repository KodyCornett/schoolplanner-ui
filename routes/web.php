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

    // Step 3: Preview agenda
    Route::get('/preview', [PlanController::class, 'preview'])->name('preview');
    Route::get('/preview/data', [PlanController::class, 'previewData'])->name('preview.data');
    Route::post('/preview/block/{blockId}', [PlanController::class, 'updateBlock'])->name('preview.block.update');
    Route::delete('/preview/block/{blockId}', [PlanController::class, 'deleteBlock'])->name('preview.block.delete');
    Route::post('/preview/assignment/{assignmentId}/settings', [PlanController::class, 'updateAssignmentSettings'])->name('preview.assignment.settings');
    Route::post('/preview/assignment/{assignmentId}/block', [PlanController::class, 'createBlock'])->name('preview.assignment.block.create');
    Route::post('/preview/finalize', [PlanController::class, 'finalizeCalendar'])->name('preview.finalize');
    Route::post('/preview/regenerate', [PlanController::class, 'regenerate'])->name('preview.regenerate');

    // Step 4: Download (serves StudyPlan.ics later)
    Route::get('/download', [PlanController::class, 'download'])->name('download');

    Route::get('/run/{runId}/canvas.ics', [PlanController::class, 'serveCanvasIcs'])->name('run.canvas');

});




