<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Candidates sign in with their NECTA index number as the username, so a
     * dedicated column is added. Existing applicants are backfilled from the
     * exam registration number stored on their most recent application.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 60)->nullable()->unique()->after('name');
            $table->string('name')->nullable()->change();
            $table->string('email')->nullable()->change();
        });

        $rows = DB::table('applications')
            ->join('students', 'students.id', '=', 'applications.student_id')
            ->whereNotNull('students.exam_reg_number')
            ->orderBy('applications.submitted_at', 'desc')
            ->get(['applications.user_id', 'students.exam_reg_number']);

        $backfilled = [];

        foreach ($rows as $row) {
            $userId = (int) $row->user_id;

            if (isset($backfilled[$userId])) {
                continue;
            }

            $backfilled[$userId] = true;

            DB::table('users')
                ->where('id', $userId)
                ->whereNull('username')
                ->update(['username' => strtoupper(trim((string) $row->exam_reg_number))]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
