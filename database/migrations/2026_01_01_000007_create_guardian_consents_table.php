<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardian_consents', function (Blueprint $table) {
            $table->id('consent_id');

            $table->unsignedBigInteger('guardian_id');

            $table->enum('consent_type', [
                'share_contact_number',
                'share_dropping_details',
            ]);

            $table->string('consent_form_path', 255)->nullable();
            $table->timestamp('consented_at');
            $table->string('status', 50);

            $table->foreign('guardian_id')
                ->references('guardian_id')
                ->on('guardians');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_consents');
    }
};
