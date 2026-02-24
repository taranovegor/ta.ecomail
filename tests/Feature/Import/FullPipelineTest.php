<?php

use App\Enums\ImportStatus;
use App\Enums\IssueType;
use App\Jobs\FinalizeImportJob;
use App\Jobs\ProcessImportChunkJob;
use App\Jobs\SplitImportJob;
use App\Models\Contact;
use App\Models\Import;
use App\Models\ImportIssue;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('import.disk'));
    Artisan::spy();
});

test('valid records are imported end-to-end', function () {
    $import = xmlImport([
        record('alice@test.com', 'Alice', 'Smith'),
        record('bob@test.com', 'Bob', 'Jones'),
        record('carol@test.com', 'Carol', 'White'),
    ]);

    pipeline($import);

    $import->refresh();
    expect($import->status)->toBe(ImportStatus::Completed)
        ->and($import->total_records)->toBe(3)
        ->and($import->imported_count)->toBe(3)
        ->and($import->duplicates_count)->toBe(0)
        ->and($import->invalid_count)->toBe(0);

    $this->assertDatabaseCount('contacts', 3);
});

test('file duplicates are counted and tracked', function () {
    $import = xmlImport([
        record('dup@test.com', 'First', 'Entry'),
        record('dup@test.com', 'Second', 'Entry'),
        record('unique@test.com', 'Unique', 'User'),
    ]);

    pipeline($import);

    $import->refresh();
    expect($import->imported_count)->toBe(2)
        ->and($import->duplicates_count)->toBe(1);

    $dupIssues = ImportIssue::where('import_id', $import->id)
        ->where('type', IssueType::Duplicate)
        ->get();

    expect($dupIssues)->toHaveCount(1)
        ->and($dupIssues->first()->email)->toBe('dup@test.com');
});

test('invalid records are counted and tracked with validation reason', function () {
    $import = xmlImport([
        record('valid@test.com', 'Valid', 'User'),
        record('not-an-email', 'Bad', 'Email'),
        record('ok@test.com', '', 'NoFirstName'),
    ]);

    pipeline($import);

    $import->refresh();
    expect($import->imported_count)->toBe(1)
        ->and($import->invalid_count)->toBe(2);

    $invalidIssues = ImportIssue::where('import_id', $import->id)
        ->where('type', IssueType::Invalid)
        ->get();

    expect($invalidIssues)->toHaveCount(2)
        ->and($invalidIssues->pluck('reason')->join(' '))->toContain('email');
});

test('existing db records are silently skipped', function () {
    Contact::create([
        'email' => 'existing@test.com',
        'first_name' => 'Existing',
        'last_name' => 'Contact',
    ]);

    $import = xmlImport([
        record('existing@test.com', 'New', 'Version'),
        record('fresh@test.com', 'Fresh', 'Contact'),
    ]);

    pipeline($import);

    $import->refresh();
    expect($import->imported_count)->toBe(1)
        ->and($import->duplicates_count)->toBe(0)
        ->and($import->invalid_count)->toBe(0);

    $this->assertDatabaseHas('contacts', ['email' => 'existing@test.com', 'first_name' => 'Existing']);
    $this->assertDatabaseCount('contacts', 2);
});

test('mixed scenario: valid + invalid + file duplicates + db existing', function () {
    Contact::create(['email' => 'pre@test.com', 'first_name' => 'Pre', 'last_name' => 'Existing']);

    $import = xmlImport([
        record('good1@test.com', 'Good', 'One'),
        record('good2@test.com', 'Good', 'Two'),
        record('bad-email', 'Bad', 'Email'),
        record('good1@test.com', 'Dup', 'One'),
        record('', 'Empty', 'Email'),
        record('pre@test.com', 'New', 'Version'),
    ]);

    pipeline($import);

    $import->refresh();
    expect($import->total_records)->toBe(6)
        ->and($import->imported_count)->toBe(2)
        ->and($import->duplicates_count)->toBe(1)
        ->and($import->invalid_count)->toBe(2);

    $this->assertDatabaseCount('contacts', 3);

    expect(ImportIssue::where('import_id', $import->id)->count())->toBe(3);
});

test('files are cleaned up after pipeline', function () {
    $import = xmlImport([record('a@t.com', 'A', 'B')]);
    $originalPath = $import->file_path;

    pipeline($import);

    $disk = Storage::disk(config('import.disk'));
    $disk->assertMissing($originalPath);
    expect($disk->files("imports/{$import->id}"))->toBeEmpty();
});

test('scout reindex is called after import', function () {
    $import = xmlImport([record('a@t.com', 'A', 'B')]);

    pipeline($import);

    Artisan::shouldHaveReceived('call')
        ->with('scout:queue-import', Mockery::hasKey('model'))
        ->once();
});

function record(string $email, string $firstName, string $lastName): array
{
    return compact('email', 'firstName', 'lastName');
}

function xmlImport(array $items): Import
{
    $xml = '<?xml version="1.0" encoding="UTF-8"?><data>';
    foreach ($items as $item) {
        $xml .= '<item>';
        $xml .= '<email>'.htmlspecialchars($item['email']).'</email>';
        $xml .= '<first_name>'.htmlspecialchars($item['firstName']).'</first_name>';
        $xml .= '<last_name>'.htmlspecialchars($item['lastName']).'</last_name>';
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

function pipeline(Import $import): void
{
    Bus::fake([ProcessImportChunkJob::class]);

    dispatch_sync(new SplitImportJob($import));

    $capturedJobs = collect();
    Bus::assertBatched(function ($batch) use ($capturedJobs) {
        $batch->jobs->each(fn ($job) => $capturedJobs->push($job));

        return true;
    });

    Bus::swap(app(Dispatcher::class));

    $capturedJobs->each(function (ProcessImportChunkJob $job) {
        app()->call([$job, 'handle']);
    });

    dispatch_sync(new FinalizeImportJob($import));
}
