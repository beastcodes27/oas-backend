<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('verification_status', 20)->default('pending')->index()->after('status');
            $table->string('necta_division', 10)->nullable()->after('verification_status');
            $table->string('necta_matched_name')->nullable()->after('necta_division');
            $table->timestamp('necta_verified_at')->nullable()->after('necta_matched_name');
            $table->string('verification_error', 500)->nullable()->after('necta_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'verification_status',
                'necta_division',
                'necta_matched_name',
                'necta_verified_at',
                'verification_error',
            ]);
        });
    }
};
