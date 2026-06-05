<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $query = Task::with('project')->where('assignee_user_id', $userId)->whereNotNull('due_date');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('due_date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('label')) {
            $query->where('label', $request->label);
        }

        $tasks = $query->orderBy('due_date', 'asc')->get();

        return response()->json([
            'meta' => ['code' => 200, 'status' => 'success', 'message' => 'Calendar tasks fetched successfully.'],
            'data' => [
                'tasks' => $tasks
            ]
        ]);
    }
}
