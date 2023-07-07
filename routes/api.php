<?php

use App\Http\Controllers\Api\Auth\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::controller(LoginController::class)->prefix('auth')->group(function () {
    Route::post('login', 'login');
    Route::post('verify-otp', 'verifyOtp');
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
