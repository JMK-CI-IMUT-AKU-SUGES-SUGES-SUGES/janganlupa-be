<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_owner_can_reassign_project_task_to_another_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $newAssignee = User::factory()->create();

        $project = Project::create([
            'name' => 'PBL Web',
            'description' => 'Project kolaborasi',
            'created_by_user_id' => $owner->id,
        ]);

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $newAssignee->id,
            'role' => 'member',
        ]);

        $task = Task::create([
            'project_id' => $project->id,
            'assignee_user_id' => $member->id,
            'created_by_user_id' => $owner->id,
            'title' => 'Implement login',
            'status' => 'belum',
            'priority' => 'sedang',
            'progress' => 0,
        ]);

        $response = $this
            ->actingAs($owner, 'api')
            ->putJson("/api/tasks/{$task->id}", [
                'title' => 'Implement login v2',
                'assignee_user_id' => $newAssignee->id,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.task.assignee_user_id', $newAssignee->id);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'assignee_user_id' => $newAssignee->id,
            'title' => 'Implement login v2',
        ]);
    }
}
