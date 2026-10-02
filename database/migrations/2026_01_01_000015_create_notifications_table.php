<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id('notification_id');

            $table->unsignedBigInteger('readmission_id')->nullable();
            $table->unsignedBigInteger('osa_id')->nullable();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('guardian_id')->nullable();

            $table->string('notification_type', 100);

            $table->enum('channel', [
                'email',
                'sms',
            ]);

            $table->text('message');

            $table->timestamp('sent_at');

            $table->foreign('readmission_id')
                ->references('readmission_id')
                ->on('readmission_requests');

            $table->foreign('osa_id')
                ->references('osa_id')
                ->on('osa_staff');

            $table->foreign('student_id')
                ->references('student_id')
                ->on('students');

            $table->foreign('guardian_id')
                ->references('guardian_id')
                ->on('guardians');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
