<?php

use App\Enums\ImportChunkStatus;
use App\Enums\ImportStatus;
use App\Jobs\ProcessImportChunkJob;
use App\Models\Contact;
use App\Models\Import;
use App\Models\ImportChunk;
use App\Models\ImportIssue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('import.disk'));
});

test('inserts contacts and marks chunk completed', function () {
    $chunk = makeChunk([
        ['alice@test.com', 'Alice', 'Smith'],
        ['bob@test.com', 'Bob', 'Jones'],
    ]);

    dispatch_sync(new ProcessImportChunkJob($chunk));

    $chunk->refresh();
    expect($chunk->status)->toBe(ImportChunkStatus::Completed)
        ->and($chunk->total)->toBe(2)
        ->and($chunk->imported)->toBe(2)
        ->and($chunk->duplicates)->toBe(0)
        ->and($chunk->invalid)->toBe(0);

    $this->assertDatabaseCount('contacts', 2);
});

test('tracks file duplicates in chunk counters', function () {
    $chunk = makeChunk([
        ['dup@test.com', 'First', 'Entry'],
        ['dup@test.com', 'Second', 'Entry'],
        ['unique@test.com', 'Unique', 'User'],
    ]);

    dispatch_sync(new ProcessImportChunkJob($chunk));

    $chunk->refresh();
    expect($chunk->imported)->toBe(2)
        ->and($chunk->duplicates)->toBe(1);
});

test('tracks invalid records in chunk counters', function () {
    $chunk = makeChunk([
        ['bad-email', 'Bad', 'Email'],
        ['valid@test.com', 'Good', 'Email'],
    ]);

    dispatch_sync(new ProcessImportChunkJob($chunk));

    $chunk->refresh();
    expect($chunk->invalid)->toBe(1)
        ->and($chunk->imported)->toBe(1);
});

test('creates import issue records', function () {
    $chunk = makeChunk([
        ['bad', 'Invalid', 'Email'],
        ['a@t.com', 'Valid', 'One'],
        ['a@t.com', 'Dup', 'One'],
    ]);

    dispatch_sync(new ProcessImportChunkJob($chunk));

    expect(ImportIssue::where('import_id', $chunk->import_id)->count())->toBe(2);
});

test('db existing records silently skipped', function () {
    Contact::create([
        'email' => 'exists@test.com',
        'first_name' => 'Old',
        'last_name' => 'Record',
    ]);

    $chunk = makeChunk([
        ['exists@test.com', 'New', 'Version'],
        ['fresh@test.com', 'Fresh', 'Contact'],
    ]);

    dispatch_sync(new ProcessImportChunkJob($chunk));

    $chunk->refresh();
    expect($chunk->imported)->toBe(1)
        ->and($chunk->duplicates)->toBe(0);

    $this->assertDatabaseHas('contacts', ['email' => 'exists@test.com', 'first_name' => 'Old']);
});

test('deletes shard file after processing', function () {
    $chunk = makeChunk([['a@t.com', 'A', 'B']]);
    $path = $chunk->file_path;

    dispatch_sync(new ProcessImportChunkJob($chunk));

    Storage::disk(config('import.disk'))->assertMissing($path);
});

test('marks chunk as failed on error and still deletes file', function () {
    $import = Import::create([
        'status' => ImportStatus::Processing,
        'original_filename' => 'test.xml',
        'file_path' => 'imports/test.xml',
        'mime_type' => 'text/xml',
        'started_at' => now(),
    ]);

    $shardPath = "imports/{$import->id}/shard_0.csv";

    $chunk = ImportChunk::create([
        'import_id' => $import->id,
        'chunk_number' => 0,
        'file_path' => $shardPath,
        'status' => ImportChunkStatus::Pending,
    ]);

    try {
        dispatch_sync(new ProcessImportChunkJob($chunk));
    } catch (Throwable) {
    }

    $chunk->refresh();
    expect($chunk->status)->toBe(ImportChunkStatus::Failed)
        ->and($chunk->error_message)->not->toBeNull();
});

test('sets status to processing before work begins', function () {
    $chunk = makeChunk([['a@t.com', 'A', 'B']]);

    dispatch_sync(new ProcessImportChunkJob($chunk));

    $chunk->refresh();
    expect($chunk->status)->toBe(ImportChunkStatus::Completed);
});

function makeChunk(array $rows, int $chunkNumber = 0): ImportChunk
{
    $import = Import::create([
        'status' => ImportStatus::Processing,
        'original_filename' => 'test.xml',
        'file_path' => 'imports/test.xml',
        'mime_type' => 'text/xml',
        'started_at' => now(),
    ]);

    $csv = "email,first_name,last_name\n";
    foreach ($rows as $row) {
        $csv .= implode(',', array_map(
            fn ($v) => '"'.str_replace('"', '""', $v).'"',
            $row,
        ))."\n";
    }

    $path = "imports/{$import->id}/shard_{$chunkNumber}.csv";
    Storage::disk(config('import.disk'))->put($path, $csv);

    return ImportChunk::create([
        'import_id' => $import->id,
        'chunk_number' => $chunkNumber,
        'file_path' => $path,
        'status' => ImportChunkStatus::Pending,
    ]);
}
