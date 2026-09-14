<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority')->default('medium');
            $table->date('scheduled_for')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->unsignedSmallInteger('estimated_minutes')->nullable();
            $table->string('automation_level')->default('assistant');
            $table->boolean('approval_required')->default(false);
            $table->string('status')->default('to_do'); // to_do|in_progress|completed|skipped
            $table->string('fingerprint')->nullable()->index();
            $table->text('rationale')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
