<?php

use App\Http\Controllers\MPesaController;
use Illuminate\Support\Facades\Route;

Route::post('push-stk', [MPesaController::class, 'push_stk'])->name('m_pesa_push_stk');
Route::post('push-stk-call-back', [MPesaController::class, 'call_back'])->name('m_pesa_push_stk_call_back');