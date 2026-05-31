<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    // ──────────────────────────────────────────────────────────────────
    // HELPER — Format respons JSON yang konsisten (sama seperti Auth)
    // ──────────────────────────────────────────────────────────────────

    private function successResponse(string $message, mixed $data = [], int $code = 200): JsonResponse
    {
        return response()->json([
            'meta' => [
                'code'    => $code,
                'status'  => 'success',
                'message' => $message,
            ],
            'data' => $data,
        ], $code);
    }

    private function errorResponse(string $message, mixed $errors = [], int $code = 400): JsonResponse
    {
        return response()->json([
            'meta' => [
                'code'    => $code,
                'status'  => 'error',
                'message' => $message,
            ],
            'data' => $errors,
        ], $code);
    }

    // ──────────────────────────────────────────────────────────────────
    // READ — GET /api/tasks
    // Ambil semua task milik user yang sedang login
    // Query params: ?search=, ?status=, ?label=
    // ──────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $query = Task::where('user_id', auth()->id());

        // Filter: carian teks pada tajuk / deskripsi
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter: status ('Belum Selesai' | 'Berjalan' | 'Selesai')
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter: label / mata kuliah
        if ($request->filled('label')) {
            $query->where('label', $request->label);
        }

        // Urutkan: task terbaru di atas
        $tasks = $query->orderBy('created_at', 'desc')->get();

        // Ringkasan statistik untuk dashboard
        $summary = [
            'total'         => $tasks->count(),
            'belum_selesai' => $tasks->where('status', 'Belum Selesai')->count(),
            'berjalan'      => $tasks->where('status', 'Berjalan')->count(),
            'selesai'       => $tasks->where('status', 'Selesai')->count(),
        ];

        return $this->successResponse('Tasks fetched successfully.', [
            'summary' => $summary,
            'tasks'   => $tasks,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    // READ — GET /api/tasks/{id}
    // Ambil detail satu task (hanya milik user sendiri)
    // ──────────────────────────────────────────────────────────────────

    public function show(int $id): JsonResponse
    {
        $task = Task::where('id', $id)
                    ->where('user_id', auth()->id())
                    ->first();

        if (!$task) {
            return $this->errorResponse('Task tidak ditemukan.', [], 404);
        }

        return $this->successResponse('Task fetched successfully.', ['task' => $task]);
    }

    // ──────────────────────────────────────────────────────────────────
    // CREATE — POST /api/tasks
    // Tambah task baru
    // ──────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'nullable|in:Belum Selesai,Berjalan,Selesai',
            'label'       => 'nullable|string|max:100',
            'due_date'    => 'nullable|date|date_format:Y-m-d',
            'progress'    => 'nullable|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi gagal.', $validator->errors(), 422);
        }

        $task = Task::create([
            'user_id'     => auth()->id(),
            'title'       => $request->title,
            'description' => $request->description,
            'status'      => $request->status ?? 'Belum Selesai',
            'label'       => $request->label,
            'due_date'    => $request->due_date,
            'progress'    => $request->progress ?? 0,
        ]);

        return $this->successResponse('Task berhasil ditambahkan.', ['task' => $task], 201);
    }

    // ──────────────────────────────────────────────────────────────────
    // UPDATE (Full Edit) — PUT /api/tasks/{id}
    // Edit semua butiran task
    // ──────────────────────────────────────────────────────────────────

    public function update(Request $request, int $id): JsonResponse
    {
        $task = Task::where('id', $id)
                    ->where('user_id', auth()->id())
                    ->first();

        if (!$task) {
            return $this->errorResponse('Task tidak ditemukan.', [], 404);
        }

        $validator = Validator::make($request->all(), [
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'nullable|in:Belum Selesai,Berjalan,Selesai',
            'label'       => 'nullable|string|max:100',
            'due_date'    => 'nullable|date|date_format:Y-m-d',
            'progress'    => 'nullable|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi gagal.', $validator->errors(), 422);
        }

        $task->update($request->only([
            'title', 'description', 'status', 'label', 'due_date', 'progress',
        ]));

        return $this->successResponse('Task berhasil diperbarui.', ['task' => $task->fresh()]);
    }

    // ──────────────────────────────────────────────────────────────────
    // UPDATE STATUS — PATCH /api/tasks/{id}/status
    // Ubah status sahaja: 'Belum Selesai' → 'Berjalan' → 'Selesai'
    // ──────────────────────────────────────────────────────────────────

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $task = Task::where('id', $id)
                    ->where('user_id', auth()->id())
                    ->first();

        if (!$task) {
            return $this->errorResponse('Task tidak ditemukan.', [], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Belum Selesai,Berjalan,Selesai',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi gagal.', $validator->errors(), 422);
        }

        // Auto-set progress berdasarkan status
        $progressMap = [
            'Belum Selesai' => 0,
            'Berjalan'      => 50,
            'Selesai'       => 100,
        ];

        $task->update([
            'status'   => $request->status,
            'progress' => $progressMap[$request->status],
        ]);

        return $this->successResponse(
            "Status task diubah menjadi '{$request->status}'.",
            ['task' => $task->fresh()]
        );
    }

    // ──────────────────────────────────────────────────────────────────
    // DELETE — DELETE /api/tasks/{id}
    // Hapus task
    // ──────────────────────────────────────────────────────────────────

    public function destroy(int $id): JsonResponse
    {
        $task = Task::where('id', $id)
                    ->where('user_id', auth()->id())
                    ->first();

        if (!$task) {
            return $this->errorResponse('Task tidak ditemukan.', [], 404);
        }

        $task->delete();

        return $this->successResponse('Task berhasil dihapus.');
    }
}
