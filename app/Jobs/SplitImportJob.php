<?php

namespace App\Jobs;

use App\Enums\ImportChunkStatus;
use App\Enums\ImportStatus;
use App\Models\Import;
use App\Models\ImportChunk;
use App\Services\Import\ContactImporterResolver;
use App\Services\Import\ShardWriter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SplitImportJob implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly Import $import,
    ) {
        $this->onQueue('import-orchestrator');
    }

    /**
     * @throws Throwable
     */
    public function handle(ShardWriter $shardWriter): void
    {
        $fs = Storage::disk(config('import.disk'));

        try {
            $this->import->update([
                'status' => ImportStatus::Processing,
                'started_at' => now(),
            ]);

            $importer = app(ContactImporterResolver::class)->resolve($this->import->mime_type);

            $fileSize = $fs->size($this->import->file_path);
            $shardSize = (int) config('import.shard_size', 5 * 1024 * 1024);
            $shardCount = max(1, (int) ceil($fileSize / $shardSize));
            $shardDir = "imports/{$this->import->id}";

            $totalRecords = $shardWriter->write($importer, $fs, $this->import->file_path, $shardDir, $shardCount);

            $this->import->update(['total_records' => $totalRecords]);

            $jobs = [];
            for ($i = 0; $i < $shardCount; $i++) {
                $chunk = ImportChunk::create([
                    'import_id' => $this->import->id,
                    'chunk_number' => $i,
                    'file_path' => "{$shardDir}/shard_{$i}.csv",
                    'status' => ImportChunkStatus::Pending,
                ]);

                $jobs[] = new ProcessImportChunkJob($chunk);
            }

            $import = $this->import;
            Bus::batch($jobs)
                ->name("import-{$this->import->id}")
                ->onQueue('import-chunks')
                ->finally(function () use ($import) {
                    dispatch(new FinalizeImportJob($import));
                })
                ->dispatch();

            $fs->delete($this->import->file_path);

        } catch (Throwable $e) {
            $this->import->update([
                'status' => ImportStatus::Failed,
                'error_message' => "Split failed: {$e->getMessage()}",
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }
}
