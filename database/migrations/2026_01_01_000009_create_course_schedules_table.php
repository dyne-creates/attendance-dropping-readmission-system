<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_schedules', function (Blueprint $table) {
            $table->id('schedule_id');

            $table->unsignedBigInteger('course_id');

            $table->enum('day_of_week', [
                'Mon',
                'Tue',
                'Wed',
                'Thu',
                'Fri',
                'Sat',
                'Sun',
            ]);

            $table->time('start_time');
            $table->time('end_time');
            $table->string('room', 50);

            $table->foreign('course_id')
                ->references('course_id')
                ->on('courses');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_schedules');
    }
};
