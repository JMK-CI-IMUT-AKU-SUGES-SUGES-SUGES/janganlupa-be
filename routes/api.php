<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TaskController;   // ← Tambah import ini

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);


// ═══════════════════════════════════════════════════════════
// PROTECTED ROUTES — Wajib ada token JWT (Bearer Token)
// Middleware 'auth:api' menggunakan driver JWT dari config/auth.php
// ═══════════════════════════════════════════════════════════

Route::middleware('auth:api')->group(function () {

    // ── Auth ────────────────────────────────────────────────
    Route::get('/me',      [UserController::class, 'me']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ── Task CRUD ───────────────────────────────────────────
    Route::get('/tasks',                    [TaskController::class, 'index']);
    Route::post('/tasks',                   [TaskController::class, 'store']);
    Route::get('/tasks/{id}',               [TaskController::class, 'show']);
    Route::put('/tasks/{id}',               [TaskController::class, 'update']);
    Route::patch('/tasks/{id}/status',      [TaskController::class, 'updateStatus']);
    Route::delete('/tasks/{id}',            [TaskController::class, 'destroy']);

    // ── Project CRUD ────────────────────────────────────────
    Route::get('/projects',                 [\App\Http\Controllers\Api\ProjectController::class, 'index']);
    Route::post('/projects',                [\App\Http\Controllers\Api\ProjectController::class, 'store']);
    Route::get('/projects/{id}',            [\App\Http\Controllers\Api\ProjectController::class, 'show']);
    Route::put('/projects/{id}',            [\App\Http\Controllers\Api\ProjectController::class, 'update']);
    Route::delete('/projects/{id}',         [\App\Http\Controllers\Api\ProjectController::class, 'destroy']);
    Route::post('/projects/{id}/members',   [\App\Http\Controllers\Api\ProjectController::class, 'addMember']);
    Route::delete('/projects/{id}/members/{user_id}', [\App\Http\Controllers\Api\ProjectController::class, 'removeMember']);

    // ── Partner Relations ───────────────────────────────────
    Route::get('/partners',                       [\App\Http\Controllers\Api\PartnerRelationController::class, 'index']);
    Route::get('/partners/requests',              [\App\Http\Controllers\Api\PartnerRelationController::class, 'requests']);
    Route::post('/partners/request',              [\App\Http\Controllers\Api\PartnerRelationController::class, 'sendRequest']);
    Route::put('/partners/requests/{id}',         [\App\Http\Controllers\Api\PartnerRelationController::class, 'respondRequest']);
    Route::delete('/partners/{id}',               [\App\Http\Controllers\Api\PartnerRelationController::class, 'destroy']);

    // ── Aggregation ─────────────────────────────────────────
    Route::get('/dashboard',                      [\App\Http\Controllers\Api\DashboardController::class, 'index']);
    Route::get('/calendar',                       [\App\Http\Controllers\Api\CalendarController::class, 'index']);

});
