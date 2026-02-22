<?php

use App\Contracts\ContactSearchInterface;
use App\Models\Contact;
use Database\Factories\ContactFactory;
use Illuminate\Pagination\LengthAwarePaginator;

use function Pest\Laravel\get;

beforeEach(function () {
    Contact::disableSearchSyncing();
});

test('index displays contacts', function () {
    ContactFactory::new()->create([
        'email' => 'visible@example.com',
        'first_name' => 'Visible',
    ]);

    get(route('contacts.index'))
        ->assertOk()
        ->assertSeeText('visible@example.com')
        ->assertSeeText('Visible');
});

test('index shows empty state when no contacts exist', function () {
    get(route('contacts.index'))
        ->assertOk()
        ->assertSeeText('No contacts yet');
});

test('index paginates contacts', function () {
    ContactFactory::new()->count(20)->create();

    get(route('contacts.index'))
        ->assertOk()
        ->assertViewHas('contacts', function ($contacts) {
            return $contacts->count() === 10
                && $contacts->total() === 20;
        });
});

test('search term is passed to view for display', function () {
    $mock = Mockery::mock(ContactSearchInterface::class);
    $mock->shouldReceive('search')
        ->andReturn(new LengthAwarePaginator([], 0, 15));

    $this->app->instance(ContactSearchInterface::class, $mock);

    get(route('contacts.index', ['search' => 'test query']))
        ->assertOk()
        ->assertViewHas('search', 'test query')
        ->assertSeeText('test query');
});

test('empty search returns all contacts', function () {
    ContactFactory::new()->count(5)->create();

    get(route('contacts.index', ['search' => '']))
        ->assertOk()
        ->assertViewHas('contacts', function ($contacts) {
            return $contacts->total() === 5;
        });
});

test('search shows empty state when no results', function () {
    $mock = Mockery::mock(ContactSearchInterface::class);
    $mock->shouldReceive('search')
        ->andReturn(new LengthAwarePaginator([], 0, 15));

    $this->app->instance(ContactSearchInterface::class, $mock);

    get(route('contacts.index', ['search' => 'nonexistent']))
        ->assertOk()
        ->assertSeeText('No contacts found');
});

test('search query max length is 255', function () {
    get(route('contacts.index', ['search' => str_repeat('a', 256)]))
        ->assertSessionHasErrors('search');
});
