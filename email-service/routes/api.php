<?php

use App\Http\Controllers\Api\EmailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/email/send-order', [EmailController::class, 'sendOrder']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
