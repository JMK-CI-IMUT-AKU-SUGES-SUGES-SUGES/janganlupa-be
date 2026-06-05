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
        $userId = auth()->id();
        $now = Carbon::now();

        // Task aktif
        $activeTasks = Task::where('assignee_user_id', $userId)
            ->whereIn('status', ['belum', 'berjalan'])
            ->count();

        // Deadline dekat (dalam 7 hari)
        $upcomingDeadlines = Task::where('assignee_user_id', $userId)
            ->whereIn('status', ['belum', 'berjalan'])
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$now->format('Y-m-d'), $now->copy()->addDays(7)->format('Y-m-d')])
            ->orderBy('due_date', 'asc')
            ->get();

        // Project fokus (berjalan)
        $focusedProjects = Project::whereHas('members', function($q) use ($userId) {
            $q->where('user_id', $userId);
        })->withCount(['tasks as active_tasks_count' => function($q) {
            $q->whereIn('status', ['belum', 'berjalan']);
        }])->orderBy('deadline_date', 'asc')->get();

        // Partner yang perlu ditindak (incoming pending requests)
        $pendingPartners = PartnerRelation::with('requester')
            ->where('receiver_user_id', $userId)
            ->where('status', 'pending')
            ->get();

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
                'active_tasks_count' => $activeTasks,
                'upcoming_deadlines' => $upcomingDeadlines,
                'focused_projects' => $focusedProjects,
                'pending_partners' => $pendingPartners,
                'progress_chart' => $progressChart,
            ]
        ]);
    }
}
