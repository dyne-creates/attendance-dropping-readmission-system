<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dropping_transactions', function (Blueprint $table): void {
            $table->string('drop_type', 30)->default('weekly_hours');
        });

        DB::table('dropping_transactions')
            ->join(
                'enrollments',
                'dropping_transactions.enrollment_id',
                '=',
                'enrollments.enrollment_id',
            )
            ->join(
                'courses',
                'enrollments.course_id',
                '=',
                'courses.course_id',
            )
            ->select([
                'dropping_transactions.drop_id',
                'dropping_transactions.absence_hours_at_drop',
                'courses.total_semester_hours',
            ])
            ->orderBy('dropping_transactions.drop_id')
            ->chunk(200, function ($drops): void {
                foreach ($drops as $drop) {
                    if ($drop->absence_hours_at_drop >= (float) $drop->total_semester_hours * 0.20) {
                        DB::table('dropping_transactions')
                            ->where('drop_id', $drop->drop_id)
                            ->update(['drop_type' => 'permanent']);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dropping_transactions', function (Blueprint $table): void {
            $table->dropColumn('drop_type');
        });
    }
};
