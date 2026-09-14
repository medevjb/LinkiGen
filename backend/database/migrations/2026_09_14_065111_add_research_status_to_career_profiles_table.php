<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('career_profiles', function (Blueprint $table) {
            $table->string('research_status')->default('idle')->after('linkedin_url_claim_status'); // idle|running|success|partial|no_data|failed
            $table->string('research_error_code')->nullable()->after('research_status');
        });
    }

    public function down(): void
    {
        Schema::table('career_profiles', function (Blueprint $table) {
            $table->dropColumn(['research_status', 'research_error_code']);
        });
    }
};
