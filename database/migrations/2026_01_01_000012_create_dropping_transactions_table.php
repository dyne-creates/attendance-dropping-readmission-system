<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dropping_transactions', function (Blueprint $table) {
            $table->id('drop_id');

            $table->unsignedBigInteger('enrollment_id');
            $table->unsignedBigInteger('dropped_by');

            $table->decimal('absence_hours_at_drop', 6, 2);

            $table->integer('sequence_in_cycle');

            $table->date('drop_date');

            $table->timestamps();

            $table->unique([
                'enrollment_id',
                'sequence_in_cycle',
            ]);

            $table->foreign('enrollment_id')
                ->references('enrollment_id')
                ->on('enrollments');

            $table->foreign('dropped_by')
                ->references('faculty_id')
                ->on('faculty_members');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dropping_transactions');
    }
};
