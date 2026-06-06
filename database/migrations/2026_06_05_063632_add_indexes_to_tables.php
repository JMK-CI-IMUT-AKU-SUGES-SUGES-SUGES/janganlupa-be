<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->index('assignee_user_id');
            $table->index('project_id');
            $table->index('status');
            $table->index('due_date');
            $table->index('created_at');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->index('created_by_user_id');
            $table->index('deadline_date');
        });

        Schema::table('project_members', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('role');
        });

        Schema::table('partner_relations', function (Blueprint $table) {
            $table->index('requester_user_id');
            $table->index('receiver_user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['assignee_user_id']);
            $table->dropIndex(['project_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['due_date']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['created_by_user_id']);
            $table->dropIndex(['deadline_date']);
        });

        Schema::table('project_members', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['role']);
        });

        Schema::table('partner_relations', function (Blueprint $table) {
            $table->dropIndex(['requester_user_id']);
            $table->dropIndex(['receiver_user_id']);
            $table->dropIndex(['status']);
        });
    }
};
