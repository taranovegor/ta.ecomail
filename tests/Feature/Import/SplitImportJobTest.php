<?php

use App\Enums\ImportChunkStatus;
use App\Enums\ImportStatus;
use App\Jobs\ProcessImportChunkJob;
use App\Jobs\SplitImportJob;
use App\Models\Import;
use App\Models\ImportChunk;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('import.disk'));
    Bus::fake([ProcessImportChunkJob::class]);
});

test('sets import status to processing with started_at', function () {
    $import = makeImport(items: 1);

    dispatch_sync(new SplitImportJob($import));

    $import->refresh();
    expect($import->status)->toBe(ImportStatus::Processing)
        ->and($import->started_at)->not->toBeNull();
});

test('updates total_records', function () {
    $import = makeImport(items: 5);

    dispatch_sync(new SplitImportJob($import));

    $import->refresh();
    expect($import->total_records)->toBe(5);
});

test('creates ImportChunk records', function () {
    $import = makeImport(items: 3);

    dispatch_sync(new SplitImportJob($import));

    $chunks = ImportChunk::where('import_id', $import->id)->get();

    expect($chunks)->not->toBeEmpty();
    $chunks->each(fn (ImportChunk $c) => expect($c)
        ->status->toBe(ImportChunkStatus::Pending)
        ->import_id->toBe($import->id)
    );
});

test('creates shard CSV files in storage', function () {
    $import = makeImport(items: 3);

    dispatch_sync(new SplitImportJob($import));

    $disk = Storage::disk(config('import.disk'));
    $files = $disk->files("imports/{$import->id}");

    expect($files)->not->toBeEmpty();

    foreach ($files as $file) {
        expect($disk->get($file))->toStartWith('email,first_name,last_name');
    }
});

test('dispatches Bus::batch with ProcessImportChunkJob jobs', function () {
    $import = makeImport(items: 3);

    dispatch_sync(new SplitImportJob($import));

    Bus::assertBatched(fn ($batch) => $batch->jobs->every(
        fn ($job) => $job instanceof ProcessImportChunkJob,
    ));
});

test('batch contains one job per chunk', function () {
    $import = makeImport(items: 3);

    dispatch_sync(new SplitImportJob($import));

    $chunkCount = ImportChunk::where('import_id', $import->id)->count();

    Bus::assertBatched(fn ($batch) => $batch->jobs->count() === $chunkCount);
});

test('deletes original xml after splitting', function () {
    $import = makeImport(items: 1);
    $originalPath = $import->file_path;

    dispatch_sync(new SplitImportJob($import));

    Storage::disk(config('import.disk'))->assertMissing($originalPath);
});

test('same email routes to single shard', function () {
    config(['import.shard_size' => 100]);

    $items = [];
    for ($i = 0; $i < 10; $i++) {
        $items[] = ['email' => 'same@t.com', 'first_name' => "F{$i}", 'last_name' => "L{$i}"];
    }
    for ($i = 0; $i < 20; $i++) {
        $items[] = ['email' => "x{$i}@t.com", 'first_name' => "X{$i}", 'last_name' => "Y{$i}"];
    }

    $import = makeImportWithItems($items);

    dispatch_sync(new SplitImportJob($import));

    $shardsWithSameEmail = 0;
    foreach (Storage::disk(config('import.disk'))->files("imports/{$import->id}") as $file) {
        if (str_contains(Storage::disk(config('import.disk'))->get($file), 'same@t.com')) {
            $shardsWithSameEmail++;
        }
    }

    expect($shardsWithSameEmail)->toBe(1);
});

test('marks import as failed on error', function () {
    $import = Import::create([
        'status' => ImportStatus::Pending,
        'original_filename' => 'bad.xml',
        'file_path' => 'imports/nonexistent.xml',
        'mime_type' => 'text/xml',
    ]);

    try {
        dispatch_sync(new SplitImportJob($import));
    } catch (Throwable) {
    }

    $import->refresh();
    expect($import->status)->toBe(ImportStatus::Failed)
        ->and($import->error_message)->toContain('Split failed');
});

function makeImport(int $items): Import
{
    $records = [];
    for ($i = 0; $i < $items; $i++) {
        $records[] = ['email' => "u{$i}@t.com", 'first_name' => "F{$i}", 'last_name' => "L{$i}"];
    }

    return makeImportWithItems($records);
}

function makeImportWithItems(array $items): Import
{
    $xml = '<?xml version="1.0" encoding="UTF-8"?><data>';
    foreach ($items as $item) {
        $xml .= '<item>';
        $xml .= '<email>'.htmlspecialchars($item['email']).'</email>';
        $xml .= '<first_name>'.htmlspecialchars($item['first_name']).'</first_name>';
        $xml .= '<last_name>'.htmlspecialchars($item['last_name']).'</last_name>';
        $xml .= '</item>';
    }
    $xml .= '</data>';

    $path = 'imports/test_'.uniqid().'.xml';
    Storage::disk(config('import.disk'))->put($path, $xml);

    return Import::create([
        'status' => ImportStatus::Pending,
        'original_filename' => 'contacts.xml',
        'file_path' => $path,
        'mime_type' => 'text/xml',
    ]);
}
