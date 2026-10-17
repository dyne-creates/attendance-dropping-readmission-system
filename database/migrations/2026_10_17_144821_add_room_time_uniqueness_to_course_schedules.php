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
        $schedules = DB::table('course_schedules')
            ->select(['schedule_id', 'day_of_week', 'start_time', 'end_time', 'room'])
            ->whereNotNull('room')
            ->get();
        $roomReservations = [];

        foreach ($schedules as $schedule) {
            $room = trim($schedule->room);

            if ($room === '') {
                continue;
            }

            $roomKey = mb_strtolower($room);
            $reservationKey = serialize([
                $schedule->day_of_week,
                $schedule->start_time,
                $schedule->end_time,
                $roomKey,
            ]);

            $roomReservations[$reservationKey] ??= [
                'count' => 0,
                'description' => "{$roomKey} on {$schedule->day_of_week} {$schedule->start_time}-{$schedule->end_time}",
            ];
            $roomReservations[$reservationKey]['count']++;
        }

        $conflicts = array_values(array_filter(
            $roomReservations,
            fn (array $reservation): bool => $reservation['count'] > 1,
        ));

        if ($conflicts !== []) {
            $conflictList = implode(', ', array_column($conflicts, 'description'));

            throw new RuntimeException("Resolve existing classroom schedule conflicts before migrating: {$conflictList}");
        }

        Schema::table('course_schedules', function (Blueprint $table): void {
            $table->string('room_key', 50)->nullable()->after('room');
        });

        foreach ($schedules as $schedule) {
            $room = trim($schedule->room);

            DB::table('course_schedules')
                ->where('schedule_id', $schedule->schedule_id)
                ->update([
                    'room' => $room === '' ? null : $room,
                    'room_key' => $room === '' ? null : mb_strtolower($room),
                ]);
        }

        Schema::table('course_schedules', function (Blueprint $table): void {
            $table->unique(
                ['day_of_week', 'start_time', 'end_time', 'room_key'],
                'course_schedules_day_time_room_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_schedules', function (Blueprint $table): void {
            $table->dropUnique('course_schedules_day_time_room_unique');
            $table->dropColumn('room_key');
        });
    }
};
