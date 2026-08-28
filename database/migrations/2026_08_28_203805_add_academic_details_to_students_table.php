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
        Schema::table('students', function (Blueprint $table) {
            $table->string('exam_type', 20)->nullable()->after('previous_marks');
            $table->string('exam_reg_number', 40)->nullable()->after('exam_type');
            $table->unsignedSmallInteger('exam_year')->nullable()->after('exam_reg_number');
            $table->boolean('exam_confirmed')->default(false)->after('exam_year');
            $table->json('exam_result')->nullable()->after('exam_confirmed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['exam_type', 'exam_reg_number', 'exam_year', 'exam_confirmed', 'exam_result']);
        });
    }
};
