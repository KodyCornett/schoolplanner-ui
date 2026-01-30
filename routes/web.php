<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/help', fn () => view('help.index'))->name('help');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Plan routes - require authentication
Route::prefix('plan')->name('plan.')->middleware('auth')->group(function () {
    Route::get('/import', [PlanController::class, 'showImport'])->name('import');
    Route::post('/import', [PlanController::class, 'handleImport'])->name('import.handle');
    Route::get('/generate', [PlanController::class, 'generate'])->name('generate');
    Route::get('/preview', [PlanController::class, 'preview'])->name('preview');
    Route::get('/preview/data', [PlanController::class, 'previewData'])->name('preview.data');
    Route::put('/preview/blocks/{blockId}', [PlanController::class, 'updateBlock'])->name('preview.blocks.update');
    Route::delete('/preview/blocks/{blockId}', [PlanController::class, 'deleteBlock'])->name('preview.blocks.delete');
    Route::post('/preview/assignments/{assignmentId}/blocks', [PlanController::class, 'createBlock'])->name('preview.blocks.create');
    Route::put('/preview/assignments/{assignmentId}/settings', [PlanController::class, 'updateAssignmentSettings'])->name('preview.assignments.settings');
    Route::post('/preview/regenerate', [PlanController::class, 'regenerate'])->name('preview.regenerate');
    Route::post('/preview/finalize', [PlanController::class, 'finalizeCalendar'])->name('preview.finalize');
    Route::get('/download', [PlanController::class, 'download'])->name('download');
});

// Canvas ICS serving endpoint - no auth (uses token validation)
Route::get('/plan/canvas/{runId}', [PlanController::class, 'serveCanvasIcs'])->name('plan.canvas');

require __DIR__.'/auth.php';
