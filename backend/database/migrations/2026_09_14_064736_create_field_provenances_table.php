<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_provenances', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('field_name');
            $table->string('authority'); // user|cv|research|inference
            $table->string('confidence')->nullable(); // high|medium|low|unknown
            $table->foreignId('source_id')->nullable()->constrained('career_profile_sources')->nullOnDelete();
            $table->json('original_value_json')->nullable();
            $table->timestamp('set_at');
            $table->timestamps();

            $table->index(['entity_type', 'entity_id', 'field_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_provenances');
    }
};
