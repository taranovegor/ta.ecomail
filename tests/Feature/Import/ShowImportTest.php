<?php

use App\Enums\ImportStatus;
use Database\Factories\ImportFactory;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

test('show returns view with import view model', function () {
    $import = ImportFactory::new()->create();

    $this->get(route('imports.show', $import))
        ->assertOk()
        ->assertViewIs('imports.show')
        ->assertViewHas('import');
});

test('show returns 404 for missing import', function () {
    $this->get(route('imports.show', 999))
        ->assertNotFound();
});

test('show displays completed import with statistics', function () {
    $import = ImportFactory::new()->completed()->create();

    $this->get(route('imports.show', $import))
        ->assertOk()
        ->assertViewIs('imports.show')
        ->assertViewHas('import');

    $this->assertDatabaseHas('imports', [
        'id' => $import->id,
        'status' => ImportStatus::Completed,
    ]);
});

test('show displays failed import with error message', function () {
    $import = ImportFactory::new()->failed()->create();

    $this->get(route('imports.show', $import))
        ->assertOk()
        ->assertViewIs('imports.show')
        ->assertViewHas('import');

    $this->assertDatabaseHas('imports', [
        'id' => $import->id,
        'status' => ImportStatus::Failed,
    ]);
});
