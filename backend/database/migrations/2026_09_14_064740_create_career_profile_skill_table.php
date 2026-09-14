<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_profile_skill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->string('proficiency')->nullable();
            $table->string('evidence_level')->nullable();
            $table->timestamps();

            $table->unique(['career_profile_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_profile_skill');
    }
};
