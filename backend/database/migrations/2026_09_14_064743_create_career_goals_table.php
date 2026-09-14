<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('primary_goal'); // grow_linkedin|find_job|both
            $table->json('target_roles_json')->nullable();
            $table->json('locations_json')->nullable();
            $table->string('remote_preference')->nullable();
            $table->string('career_direction')->nullable(); // ic|leadership|freelance|not_sure
            $table->unsignedInteger('salary_min')->nullable();
            $table->string('salary_currency', 3)->nullable();
            $table->json('industries_json')->nullable();
            $table->json('employment_types_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_goals');
    }
};
