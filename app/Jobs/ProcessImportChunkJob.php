<?php

namespace App\Jobs;

use App\Enums\ImportChunkStatus;
use App\Models\ImportChunk;
use App\Services\Import\ChunkProcessor;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessImportChunkJob implements ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly ImportChunk $chunk,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(ChunkProcessor $processor): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $fs = Storage::disk(config('import.disk'));
        $batchSize = (int) config('import.batch_size', 1000);

        try {
            $this->chunk->update(['status' => ImportChunkStatus::Processing]);

            $counters = $processor->process($fs, $this->chunk->file_path, $this->chunk->import_id, $batchSize);

            $this->chunk->update([
                'status' => ImportChunkStatus::Completed,
                ...$counters,
            ]);
        } catch (Throwable $e) {
            $this->chunk->update([
                'status' => ImportChunkStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            $fs->delete($this->chunk->file_path);
        }
    }
}
