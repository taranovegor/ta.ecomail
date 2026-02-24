<?php

use App\Enums\IssueType;
use App\Models\Contact;
use App\Models\Import;
use App\Models\ImportIssue;
use App\Services\Import\ChunkProcessor;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    $this->import = Import::create([
        'status' => 'processing',
        'original_filename' => 'test.xml',
        'file_path' => 'imports/test.xml',
        'mime_type' => 'text/xml',
        'started_at' => now(),
    ]);

    $this->processor = new ChunkProcessor;
    $this->disk = Storage::disk('local');
});

test('imports valid contacts and returns correct counters', function () {
    putCsv('shard.csv', [
        ['alice@test.com', 'Alice', 'Smith'],
        ['bob@test.com', 'Bob', 'Jones'],
    ]);

    $counters = $this->processor->process($this->disk, 'shard.csv', $this->import->id, 1000);

    expect($counters)->toBe(['total' => 2, 'imported' => 2, 'duplicates' => 0, 'invalid' => 0]);

    $this->assertDatabaseHas('contacts', ['email' => 'alice@test.com']);
    $this->assertDatabaseHas('contacts', ['email' => 'bob@test.com']);
});

test('should normalize email before saving contact', function () {
    putCsv('shard.csv', [
        ['   NORMALIZEME@test.com   ', 'Alice', 'Smith'],
    ]);

    $counters = $this->processor->process($this->disk, 'shard.csv', $this->import->id, 1000);

    expect($counters)->toBe(['total' => 1, 'imported' => 1, 'duplicates' => 0, 'invalid' => 0]);

    $this->assertDatabaseHas('contacts', ['email' => 'normalizeme@test.com']);
    $this->assertDatabaseMissing('contacts', ['email' => 'NORMALIZEME@test.com']);
    $this->assertDatabaseMissing('contacts', ['email' => '   NORMALIZEME@test.com   ']);
});

test('detects duplicates within file', function () {
    putCsv('shard.csv', [
        ['dup@test.com', 'First', 'Entry'],
        ['dup@test.com', 'Second', 'Entry'],
        ['dup@test.com', 'Third', 'Entry'],
    ]);

    $counters = $this->processor->process($this->disk, 'shard.csv', $this->import->id, 1000);

    expect($counters['imported'])->toBe(1)
        ->and($counters['duplicates'])->toBe(2)
        ->and($counters['total'])->toBe(3);
});

test('creates issue records for duplicates', function () {
    putCsv('shard.csv', [
        ['dup@test.com', 'First', 'One'],
        ['dup@test.com', 'Second', 'Two'],
        ['DUP@uppercase.com', 'Upper', 'Case'],
        ['dup@uppercase.com', 'Upper', 'Case'],
    ]);

    $this->processor->process($this->disk, 'shard.csv', $this->import->id, 1000);

    $issues = ImportIssue::where('import_id', $this->import->id)
        ->where('type', IssueType::Duplicate)
        ->get();

    expect($issues)->toHaveCount(2)
        ->and($issues->first())
        ->email->toBe('dup@test.com')
        ->reason->toBe('Duplicate email within file')
        ->and($issues->last())
        ->email->toBe('dup@uppercase.com')
        ->reason->toBe('Duplicate email within file');
});

test('detects invalid email', function () {
    putCsv('shard.csv', [
        ['not-an-email', 'Bad', 'Email'],
        ['valid@test.com', 'Good', 'Email'],
    ]);

    $counters = $this->processor->process($this->disk, 'shard.csv', $this->import->id, 1000);

    expect($counters['invalid'])->toBe(1)
        ->and($counters['imported'])->toBe(1);
});

test('creates issue records for invalid with validation reason', function () {
    putCsv('shard.csv', [
        ['bad-email', 'John', 'Doe'],
    ]);

    $this->processor->process($this->disk, 'shard.csv', $this->import->id, 1000);

    $issue = ImportIssue::where('import_id', $this->import->id)->first();

    expect($issue)
        ->type->toBe(IssueType::Invalid)
        ->email->toBe('bad-email')
        ->and($issue->reason)->toContain('email');
});

