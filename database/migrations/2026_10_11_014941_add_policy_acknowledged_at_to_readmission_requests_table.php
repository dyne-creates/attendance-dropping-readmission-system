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
        Schema::table('readmission_requests', function (Blueprint $table) {
            $table->timestamp('policy_acknowledged_at')->nullable()->after('reason_details');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('readmission_requests', function (Blueprint $table) {
            $table->dropColumn('policy_acknowledged_at');
        });
    }
};
