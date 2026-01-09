<?php

use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\ForgotPasswordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\MaterialController;
use App\Http\Controllers\Api\AssignmentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- PUBLIC ROUTES ---
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendOtp']);
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword']);

// --- PROTECTED ROUTES (Butuh Token) ---
Route::middleware('auth:sanctum')->group(function () {

    // 1. AUTHENTICATION & USER
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // 2. PROFILE MANAGEMENT
    Route::get('/profile', [ProfileController::class, 'index']);
    Route::post('/profile/update', [ProfileController::class, 'update']);

    // 3. CLASSROOMS (KELAS)
    Route::get('/my-classrooms', [ClassroomController::class, 'index']); // List Kelas Saya
    Route::post('/join-class', [ClassroomController::class, 'join']);      // Gabung Kelas via Kode
    Route::get('/classrooms/{id}', [ClassroomController::class, 'show']);  // Detail Kelas
    Route::get('/classrooms/{id}/subjects', [ClassroomController::class, 'subjects']); // List Matkul di Kelas

    // 4. SCHEDULES (JADWAL)
    Route::get('/schedules', [ScheduleController::class, 'index']);

    // 5. MATERIALS (MATERI)
    // Get materi by subject_id (bisa via query param ?subject_id=1 atau path parameter)
    Route::get('/materials', [MaterialController::class, 'index']);
    Route::get('/materials/{id}', [MaterialController::class, 'show']); // Support route lama
    Route::get('/classrooms/{id}/materials', [MaterialController::class, 'byClassroom']); // Materi per Kelas
    Route::get('/materials/{id}/download', [MaterialController::class, 'download']);

    // 6. ASSIGNMENTS (TUGAS)
    // Get tugas by subject_id
    Route::get('/assignments', [AssignmentController::class, 'index']);
    Route::get('/classrooms/{id}/assignments', [AssignmentController::class, 'byClassroom']); // Tugas per Kelas
    Route::get('/assignments/{id}', [AssignmentController::class, 'show']); // Detail Tugas

    // 7. ANNOUNCEMENTS (PENGUMUMAN)
    Route::get('/announcements', [AnnouncementController::class, 'index']);
});
