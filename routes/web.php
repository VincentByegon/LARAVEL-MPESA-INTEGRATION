<?php

use App\Http\Controllers\MpesaController;
use Illuminate\Support\Facades\Route;

// Dashboard (web view)
Route::get('/payments/dashboard', [MpesaController::class, 'dashboard'])->name('payments.dashboard');