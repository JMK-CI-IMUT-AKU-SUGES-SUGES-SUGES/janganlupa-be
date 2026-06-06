<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Project;
use App\Models\PartnerRelation;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $userId = auth('api')->id();
        $now = Carbon::now();

        // All user tasks (for workload chart, calendar etc)
        $tasks = Task::with(['links', 'assignee:id,name', 'project:id,name'])
            ->where('assignee_user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Task counts
        $activeTasks = $tasks->whereIn('status', ['belum', 'berjalan'])->count();
        $totalTasks = $tasks->count();
        $completedTasks = $tasks->where('status', 'selesai')->count();

        // Deadline dekat (dalam 7 hari)
        $upcomingDeadlines = $tasks
            ->whereIn('status', ['belum', 'berjalan'])
            ->whereNotNull('due_date')
            ->where('due_date', '>=', $now->format('Y-m-d'))
            ->where('due_date', '<=', $now->copy()->addDays(7)->format('Y-m-d'))
            ->sortBy('due_date')
            ->values();

        // Project fokus (berjalan)
        $focusedProjects = Project::with('users')
            ->whereHas('members', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })->withCount(['tasks as active_tasks_count' => function($q) {
                $q->whereIn('status', ['belum', 'berjalan']);
            }])->orderBy('deadline_date', 'asc')->get();

        // Partner yang perlu ditindak (incoming pending requests)
        $pendingPartners = PartnerRelation::with('requester')
            ->where('receiver_user_id', $userId)
            ->where('status', 'pending')
            ->get();

        // Connected partners count
        $connectedPartners = PartnerRelation::where('status', 'accepted')
            ->where(function ($q) use ($userId) {
                $q->where('requester_user_id', $userId)
                  ->orWhere('receiver_user_id', $userId);
            })->count();

        // Grafik progres (jumlah task selesai per hari selama 7 hari terakhir)
        $progressChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $count = Task::where('assignee_user_id', $userId)
                ->where('status', 'selesai')
                ->whereDate('updated_at', $date)
                ->count();
            $progressChart[] = [
                'date' => $date,
                'completed' => $count
            ];
        }

        return response()->json([
            'meta' => ['code' => 200, 'status' => 'success', 'message' => 'Dashboard data fetched successfully.'],
            'data' => [
                'tasks' => $tasks,
                'active_tasks_count' => $activeTasks,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'upcoming_deadlines' => $upcomingDeadlines,
                'focused_projects' => $focusedProjects,
                'pending_partners' => $pendingPartners,
                'connected_partners_count' => $connectedPartners,
                'progress_chart' => $progressChart,
            ]
        ]);
    }
}
