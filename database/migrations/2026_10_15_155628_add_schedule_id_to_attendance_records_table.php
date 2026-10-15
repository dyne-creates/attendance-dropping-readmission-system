<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table): void {
            // Nullable so existing records (recorded before per-slot tracking) are not broken.
            $table->unsignedBigInteger('schedule_id')->nullable()->after('enrollment_id');

            $table->foreign('schedule_id')
                ->references('schedule_id')
                ->on('course_schedules')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table): void {
            $table->dropForeign(['schedule_id']);
            $table->dropColumn('schedule_id');
        });
    }
};
