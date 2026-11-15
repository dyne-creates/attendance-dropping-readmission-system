<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guardians', function (Blueprint $table): void {
            $table->text('full_name')->nullable()->change();
            $table->text('relationship')->nullable()->change();
            $table->text('email')->nullable()->change();
            $table->text('phone_number')->nullable()->change();
        });

        $this->encryptColumns('guardians', 'guardian_id', [
            'full_name',
            'relationship',
            'email',
            'phone_number',
        ]);
        $this->encryptColumns('readmission_requests', 'readmission_id', [
            'reason_details',
            'rejection_reason',
        ]);
        $this->encryptColumns('dropping_transactions', 'drop_id', [
            'instructor_reason',
        ]);
        $this->transformDocuments(true);

        $this->redactNotificationReasons();
    }

    public function down(): void
    {
        $this->decryptColumns('guardians', 'guardian_id', [
            'full_name',
            'relationship',
            'email',
            'phone_number',
        ]);
        $this->decryptColumns('readmission_requests', 'readmission_id', [
            'reason_details',
            'rejection_reason',
        ]);
        $this->decryptColumns('dropping_transactions', 'drop_id', [
            'instructor_reason',
        ]);
        $this->transformDocuments(false);
        $this->restoreNotificationReasons();

        Schema::table('guardians', function (Blueprint $table): void {
            $table->string('full_name', 150)->nullable()->change();
            $table->string('relationship', 50)->nullable()->change();
            $table->string('email', 255)->nullable()->change();
            $table->string('phone_number', 20)->nullable()->change();
        });
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function encryptColumns(string $table, string $key, array $columns): void
    {
        DB::table($table)
            ->select([$key, ...$columns])
            ->orderBy($key)
            ->chunkById(100, function ($records) use ($table, $key, $columns): void {
                foreach ($records as $record) {
                    $values = [];

                    foreach ($columns as $column) {
                        $value = $record->{$column};

                        if ($value !== null && ! $this->isEncrypted((string) $value)) {
                            $values[$column] = Crypt::encryptString((string) $value);
                        }
                    }

                    if ($values !== []) {
                        DB::table($table)->where($key, $record->{$key})->update($values);
                    }
                }
            }, $key);
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function decryptColumns(string $table, string $key, array $columns): void
    {
        DB::table($table)
            ->select([$key, ...$columns])
            ->orderBy($key)
            ->chunkById(100, function ($records) use ($table, $key, $columns): void {
                foreach ($records as $record) {
                    $values = [];

                    foreach ($columns as $column) {
                        $value = $record->{$column};

                        if ($value !== null) {
                            try {
                                $values[$column] = Crypt::decryptString((string) $value);
                            } catch (DecryptException $exception) {
                                throw new RuntimeException(
                                    "Could not decrypt {$table}.{$column} during rollback.",
                                    previous: $exception,
                                );
                            }
                        }
                    }

                    if ($values !== []) {
                        DB::table($table)->where($key, $record->{$key})->update($values);
                    }
                }
            }, $key);
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }

    private function transformDocuments(bool $encrypt): void
    {
        DB::table('excuse_documents')
            ->select(['document_id', 'file_path'])
            ->orderBy('document_id')
            ->chunkById(50, function ($documents) use ($encrypt): void {
                foreach ($documents as $document) {
                    if (! Storage::disk('local')->exists($document->file_path)) {
                        throw new RuntimeException(
                            "Supporting document {$document->document_id} is missing from local storage.",
                        );
                    }

                    $contents = Storage::disk('local')->get($document->file_path);
                    $shouldEncrypt = $encrypt && ! $this->isEncrypted($contents);
                    $storedContents = $contents;

                    if ($shouldEncrypt) {
                        $storedContents = Crypt::encryptString($contents);
                    } elseif (! $encrypt) {
                        try {
                            $storedContents = Crypt::decryptString($contents);
                        } catch (DecryptException $exception) {
                            throw new RuntimeException(
                                "Could not decrypt supporting document {$document->document_id} during rollback.",
                                previous: $exception,
                            );
                        }
                    }

                    if ($storedContents !== $contents && ! Storage::disk('local')->put($document->file_path, $storedContents)) {
                        throw new RuntimeException(
                            "Could not update supporting document {$document->document_id} during migration.",
                        );
                    }
                }
            }, 'document_id');
    }

    private function redactNotificationReasons(): void
    {
        DB::table('notifications')
            ->where('notification_type', 'readmission_not_excused')
            ->update([
                'message' => 'Your readmission request was not excused. OSA feedback is available in your readmission record.',
            ]);
        DB::table('notifications')
            ->where('notification_type', 'readmission_rejected')
            ->update([
                'message' => 'Your readmission request was rejected. OSA feedback is available in your readmission record.',
            ]);
    }

    private function restoreNotificationReasons(): void
    {
        DB::table('notifications')
            ->whereIn('notification_type', ['readmission_not_excused', 'readmission_rejected'])
            ->orderBy('notification_id')
            ->chunkById(100, function ($notifications): void {
                foreach ($notifications as $notification) {
                    if ($notification->readmission_id === null) {
                        continue;
                    }

                    $reason = DB::table('readmission_requests')
                        ->where('readmission_id', $notification->readmission_id)
                        ->value('rejection_reason');

                    if ($reason === null) {
                        continue;
                    }

                    $prefix = $notification->notification_type === 'readmission_not_excused'
                        ? 'Your readmission request was not excused. OSA feedback: '
                        : 'Your readmission request has been rejected. OSA feedback: ';

                    DB::table('notifications')
                        ->where('notification_id', $notification->notification_id)
                        ->update(['message' => $prefix.$reason]);
                }
            }, 'notification_id');
    }
};
