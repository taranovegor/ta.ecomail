<?php

use App\Enums\ImportChunkStatus;
use App\Enums\ImportStatus;
use App\Models\Contact;
use App\Models\Import;
use App\Models\ImportChunk;
use App\Services\Import\ImportFinalizer;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->disk = Storage::disk('local');
    $this->finalizer = new ImportFinalizer;
});

test('aggregates counters from all chunks', function () {
    $import = createImport();

    completedChunk($import, 0, 100, 90, 5, 5);
    completedChunk($import, 1, 100, 85, 10, 5);

    $this->finalizer->finalize($import, $this->disk);

    $import->refresh();
    expect($import->imported_count)->toBe(175)
        ->and($import->duplicates_count)->toBe(15)
        ->and($import->invalid_count)->toBe(10);
});

test('status completed when all chunks succeeded', function () {
    $import = createImport();

    completedChunk($import, 0, 50, 50);
    completedChunk($import, 1, 50, 50);

    $this->finalizer->finalize($import, $this->disk);

    $import->refresh();
    expect($import->status)->toBe(ImportStatus::Completed);
});

test('status completed_with_errors when some chunks failed', function () {
    $import = createImport();

    completedChunk($import, 0, 50, 50);
    failedChunk($import, 1);

    $this->finalizer->finalize($import, $this->disk);

    $import->refresh();
    expect($import->status)->toBe(ImportStatus::CompletedWithErrors);
});

test('status failed when all chunks failed', function () {
    $import = createImport();

    failedChunk($import, 0);
    failedChunk($import, 1);

    $this->finalizer->finalize($import, $this->disk);

    $import->refresh();
    expect($import->status)->toBe(ImportStatus::Failed);
});

test('sets completed_at and processing_time', function () {
    $import = createImport();

    completedChunk($import, 0, 10, 10);

    $this->finalizer->finalize($import, $this->disk);

    $import->refresh();
    expect($import->completed_at)->not->toBeNull()
        ->and($import->processing_time_seconds)->toBeGreaterThanOrEqual(0);
});

test('calls scout:queue-import when records imported', function () {
    Artisan::spy();

    $import = createImport();
    completedChunk($import, 0, 10, 10);

    $this->finalizer->finalize($import, $this->disk);

    Artisan::shouldHaveReceived('call')
        ->with('scout:queue-import', Mockery::on(fn ($args) => $args['model'] === Contact::class))
        ->once();
});

test('skips scout:queue-import when no records imported', function () {
    Artisan::spy();

    $import = createImport();
    completedChunk($import, 0, 10, 0, 5, 5);

    $this->finalizer->finalize($import, $this->disk);

    Artisan::shouldNotHaveReceived('call');
});

test('cleans up shard directory', function () {
    $import = createImport();
    completedChunk($import, 0, 10, 10);

    Storage::disk('local')->put("imports/{$import->id}/leftover.csv", 'data');

    $this->finalizer->finalize($import, $this->disk);

    Storage::disk('local')->assertMissing("imports/{$import->id}/leftover.csv");
});

function createImport(): Import
{
    return Import::create([
        'status' => ImportStatus::Processing,
        'original_filename' => 'test.xml',
        'file_path' => 'imports/test.xml',
        'mime_type' => 'text/xml',
        'started_at' => now()->subSeconds(5),
    ]);
}

function completedChunk(
    Import $import,
    int $number,
    int $total = 0,
    int $imported = 0,
    int $duplicates = 0,
    int $invalid = 0,
): void {
    ImportChunk::create([
        'import_id' => $import->id,
        'chunk_number' => $number,
        'file_path' => "imports/{$import->id}/shard_{$number}.csv",
        'status' => ImportChunkStatus::Completed,
        'total' => $total,
        'imported' => $imported,
        'duplicates' => $duplicates,
        'invalid' => $invalid,
    ]);
}

function failedChunk(Import $import, int $number): void
{
    ImportChunk::create([
        'import_id' => $import->id,
        'chunk_number' => $number,
        'file_path' => "imports/{$import->id}/shard_{$number}.csv",
        'status' => ImportChunkStatus::Failed,
        'error_message' => 'Test failure',
    ]);
}
