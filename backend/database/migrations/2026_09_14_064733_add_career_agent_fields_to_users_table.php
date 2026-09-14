<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('timezone')->default('UTC')->after('email');
            $table->string('locale')->default('en')->after('timezone');
            $table->string('onboarding_status')->default('registered')->after('locale');
            $table->timestamp('deletion_requested_at')->nullable()->after('onboarding_status');
            $table->timestamp('erasure_completed_at')->nullable()->after('deletion_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['timezone', 'locale', 'onboarding_status', 'deletion_requested_at', 'erasure_completed_at']);
        });
    }
};
