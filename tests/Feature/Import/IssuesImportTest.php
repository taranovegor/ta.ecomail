<?php

use App\Enums\IssueType;
use Database\Factories\ImportFactory;
use Database\Factories\ImportIssueFactory;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

test('issues returns view with paginated issues', function () {
    $import = ImportFactory::new()->create();
    ImportIssueFactory::new()->count(5)->create(['import_id' => $import->id]);

    $this->get(route('imports.issues', $import))
        ->assertOk()
        ->assertViewIs('imports.issues')
        ->assertViewHas(['import', 'issues', 'currentType']);
});

test('issues filters by type when provided', function () {
    $import = ImportFactory::new()->create();

    ImportIssueFactory::new()->count(3)->create([
        'import_id' => $import->id,
        'type' => IssueType::Duplicate,
    ]);

    ImportIssueFactory::new()->count(2)->create([
        'import_id' => $import->id,
        'type' => IssueType::Invalid,
    ]);

    $response = $this->get(route('imports.issues', [$import, 'type' => IssueType::Duplicate->value]))
        ->assertOk();

    expect($response->viewData('issues')->total())->toBe(3)
        ->and($response->viewData('currentType'))->toBe(IssueType::Duplicate->value);
});

test('issues passes null currentType when no filter', function () {
    $import = ImportFactory::new()->create();

    $response = $this->get(route('imports.issues', $import))->assertOk();

    expect($response->viewData('currentType'))->toBeNull();
});

test('issues returns 404 for missing import', function () {
    $this->get(route('imports.issues', 999))->assertNotFound();
});
