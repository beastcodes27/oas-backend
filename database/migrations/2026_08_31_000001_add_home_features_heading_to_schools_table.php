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
            $table->string('home_features_label', 120)->nullable()->after('result_links');
            $table->string('home_features_title', 200)->nullable()->after('home_features_label');
            $table->string('home_features_subtitle', 500)->nullable()->after('home_features_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['home_features_label', 'home_features_title', 'home_features_subtitle']);
        });
    }
};
