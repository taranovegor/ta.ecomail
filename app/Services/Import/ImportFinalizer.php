<?php

namespace App\Services\Import;

use App\Enums\ImportChunkStatus;
use App\Enums\ImportStatus;
use App\Models\Contact;
use App\Models\Import;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;

class ImportFinalizer
{
    public function finalize(Import $import, Filesystem $fs): void
    {
        /** @var object{
         *     total_records:int|null,
         *     imported:int|null,
         *     duplicates:int|null,
         *     invalid:int|null,
         *     failed_chunks:int|null,
         *     total_chunks:int|null
         * } $stats
         */
        $stats = $import->chunks()
            ->selectRaw('SUM(total) as total_records')
            ->selectRaw('SUM(imported) as imported')
            ->selectRaw('SUM(duplicates) as duplicates')
            ->selectRaw('SUM(invalid) as invalid')
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) as failed_chunks', [
                ImportChunkStatus::Failed->value,
            ])
            ->selectRaw('COUNT(*) as total_chunks')
            ->first();

        $failedChunks = (int) $stats->failed_chunks;
        $totalChunks = (int) $stats->total_chunks;

        $status = match (true) {
            $failedChunks === 0 => ImportStatus::Completed,
            $failedChunks < $totalChunks => ImportStatus::CompletedWithErrors,
            default => ImportStatus::Failed,
        };

        $now = now();
        $processingTime = $import->started_at
            ? $import->started_at->diffInMilliseconds($now) / 1000
            : null;

        $import->update([
            'status' => $status,
            'imported_count' => (int) $stats->imported,
            'duplicates_count' => (int) $stats->duplicates,
            'invalid_count' => (int) $stats->invalid,
            'processing_time_seconds' => $processingTime,
            'completed_at' => $now,
        ]);

        // IMHO, it would be better to set up a cron job that would run this command periodically. It shouldn’t be here.
        if ((int) $stats->imported > 0) {
            Artisan::call('scout:queue-import', ['model' => Contact::class]);
        }

        $fs->deleteDirectory("imports/{$import->id}");
    }
}
