<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Project;
use App\Models\TaskLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TaskController extends Controller
{
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

    public function index(Request $request): JsonResponse
    {
        $query = Task::with('links')->where('assignee_user_id', auth()->id());

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('label')) {
            $query->where('label', $request->label);
        }

        $tasks = $query->orderBy('created_at', 'desc')->get();

        $summary = [
            'total'   => $tasks->count(),
            'belum'   => $tasks->where('status', 'belum')->count(),
            'berjalan'=> $tasks->where('status', 'berjalan')->count(),
            'selesai' => $tasks->where('status', 'selesai')->count(),
        ];

        return $this->successResponse('Tasks fetched successfully.', [
            'summary' => $summary,
            'tasks'   => $tasks,
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $task = Task::with('links')->where('id', $id)
                    ->where('assignee_user_id', auth()->id())
                    ->first();

        if (!$task) {
            return $this->errorResponse('Task tidak ditemukan.', [], 404);
        }

        return $this->successResponse('Task fetched successfully.', ['task' => $task]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'project_id'  => 'nullable|uuid|exists:projects,id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'nullable|in:belum,berjalan,selesai',
            'priority'    => 'nullable|in:rendah,sedang,tinggi',
            'label'       => 'nullable|string|max:100',
            'due_date'    => 'nullable|date|date_format:Y-m-d',
            'progress'    => 'nullable|integer|min:0|max:100',
            'links'       => 'nullable|array',
            'links.*.label' => 'nullable|string',
            'links.*.url'   => 'required|url',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi gagal.', $validator->errors(), 422);
        }

        // Validasi deadline project jika project_id != null
        if ($request->project_id && $request->due_date) {
            $project = Project::find($request->project_id);
            if ($project && $project->deadline_date) {
                $projectDeadline = Carbon::parse($project->deadline_date);
                $taskDeadline = Carbon::parse($request->due_date);
                
                // Task deadline cannot exceed H-1 of project deadline
                if ($taskDeadline->greaterThanOrEqualTo($projectDeadline)) {
                    return $this->errorResponse('Validasi gagal.', ['due_date' => ['Task due date must be at least 1 day before the project deadline.']], 422);
                }
            }
        }

        $task = Task::create([
            'project_id'         => $request->project_id,
            'assignee_user_id'   => auth()->id(),
            'created_by_user_id' => auth()->id(),
            'title'              => $request->title,
            'description'        => $request->description,
            'status'             => $request->status ?? 'belum',
            'priority'           => $request->priority ?? 'sedang',
            'label'              => $request->label,
            'due_date'           => $request->due_date,
            'progress'           => $request->progress ?? 0,
        ]);

        if ($request->has('links') && is_array($request->links)) {
            foreach ($request->links as $link) {
                TaskLink::create([
                    'task_id' => $task->id,
                    'label' => $link['label'] ?? null,
                    'url' => $link['url'],
                ]);
            }
        }

        return $this->successResponse('Task berhasil ditambahkan.', ['task' => $task->load('links')], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $task = Task::where('id', $id)
                    ->where('assignee_user_id', auth()->id())
                    ->first();

        if (!$task) {
            return $this->errorResponse('Task tidak ditemukan.', [], 404);
        }

        $validator = Validator::make($request->all(), [
            'project_id'  => 'nullable|uuid|exists:projects,id',
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'nullable|in:belum,berjalan,selesai',
            'priority'    => 'nullable|in:rendah,sedang,tinggi',
            'label'       => 'nullable|string|max:100',
            'due_date'    => 'nullable|date|date_format:Y-m-d',
            'progress'    => 'nullable|integer|min:0|max:100',
            'links'       => 'nullable|array',
            'links.*.label' => 'nullable|string',
            'links.*.url'   => 'required|url',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi gagal.', $validator->errors(), 422);
        }

        $projectId = $request->has('project_id') ? $request->project_id : $task->project_id;
        $dueDate = $request->has('due_date') ? $request->due_date : $task->due_date;

        if ($projectId && $dueDate) {
            $project = Project::find($projectId);
            if ($project && $project->deadline_date) {
                $projectDeadline = Carbon::parse($project->deadline_date);
                $taskDeadline = Carbon::parse($dueDate);
                
                if ($taskDeadline->greaterThanOrEqualTo($projectDeadline)) {
                    return $this->errorResponse('Validasi gagal.', ['due_date' => ['Task due date must be at least 1 day before the project deadline.']], 422);
                }
            }
        }

        $task->update($request->only([
            'project_id', 'title', 'description', 'status', 'priority', 'label', 'due_date', 'progress',
        ]));

        if ($request->has('links')) {
            $task->links()->delete();
            if (is_array($request->links)) {
                foreach ($request->links as $link) {
                    TaskLink::create([
                        'task_id' => $task->id,
                        'label' => $link['label'] ?? null,
                        'url' => $link['url'],
                    ]);
                }
            }
        }

        return $this->successResponse('Task berhasil diperbarui.', ['task' => $task->fresh('links')]);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $task = Task::where('id', $id)
                    ->where('assignee_user_id', auth()->id())
                    ->first();

        if (!$task) {
            return $this->errorResponse('Task tidak ditemukan.', [], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:belum,berjalan,selesai',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi gagal.', $validator->errors(), 422);
        }

        $progressMap = [
            'belum'   => 0,
            'berjalan'=> 50,
            'selesai' => 100,
        ];

        $task->update([
            'status'   => $request->status,
            'progress' => $progressMap[$request->status],
        ]);

        return $this->successResponse(
            "Status task diubah menjadi '{$request->status}'.",
            ['task' => $task->fresh('links')]
        );
    }

    public function destroy(string $id): JsonResponse
    {
        $task = Task::where('id', $id)
                    ->where('assignee_user_id', auth()->id())
                    ->first();

        if (!$task) {
            return $this->errorResponse('Task tidak ditemukan.', [], 404);
        }

        $task->delete();

        return $this->successResponse('Task berhasil dihapus.');
    }
}
