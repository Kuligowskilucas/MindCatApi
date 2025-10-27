<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DiaryController;
use App\Http\Controllers\MoodController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\PatientController;
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

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/diary-password', [ProfileController::class, 'setDiaryPassword']);

    Route::get('/user', [AuthController::class, 'userProfile']);
    Route::put('/user/update', [UserController::class, 'update']);
    Route::delete('/user/delete', [UserController::class, 'destroy']);

    Route::get('/me', [UserController::class, 'me']);

    Route::post('/diary', [DiaryController::class, 'store']);
    Route::get('/diary', [DiaryController::class, 'index']);
    Route::delete('/diary/{id}', [DiaryController::class, 'destroy']);

    Route::post('/moods', [MoodController::class, 'store']);
    Route::get('/moods',  [MoodController::class, 'index']);
    Route::delete('/moods/{id}', [MoodController::class, 'destroy']);

    Route::post('/exercises/complete', [ExerciseController::class,'complete']);
    Route::get('/exercises/history', [ExerciseController::class,'history']);

    Route::middleware('role:pro')->group(function () {
        Route::post('/links', [LinkController::class,'store']);
        Route::get('/patients', [LinkController::class,'indexPatients']);
        Route::delete('/links/{patientId}', [LinkController::class,'destroy']);

        Route::post('/tasks', [TaskController::class,'store']);
        Route::get('/tasks', [TaskController::class,'index'])->name('tasks.assigned');
        Route::get('/patients/{id}/summary', [PatientController::class,'summary']);
        Route::delete('/tasks/{task}', [TaskController::class,'destroy']);
    });
});
