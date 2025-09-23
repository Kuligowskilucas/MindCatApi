<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DiaryController;
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

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', [AuthController::class, 'userProfile']);
    Route::put('/user/update', [UserController::class, 'update']);
    Route::delete('/user/delete', [UserController::class, 'destroy']);

    Route::post('/diary', [DiaryController::class, 'store']); 
    Route::get('/diary', [DiaryController::class, 'index']); 
    Route::delete('/diary/{id}', [DiaryController::class, 'destroy']);
});
