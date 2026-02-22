<?php

use App\Models\Contact;
use Database\Factories\ContactFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\put;

uses(RefreshDatabase::class);

beforeEach(function () {
    Contact::disableSearchSyncing();
});

test('contact can be updated with valid data', function () {
    $contact = ContactFactory::new()->create();

    $updatedData = [
        'email' => 'updated@example.com',
        'first_name' => 'Updated',
        'last_name' => 'Name',
    ];

    put(route('contacts.update', $contact), $updatedData)
        ->assertRedirectToRoute('contacts.show', $contact)
        ->assertSessionHas('success');

    $this->assertDatabaseHas('contacts', $updatedData);
});

test('email can remain the same on update', function () {
    $contact = ContactFactory::new()->create(['email' => 'same@example.com']);

    put(route('contacts.update', $contact), [
        'email' => 'same@example.com',
        'first_name' => 'Updated',
        'last_name' => 'Name',
    ])
        ->assertRedirectToRoute('contacts.show', $contact)
        ->assertSessionHasNoErrors();
});

test('email must be unique excluding current contact', function () {
    ContactFactory::new()->create(['email' => 'taken@example.com']);
    $contact = ContactFactory::new()->create();

    put(route('contacts.update', $contact), [
        'email' => 'taken@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
    ])
        ->assertSessionHasErrors('email');
});

test('email must be valid rfc format on update', function () {
    $contact = ContactFactory::new()->create();

    put(route('contacts.update', $contact), [
        'email' => 'not-valid',
        'first_name' => 'John',
        'last_name' => 'Doe',
    ])
        ->assertSessionHasErrors('email');
});

test('all fields are required on update', function () {
    $contact = ContactFactory::new()->create();

    put(route('contacts.update', $contact), [
        'email' => '',
        'first_name' => '',
        'last_name' => '',
    ])
        ->assertSessionHasErrors(['email', 'first_name', 'last_name']);
});

test('updating nonexistent contact returns 404', function () {
    put(route('contacts.update', 999999), [
        'email' => 'test@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
    ])
        ->assertNotFound();
});
