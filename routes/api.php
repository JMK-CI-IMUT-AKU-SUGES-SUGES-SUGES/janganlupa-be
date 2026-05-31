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
    Route::post('/logout', [AuthController::class, 'logout']);

    // ── Task CRUD ───────────────────────────────────────────
    //
    //  GET    /api/tasks                → index()        — Senarai semua task user
    //  POST   /api/tasks                → store()        — Tambah task baru
    //  GET    /api/tasks/{id}           → show()         — Detail satu task
    //  PUT    /api/tasks/{id}           → update()       — Edit penuh task
    //  PATCH  /api/tasks/{id}/status    → updateStatus() — Ubah status sahaja
    //  DELETE /api/tasks/{id}           → destroy()      — Hapus task
    //
    Route::get('/tasks',                    [TaskController::class, 'index']);
    Route::post('/tasks',                   [TaskController::class, 'store']);
    Route::get('/tasks/{id}',               [TaskController::class, 'show']);
    Route::put('/tasks/{id}',               [TaskController::class, 'update']);
    Route::patch('/tasks/{id}/status',      [TaskController::class, 'updateStatus']);
    Route::delete('/tasks/{id}',            [TaskController::class, 'destroy']);

});
