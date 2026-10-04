<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('readmission_requests', function (Blueprint $table) {
            $table->id('readmission_id');

            $table->unsignedBigInteger('drop_id')->unique();
            $table->unsignedBigInteger('processed_by')->nullable();

            $table->string('reason_code', 50);
            $table->text('reason_details')->nullable();

            $table->enum('channel', [
                'online',
                'f2f',
            ]);

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
            ])->default('pending');

            $table->date('notice_sent_date')->nullable();
            $table->date('processed_date')->nullable();

            $table->timestamps();

            $table->foreign('drop_id')
                ->references('drop_id')
                ->on('dropping_transactions');

            $table->foreign('processed_by')
                ->references('osa_id')
                ->on('osa_staff');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('readmission_requests');
    }
};
