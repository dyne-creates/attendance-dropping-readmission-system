<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('excuse_documents', function (Blueprint $table) {
            $table->id('document_id');

            $table->unsignedBigInteger('readmission_id');

            $table->string('document_type', 50);
            $table->string('file_path', 255);

            $table->timestamps();

            $table->foreign('readmission_id')
                ->references('readmission_id')
                ->on('readmission_requests');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('excuse_documents');
    }
};
