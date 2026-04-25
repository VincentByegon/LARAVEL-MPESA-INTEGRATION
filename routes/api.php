<?php

use App\Http\Controllers\MpesaController;
use Illuminate\Support\Facades\Route;

// ─────────────────────────────────────────────────────────────────────────────
// M-PESA C2B ROUTES
// ─────────────────────────────────────────────────────────────────────────────

// Safaricom Callback URLs (must be publicly accessible, no auth middleware)
// Safaricom POSTs to these when a customer pays via SIM Toolkit / Lipa na M-Pesa
Route::prefix('mpesa')->group(function () {

    // Safaricom callbacks — MUST exclude CSRF (already excluded in api routes)
    Route::post('/validation',   [MpesaController::class, 'validation']);    // Safaricom asks: accept payment?
    Route::post('/confirmation', [MpesaController::class, 'confirmation']);  // Safaricom tells you: payment done!

    // Admin actions
    Route::post('/register',  [MpesaController::class, 'register']);   // Register URLs with Safaricom (run once)
    Route::post('/simulate',  [MpesaController::class, 'simulate']);   // Sandbox testing only
    Route::get('/payments',   [MpesaController::class, 'payments']);   // JSON list of payments
    Route::get('/latest',     [MpesaController::class, 'latestPayments']); // For live polling
});