test('detects missing required fields', function () {
    putCsv('shard.csv', [
        ['valid@test.com', '', 'Doe'],
        ['valid2@test.com', 'John', ''],
    ]);

    $counters = $this->processor->process($this->disk, 'shard.csv', $this->import->id, 1000);

    expect($counters['invalid'])->toBe(2)
        ->and($counters['imported'])->toBe(0);
});

test('existing db contacts are silently skipped', function () {
    Contact::create([
        'email' => 'exists@test.com',
        'first_name' => 'Existing',
        'last_name' => 'Contact',
    ]);

    putCsv('shard.csv', [
        ['exists@test.com', 'New', 'Version'],
        ['fresh@test.com', 'Fresh', 'Contact'],
    ]);

    $counters = $this->processor->process($this->disk, 'shard.csv', $this->import->id, 1000);

    expect($counters['imported'])->toBe(1)
        ->and($counters['duplicates'])->toBe(0)
        ->and($counters['invalid'])->toBe(0);

    $this->assertDatabaseHas('contacts', [
        'email' => 'exists@test.com',
        'first_name' => 'Existing',
    ]);
});

test('handles mixed valid, invalid and duplicate records', function () {
    putCsv('shard.csv', [
        ['valid@test.com', 'Valid', 'Record'],
        ['bad-email', 'Invalid', 'Email'],
        ['valid@test.com', 'Dup', 'Record'],
        ['another@test.com', 'Another', 'Valid'],
        ['', 'Missing', 'Email'],
        ['another@test.com', 'Another', 'Dup'],
    ]);

    $counters = $this->processor->process($this->disk, 'shard.csv', $this->import->id, 1000);

    expect($counters)->toBe([
        'total' => 6,
        'imported' => 2,
        'duplicates' => 2,
        'invalid' => 2,
    ])->and(ImportIssue::where('import_id', $this->import->id)->count())->toBe(4);
});

test('flushes inserts in batches', function () {
    $rows = [];
    for ($i = 0; $i < 5; $i++) {
        $rows[] = ["user{$i}@test.com", "First{$i}", "Last{$i}"];
    }
    putCsv('shard.csv', $rows);

    $counters = $this->processor->process($this->disk, 'shard.csv', $this->import->id, 2);

    expect($counters['imported'])->toBe(5);
    $this->assertDatabaseCount('contacts', 5);
});

test('flushes issues in batches', function () {
    $rows = [];
    for ($i = 0; $i < 5; $i++) {
        $rows[] = ["bad-email-{$i}", "First{$i}", "Last{$i}"];
    }
    putCsv('shard.csv', $rows);

    $this->processor->process($this->disk, 'shard.csv', $this->import->id, 2);

    expect(ImportIssue::where('import_id', $this->import->id)->count())->toBe(5);
});

test('throws on missing shard file', function () {
    expect(fn () => $this->processor->process(
        $this->disk, 'nonexistent.csv', $this->import->id, 1000,
    ))->toThrow(RuntimeException::class);
});

test('skips rows with less than 3 columns', function () {
    $csv = "email,first_name,last_name\none-column\ntwo,columns\nvalid@test.com,John,Doe\n";
    Storage::disk('local')->put('shard.csv', $csv);

    $counters = $this->processor->process($this->disk, 'shard.csv', $this->import->id, 1000);

    expect($counters['total'])->toBe(1)
        ->and($counters['imported'])->toBe(1);
});

function putCsv(string $path, array $rows): void
{
    $csv = "email,first_name,last_name\n";
    foreach ($rows as $row) {
        $csv .= implode(',', array_map(
            fn ($v) => '"'.str_replace('"', '""', $v).'"',
            $row,
        ))."\n";
    }

    Storage::disk('local')->put($path, $csv);
}
