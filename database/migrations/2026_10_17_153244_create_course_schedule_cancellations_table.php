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
        Schema::create('course_schedule_cancellations', function (Blueprint $table): void {
            $table->id('cancellation_id');
            $table->unsignedBigInteger('schedule_id');
            $table->date('session_date');
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamps();

            $table->foreign('schedule_id')
                ->references('schedule_id')
                ->on('course_schedules')
                ->cascadeOnDelete();
            $table->foreign('cancelled_by')
                ->references('faculty_id')
                ->on('faculty_members')
                ->nullOnDelete();
            $table->unique(
                ['schedule_id', 'session_date'],
                'course_schedule_cancellations_schedule_date_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_schedule_cancellations');
    }
};
