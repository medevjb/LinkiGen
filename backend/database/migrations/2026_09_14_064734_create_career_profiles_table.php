<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('linkedin_url')->nullable();
            $table->string('linkedin_url_claim_status')->default('unclaimed'); // unclaimed|pending|confirmed
            $table->string('headline')->nullable();
            $table->string('location')->nullable();
            $table->string('current_role')->nullable();
            $table->string('current_company')->nullable();
            $table->text('professional_summary')->nullable();
            $table->text('professional_focus')->nullable();
            $table->unsignedTinyInteger('completeness_score')->default(0);
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('last_researched_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_profiles');
    }
};
