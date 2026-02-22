<?php

use App\Models\Contact;
use Database\Factories\ContactFactory;

use function Pest\Laravel\get;

beforeEach(function () {
    config(['scout.driver' => 'collection']);
});

test('search finds contacts by first name', function () {
    Contact::disableSearchSyncing();
    ContactFactory::new()->create(['first_name' => 'Henri', 'last_name' => 'Schinner']);
    ContactFactory::new()->create(['first_name' => 'John', 'last_name' => 'Doe']);
    Contact::enableSearchSyncing();

    Contact::makeAllSearchable();

    get(route('contacts.index', ['search' => 'Henri']))
        ->assertOk()
        ->assertSeeText('Henri')
        ->assertDontSeeText('John');
});

test('search finds contacts by email', function () {
    Contact::disableSearchSyncing();
    ContactFactory::new()->create(['email' => 'special@test.com']);
    ContactFactory::new()->create(['email' => 'other@test.com']);
    Contact::enableSearchSyncing();

    Contact::makeAllSearchable();

    get(route('contacts.index', ['search' => 'special']))
        ->assertOk()
        ->assertSeeText('special@test.com');
});

test('search finds contacts by last name', function () {
    Contact::disableSearchSyncing();
    ContactFactory::new()->create(['last_name' => 'Schinner']);
    ContactFactory::new()->create(['last_name' => 'Tillman']);
    Contact::enableSearchSyncing();

    Contact::makeAllSearchable();

    get(route('contacts.index', ['search' => 'Schinner']))
        ->assertOk()
        ->assertSeeText('Schinner')
        ->assertDontSeeText('Tillman');
});

test('search returns empty when no matches', function () {
    Contact::disableSearchSyncing();
    ContactFactory::new()->count(3)->create();
    Contact::enableSearchSyncing();

    Contact::makeAllSearchable();

    get(route('contacts.index', ['search' => 'zzzznonexistent']))
        ->assertOk()
        ->assertSeeText('No contacts found');
});
