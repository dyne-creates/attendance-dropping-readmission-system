<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (DB::table('course_schedules')->select(['schedule_id', 'start_time', 'end_time'])->get() as $schedule) {
                DB::table('course_schedules')
                    ->where('schedule_id', $schedule->schedule_id)
                    ->update([
                        'start_time' => Carbon::parse($schedule->start_time)->format('H:i:s'),
                        'end_time' => Carbon::parse($schedule->end_time)->format('H:i:s'),
                    ]);
            }

            $duplicateGroups = DB::table('course_schedules')
                ->select([
                    'course_id',
                    'day_of_week',
                    'start_time',
                    'end_time',
                    DB::raw('MIN(schedule_id) as canonical_schedule_id'),
                ])
                ->groupBy('course_id', 'day_of_week', 'start_time', 'end_time')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($duplicateGroups as $duplicateGroup) {
                $duplicateIds = DB::table('course_schedules')
                    ->where('course_id', $duplicateGroup->course_id)
                    ->where('day_of_week', $duplicateGroup->day_of_week)
                    ->where('start_time', $duplicateGroup->start_time)
                    ->where('end_time', $duplicateGroup->end_time)
                    ->where('schedule_id', '!=', $duplicateGroup->canonical_schedule_id)
                    ->pluck('schedule_id');

                DB::table('attendance_records')
                    ->whereIn('schedule_id', $duplicateIds)
                    ->update(['schedule_id' => $duplicateGroup->canonical_schedule_id]);

                DB::table('course_schedules')->whereIn('schedule_id', $duplicateIds)->delete();
            }

            Schema::table('course_schedules', function (Blueprint $table): void {
                $table->unique(
                    ['course_id', 'day_of_week', 'start_time', 'end_time'],
                    'course_schedules_course_day_time_unique',
                );
            });
        });
    }

    public function down(): void
    {
        Schema::table('course_schedules', function (Blueprint $table): void {
            $table->dropUnique('course_schedules_course_day_time_unique');
        });
    }
};
