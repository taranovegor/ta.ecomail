<?php

use App\Services\Import\ShardWriter;
use App\Services\Import\XmlContactImporter;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

test('returns correct total record count', function () {
    putXml('source.xml', [
        ['email' => 'a@t.com', 'first_name' => 'A', 'last_name' => 'A'],
        ['email' => 'b@t.com', 'first_name' => 'B', 'last_name' => 'B'],
        ['email' => 'c@t.com', 'first_name' => 'C', 'last_name' => 'C'],
    ]);

    $writer = new ShardWriter;
    $total = $writer->write(
        new XmlContactImporter,
        Storage::disk('local'),
        'source.xml',
        'shards',
        2,
    );

    expect($total)->toBe(3);
});

test('creates correct number of shard files', function () {
    putXml('source.xml', [
        ['email' => 'a@t.com', 'first_name' => 'A', 'last_name' => 'A'],
    ]);

    $writer = new ShardWriter;
    $writer->write(
        new XmlContactImporter,
        Storage::disk('local'),
        'source.xml',
        'shards',
        3,
    );

    $files = Storage::disk('local')->files('shards');
    expect($files)->toHaveCount(3);
});

test('each shard has csv header', function () {
    putXml('source.xml', [
        ['email' => 'a@t.com', 'first_name' => 'A', 'last_name' => 'A'],
    ]);

    $writer = new ShardWriter;
    $writer->write(
        new XmlContactImporter,
        Storage::disk('local'),
        'source.xml',
        'shards',
        2,
    );

    foreach (Storage::disk('local')->files('shards') as $file) {
        $content = Storage::disk('local')->get($file);
        expect($content)->toStartWith('email,first_name,last_name');
    }
});

test('same email always routes to same shard', function () {
    $items = [];
    for ($i = 0; $i < 10; $i++) {
        $items[] = ['email' => 'same@t.com', 'first_name' => "F{$i}", 'last_name' => "L{$i}"];
    }
    for ($i = 0; $i < 10; $i++) {
        $items[] = ['email' => "other{$i}@t.com", 'first_name' => "X{$i}", 'last_name' => "Y{$i}"];
    }

    putXml('source.xml', $items);

    $writer = new ShardWriter;
    $writer->write(
        new XmlContactImporter,
        Storage::disk('local'),
        'source.xml',
        'shards',
        4,
    );

    $shardsWithSameEmail = 0;
    foreach (Storage::disk('local')->files('shards') as $file) {
        if (str_contains(Storage::disk('local')->get($file), 'same@t.com')) {
            $shardsWithSameEmail++;
        }
    }

    expect($shardsWithSameEmail)->toBe(1);
});

test('all records are distributed across shards', function () {
    $items = [];
    for ($i = 0; $i < 20; $i++) {
        $items[] = ['email' => "user{$i}@t.com", 'first_name' => "F{$i}", 'last_name' => "L{$i}"];
    }

    putXml('source.xml', $items);

    $writer = new ShardWriter;
    $writer->write(
        new XmlContactImporter,
        Storage::disk('local'),
        'source.xml',
        'shards',
        3,
    );

    $totalRows = 0;
    foreach (Storage::disk('local')->files('shards') as $file) {
        $lines = explode("\n", trim(Storage::disk('local')->get($file)));
        $totalRows += count($lines) - 1;
    }

    expect($totalRows)->toBe(20);
});

test('throws on missing source file', function () {
    $writer = new ShardWriter;

    expect(fn () => $writer->write(
        new XmlContactImporter,
        Storage::disk('local'),
        'nonexistent.xml',
        'shards',
        1,
    ))->toThrow(RuntimeException::class);
});

function putXml(string $path, array $items): void
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

    Storage::disk('local')->put($path, $xml);
}
