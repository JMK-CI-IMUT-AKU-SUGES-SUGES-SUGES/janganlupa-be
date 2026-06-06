<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\PartnerRelationController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\CalendarController;

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
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // ── Task CRUD ───────────────────────────────────────────
    Route::get('/tasks',                    [TaskController::class, 'index']);
    Route::post('/tasks',                   [TaskController::class, 'store']);
    Route::get('/tasks/{id}',               [TaskController::class, 'show']);
    Route::put('/tasks/{id}',               [TaskController::class, 'update']);
    Route::patch('/tasks/{id}/status',      [TaskController::class, 'updateStatus']);
    Route::delete('/tasks/{id}',            [TaskController::class, 'destroy']);

    // ── Project CRUD ────────────────────────────────────────
    Route::get('/projects',                 [ProjectController::class, 'index']);
    Route::post('/projects',                [ProjectController::class, 'store']);
    Route::get('/projects/{id}',            [ProjectController::class, 'show']);
    Route::put('/projects/{id}',            [ProjectController::class, 'update']);
    Route::delete('/projects/{id}',         [ProjectController::class, 'destroy']);
    Route::post('/projects/{id}/members',   [ProjectController::class, 'addMember']);
    Route::delete('/projects/{id}/members/{user_id}', [ProjectController::class, 'removeMember']);

    // ── Partner Relations ───────────────────────────────────
    Route::get('/partners',                       [PartnerRelationController::class, 'index']);
    Route::get('/partners/requests',              [PartnerRelationController::class, 'requests']);
    Route::post('/partners/request',              [PartnerRelationController::class, 'sendRequest']);
    Route::put('/partners/requests/{id}',         [PartnerRelationController::class, 'respondRequest']);
    Route::delete('/partners/{id}',               [PartnerRelationController::class, 'destroy']);

    // ── Aggregation ─────────────────────────────────────────
    Route::get('/dashboard',                      [DashboardController::class, 'index']);
    Route::get('/calendar',                       [CalendarController::class, 'index']);

});
