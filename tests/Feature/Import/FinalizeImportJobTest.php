<?php

use App\Enums\ImportChunkStatus;
use App\Enums\ImportStatus;
use App\Jobs\FinalizeImportJob;
use App\Models\Import;
use App\Models\ImportChunk;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('import.disk'));
    Artisan::spy();
});

test('finalizes import with aggregated counters', function () {
    $import = processingImport();

    addChunk($import, 0, ImportChunkStatus::Completed, total: 100, imported: 90, duplicates: 5, invalid: 5);
    addChunk($import, 1, ImportChunkStatus::Completed, total: 80, imported: 70, duplicates: 8, invalid: 2);

    dispatch_sync(new FinalizeImportJob($import));

    $import->refresh();
    expect($import->status)->toBe(ImportStatus::Completed)
        ->and($import->imported_count)->toBe(160)
        ->and($import->duplicates_count)->toBe(13)
        ->and($import->invalid_count)->toBe(7)
        ->and($import->completed_at)->not->toBeNull();
});

test('status completed_with_errors on partial failure', function () {
    $import = processingImport();

    addChunk($import, 0, ImportChunkStatus::Completed, total: 50, imported: 50);
    addChunk($import, 1, ImportChunkStatus::Failed);

    dispatch_sync(new FinalizeImportJob($import));

    $import->refresh();
    expect($import->status)->toBe(ImportStatus::CompletedWithErrors);
});

test('status failed when all chunks failed', function () {
    $import = processingImport();

    addChunk($import, 0, ImportChunkStatus::Failed);
    addChunk($import, 1, ImportChunkStatus::Failed);

    dispatch_sync(new FinalizeImportJob($import));

    $import->refresh();
    expect($import->status)->toBe(ImportStatus::Failed);
});

test('calls scout:queue-import when imported > 0', function () {
    $import = processingImport();
    addChunk($import, 0, ImportChunkStatus::Completed, total: 10, imported: 10);

    dispatch_sync(new FinalizeImportJob($import));

    Artisan::shouldHaveReceived('call')
        ->with('scout:queue-import', \Mockery::hasKey('model'))
        ->once();
});

test('skips scout:queue-import when imported is 0', function () {
    $import = processingImport();
    addChunk($import, 0, ImportChunkStatus::Completed, total: 5, imported: 0, invalid: 5);

    dispatch_sync(new FinalizeImportJob($import));

    Artisan::shouldNotHaveReceived('call');
});

test('cleans up shard directory', function () {
    $import = processingImport();
    addChunk($import, 0, ImportChunkStatus::Completed, total: 1, imported: 1);

    Storage::disk(config('import.disk'))->put("imports/{$import->id}/leftover.csv", 'data');

    dispatch_sync(new FinalizeImportJob($import));

    Storage::disk(config('import.disk'))
        ->assertMissing("imports/{$import->id}/leftover.csv");
});

test('job is dispatched on import-finalize queue', function () {
    $import = processingImport();

    $job = new FinalizeImportJob($import);

    expect($job->queue)->toBe('import-finalize');
});

function processingImport(): Import
{
    return Import::create([
        'status' => ImportStatus::Processing,
        'original_filename' => 'test.xml',
        'file_path' => 'imports/test.xml',
        'mime_type' => 'text/xml',
        'started_at' => now()->subSeconds(5),
    ]);
}

function addChunk(
    Import $import,
    int $number,
    ImportChunkStatus $status,
    int $total = 0,
    int $imported = 0,
    int $duplicates = 0,
    int $invalid = 0,
): void {
    ImportChunk::create([
        'import_id' => $import->id,
        'chunk_number' => $number,
        'file_path' => "imports/{$import->id}/shard_{$number}.csv",
        'status' => $status,
        'total' => $total,
        'imported' => $imported,
        'duplicates' => $duplicates,
        'invalid' => $invalid,
        'error_message' => $status === ImportChunkStatus::Failed ? 'Test failure' : null,
    ]);
}
