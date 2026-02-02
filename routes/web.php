<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Landing page for guests, dashboard redirect for authenticated users
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
})->name('home');

Route::get('/help', fn () => view('help.index'))->name('help');

// Public pages
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/pricing', [BillingController::class, 'pricing'])->name('billing.pricing');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Billing routes
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('/billing/success', [BillingController::class, 'success'])->name('billing.success');
    Route::get('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
});

// Plan routes - require authentication and email verification
Route::prefix('plan')->name('plan.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/import', [PlanController::class, 'showImport'])->name('import');
    Route::post('/import', [PlanController::class, 'handleImport'])->name('import.handle');
    Route::get('/generate', [PlanController::class, 'generate'])->name('generate');
    Route::get('/preview', [PlanController::class, 'preview'])->name('preview');
    Route::get('/download', [PlanController::class, 'download'])->name('download');

    // Preview API routes - rate limited to prevent abuse
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/preview/data', [PlanController::class, 'previewData'])->name('preview.data');
        Route::put('/preview/blocks/{blockId}', [PlanController::class, 'updateBlock'])->name('preview.blocks.update');
        Route::delete('/preview/blocks/{blockId}', [PlanController::class, 'deleteBlock'])->name('preview.blocks.delete');
        Route::post('/preview/assignments/{assignmentId}/blocks', [PlanController::class, 'createBlock'])->name('preview.blocks.create');
        Route::put('/preview/assignments/{assignmentId}/settings', [PlanController::class, 'updateAssignmentSettings'])->name('preview.assignments.settings');
        Route::post('/preview/regenerate', [PlanController::class, 'regenerate'])->name('preview.regenerate');
        Route::post('/preview/finalize', [PlanController::class, 'finalizeCalendar'])->name('preview.finalize');
    });
});

// Canvas ICS serving endpoint - no auth (uses token validation)
Route::get('/plan/canvas/{runId}', [PlanController::class, 'serveCanvasIcs'])->name('plan.canvas');

// Stripe Webhook (CSRF excluded in bootstrap/app.php)
Route::post('/stripe/webhook', [\Laravel\Cashier\Http\Controllers\WebhookController::class, 'handleWebhook'])->name('cashier.webhook');

require __DIR__.'/auth.php';
