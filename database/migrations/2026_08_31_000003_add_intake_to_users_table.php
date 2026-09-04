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
        Schema::table('users', function (Blueprint $table) {
            $table->string('entry_level', 20)->nullable()->after('phone');
            $table->string('exam_type', 10)->nullable()->after('entry_level');
            $table->string('exam_reg_number', 40)->nullable()->after('exam_type');
            $table->unsignedSmallInteger('exam_year')->nullable()->after('exam_reg_number');
            $table->boolean('exam_confirmed')->default(false)->after('exam_year');
            $table->timestamp('exam_confirmed_at')->nullable()->after('exam_confirmed');
            $table->json('exam_result')->nullable()->after('exam_confirmed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'entry_level',
                'exam_type',
                'exam_reg_number',
                'exam_year',
                'exam_confirmed',
                'exam_confirmed_at',
                'exam_result',
            ]);
        });
    }
};
