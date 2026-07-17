<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DiaryController;
use App\Http\Controllers\MoodController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\CredentialController;
use App\Http\Controllers\AdminCredentialController;
use App\Http\Controllers\PasswordResetController;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/forgot-password', [PasswordResetController::class, 'sendCode'])->middleware('throttle:3,1');
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/diary-password', [ProfileController::class, 'setDiaryPassword'])
        ->middleware('throttle:5,1');

    Route::get('/user', [AuthController::class, 'userProfile']);
    Route::put('/user/update', [UserController::class, 'update']);
    Route::delete('/user/delete', [UserController::class, 'destroy']);

    Route::get('/me', [UserController::class, 'me']);

    Route::post('/diary', [DiaryController::class, 'store']);
    Route::post('/diary/list', [DiaryController::class, 'index'])->middleware('throttle:5,1');
    Route::delete('/diary/{id}', [DiaryController::class, 'destroy'])->middleware('throttle:5,1');

    Route::post('/moods', [MoodController::class, 'store']);
    Route::get('/moods',  [MoodController::class, 'index']);
    Route::delete('/moods/{id}', [MoodController::class, 'destroy']);

    Route::get('/tasks', [TaskController::class, 'index']);
    Route::patch('/tasks/{task}/done', [TaskController::class, 'markDone']);

    Route::get('/my-professionals', [LinkController::class, 'indexProfessionals']);

    // Credencial do profissional (self). Só role:pro — o pro ainda NÃO
    // aprovado precisa acessar para submeter e acompanhar o status.
    Route::middleware('role:pro')->group(function () {
        Route::get('/credentials/me', [CredentialController::class, 'me']);
        Route::post('/credentials', [CredentialController::class, 'store'])
            ->middleware('throttle:10,1');
        Route::put('/credentials', [CredentialController::class, 'resubmit'])
            ->middleware('throttle:10,1');
    });

    // Admin: validação de credenciais (Fase 5c).
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/credentials', [AdminCredentialController::class, 'index']);
        Route::get('/credentials/{credential}', [AdminCredentialController::class, 'show']);
        Route::post('/credentials/{credential}/approve', [AdminCredentialController::class, 'approve']);
        Route::post('/credentials/{credential}/reject', [AdminCredentialController::class, 'reject']);
    });

    // Rotas clínicas: exigem role:pro E credencial aprovada (pro.verified).
    Route::middleware(['role:pro', 'pro.verified'])->group(function () {
        Route::post('/links', [LinkController::class, 'store']);
        Route::get('/patients', [LinkController::class, 'indexPatients']);
        Route::delete('/links/{patientId}', [LinkController::class, 'destroy']);
        Route::get('/patients/search', [LinkController::class, 'searchPatient'])
            ->middleware('throttle:10,1');
        Route::post('/tasks', [TaskController::class, 'store']);
        Route::get('/patients/{id}/summary', [PatientController::class, 'summary']);
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
    });
});


Route::get('/admin/credential-documents/{document}', [AdminCredentialController::class, 'document'])
    ->middleware('signed')
    ->name('admin.credential-document');