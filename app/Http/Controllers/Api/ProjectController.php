<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectLink;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    private function successResponse(string $message, mixed $data = [], int $code = 200): JsonResponse
    {
        return response()->json([
            'meta' => ['code' => $code, 'status' => 'success', 'message' => $message],
            'data' => $data,
        ], $code);
    }

    private function errorResponse(string $message, mixed $errors = [], int $code = 400): JsonResponse
    {
        return response()->json([
            'meta' => ['code' => $code, 'status' => 'error', 'message' => $message],
            'data' => $errors,
        ], $code);
    }

    public function index(): JsonResponse
    {
        $projects = Project::whereHas('members', function($q) {
            $q->where('user_id', auth('api')->id());
        })->with(['users', 'links'])->orderBy('created_at', 'desc')->get();

        return $this->successResponse('Projects fetched successfully.', ['projects' => $projects]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline_date' => 'nullable|date|date_format:Y-m-d',
            'links' => 'nullable|array',
            'links.*.label' => 'nullable|string',
            'links.*.url' => 'required|url',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi gagal.', $validator->errors(), 422);
        }

        $project = Project::create([
            'name' => $request->name,
            'description' => $request->description,
            'deadline_date' => $request->deadline_date,
            'created_by_user_id' => auth('api')->id(),
        ]);

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => auth('api')->id(),
            'role' => 'owner',
        ]);

        if ($request->has('links') && is_array($request->links)) {
            foreach ($request->links as $index => $link) {
                ProjectLink::create([
                    'project_id' => $project->id,
                    'label' => $link['label'] ?? null,
                    'url' => $link['url'],
                    'sort_order' => $index + 1,
                ]);
            }
        }

        return $this->successResponse('Project berhasil dibuat.', ['project' => $project->load(['users', 'links'])], 201);
    }

    public function show(string $id): JsonResponse
    {
        $project = Project::with(['users', 'links', 'tasks.assignee'])->find($id);

        if (!$project) {
            return $this->errorResponse('Project tidak ditemukan.', [], 404);
        }

        // Check if user is member
        if (!$project->members()->where('user_id', auth('api')->id())->exists()) {
            return $this->errorResponse('Unauthorized access to project.', [], 403);
        }

        return $this->successResponse('Project fetched successfully.', ['project' => $project]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $project = Project::find($id);

        if (!$project) return $this->errorResponse('Project tidak ditemukan.', [], 404);

        $member = $project->members()->where('user_id', auth('api')->id())->first();
        if (!$member || $member->role !== 'owner') {
            return $this->errorResponse('Hanya owner yang dapat mengedit project.', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'deadline_date' => 'nullable|date|date_format:Y-m-d',
            'links' => 'nullable|array',
            'links.*.label' => 'nullable|string',
            'links.*.url' => 'required|url',
        ]);

        if ($validator->fails()) return $this->errorResponse('Validasi gagal.', $validator->errors(), 422);

        $project->update($request->only(['name', 'description', 'deadline_date']));

        if ($request->has('links')) {
            $project->links()->delete();
            if (is_array($request->links)) {
                foreach ($request->links as $index => $link) {
                    ProjectLink::create([
                        'project_id' => $project->id,
                        'label' => $link['label'] ?? null,
                        'url' => $link['url'],
                        'sort_order' => $index + 1,
                    ]);
                }
            }
        }

        return $this->successResponse('Project berhasil diperbarui.', ['project' => $project->fresh(['users', 'links'])]);
    }

    public function destroy(string $id): JsonResponse
    {
        $project = Project::find($id);

        if (!$project) return $this->errorResponse('Project tidak ditemukan.', [], 404);

        $member = $project->members()->where('user_id', auth('api')->id())->first();
        if (!$member || $member->role !== 'owner') {
            return $this->errorResponse('Hanya owner yang dapat menghapus project.', [], 403);
        }

        $project->delete();
        return $this->successResponse('Project berhasil dihapus.');
    }

    public function addMember(Request $request, string $id): JsonResponse
    {
        $project = Project::find($id);
        if (!$project) return $this->errorResponse('Project tidak ditemukan.', [], 404);

        $authMember = $project->members()->where('user_id', auth('api')->id())->first();
        if (!$authMember || !in_array($authMember->role, ['owner', 'admin'])) {
            return $this->errorResponse('Hanya owner atau admin yang dapat menambah member.', [], 403);
        }

        $payload = $request->only(['slug', 'role']);

        $validator = Validator::make($payload, [
            'slug' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (!User::findBySlug($value)) {
                        $fail('User dengan slug tersebut tidak ditemukan.');
                    }
                },
            ],
            'role' => 'required|in:admin,member',
        ]);

        if ($validator->fails()) return $this->errorResponse('Validasi gagal.', $validator->errors(), 422);

        $userToAdd = User::findBySlug($payload['slug']);

        if ($project->members()->where('user_id', $userToAdd->id)->exists()) {
            return $this->errorResponse('User sudah ada di dalam project.', [], 400);
        }

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $userToAdd->id,
            'role' => $payload['role'],
        ]);

        return $this->successResponse('Member berhasil ditambahkan.', ['project' => $project->fresh(['users'])]);
    }

    public function removeMember(string $id, string $userId): JsonResponse
    {
        $project = Project::find($id);
        if (!$project) return $this->errorResponse('Project tidak ditemukan.', [], 404);

        $authMember = $project->members()->where('user_id', auth('api')->id())->first();
        if (!$authMember || !in_array($authMember->role, ['owner', 'admin'])) {
            return $this->errorResponse('Hanya owner atau admin yang dapat menghapus member.', [], 403);
        }

        $targetMember = $project->members()->where('user_id', $userId)->first();
        if (!$targetMember) return $this->errorResponse('Member tidak ditemukan dalam project.', [], 404);

        if ($targetMember->role === 'owner') {
            return $this->errorResponse('Owner tidak dapat dihapus dari project.', [], 400);
        }

        $targetMember->delete();
        return $this->successResponse('Member berhasil dihapus.');
    }
}
