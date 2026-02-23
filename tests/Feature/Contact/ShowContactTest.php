<?php

use App\Models\Contact;
use Database\Factories\ContactFactory;

use function Pest\Laravel\get;

beforeEach(function () {
    Contact::disableSearchSyncing();
});

test('show page displays contact details', function () {
    $contact = ContactFactory::new()->create([
        'email' => 'henri@example.com',
        'first_name' => 'Henri',
        'last_name' => 'Schinner',
    ]);

    get(route('contacts.show', $contact))
        ->assertOk()
        ->assertViewIs('contacts.show')
        ->assertSeeText('henri@example.com')
        ->assertSeeText('Henri')
        ->assertSeeText('Schinner');
});

test('show page returns 404 for nonexistent contact', function () {
    get(route('contacts.show', 999999))
        ->assertNotFound();
});
