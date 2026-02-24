<?php

use App\Services\ImportService;
use Database\Factories\ImportFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

test('store initiates import and redirects to show', function () {
    $file = UploadedFile::fake()->create('contacts.xml', 100, 'text/xml');

    $import = ImportFactory::new()->create();

    $this->mock(ImportService::class)
        ->shouldReceive('initiate')
        ->once()
        ->andReturn($import);

    $this->post(route('imports.store'), ['file' => $file])
        ->assertRedirect(route('imports.show', $import))
        ->assertSessionHas('success');
});

test('store redirects back with warning when no file uploaded', function () {
    $this->post(route('imports.store'), [])
        ->assertRedirectBack();
});

test('store redirects back with warning when service throws', function () {
    $file = UploadedFile::fake()->create('contacts.xml', 100, 'text/xml');

    $this->mock(ImportService::class)
        ->shouldReceive('initiate')
        ->andThrow(new RuntimeException('Something went wrong'));

    $this->post(route('imports.store'), ['file' => $file])
        ->assertRedirect(route('imports.create'))
        ->assertSessionHas('warning', 'Something went wrong');
});
