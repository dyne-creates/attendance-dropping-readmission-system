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
        Schema::table('readmission_requests', function (Blueprint $table): void {
            $table->unsignedInteger('attempt_number')->default(1)->after('drop_id');
            $table->text('rejection_reason')->nullable()->after('reason_details');
            $table->dropUnique('readmission_requests_drop_id_unique');
            $table->unique(
                ['drop_id', 'attempt_number'],
                'readmission_requests_drop_attempt_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasMultipleAttempts = DB::table('readmission_requests')
            ->select('drop_id')
            ->groupBy('drop_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasMultipleAttempts) {
            throw new RuntimeException('Readmission attempts exist. Preserve them and roll forward instead of rolling back this migration.');
        }

        if (DB::table('readmission_requests')->whereNotNull('rejection_reason')->exists()) {
            throw new RuntimeException('Readmission rejection reasons exist. Preserve them and roll forward instead of rolling back this migration.');
        }

        Schema::table('readmission_requests', function (Blueprint $table): void {
            $table->dropUnique('readmission_requests_drop_attempt_unique');
            $table->unique('drop_id');
            $table->dropColumn(['attempt_number', 'rejection_reason']);
        });
    }
};
