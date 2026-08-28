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
        Schema::table('schools', function (Blueprint $table) {
            $table->boolean('applications_open')->default(true)->after('window');
            $table->timestamp('window_opens_at')->nullable()->after('applications_open');
            $table->timestamp('window_closes_at')->nullable()->after('window_opens_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['applications_open', 'window_opens_at', 'window_closes_at']);
        });
    }
};
