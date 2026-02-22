<?php

use App\Models\Contact;
use Database\Factories\ContactFactory;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    Contact::disableSearchSyncing();
});

test('contact can be created with valid data', function () {
    $data = [
        'email' => 'ivan.ivanov@example.com',
        'first_name' => 'Ivan',
        'last_name' => 'Ivanov',
    ];

    post(route('contacts.store'), $data)
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('contacts', $data);
});

test('contact creation redirects to show page', function () {
    $data = [
        'email' => 'ivan.ivanov@example.com',
        'first_name' => 'Ivan',
        'last_name' => 'Ivanov',
    ];

    $response = post(route('contacts.store'), $data);

    $contact = Contact::where('email', 'ivan.ivanov@example.com')->first();

    $response->assertRedirectToRoute('contacts.show', $contact);
});

test('email is required', function () {
    post(route('contacts.store'), [
        'email' => '',
        'first_name' => 'Ivan',
        'last_name' => 'Ivanov',
    ])
        ->assertSessionHasErrors('email');

    $this->assertDatabaseCount('contacts', 0);
});

test('first_name is required', function () {
    post(route('contacts.store'), [
        'email' => 'ivan.ivanov@example.com',
        'first_name' => '',
        'last_name' => 'Ivanov',
    ])
        ->assertSessionHasErrors('first_name');
});

test('last_name is required', function () {
    post(route('contacts.store'), [
        'email' => 'ivan.ivanov@example.com',
        'first_name' => 'Ivan',
        'last_name' => '',
    ])
        ->assertSessionHasErrors('last_name');
});

test('email must be valid rfc format', function (string $invalidEmail) {
    post(route('contacts.store'), [
        'email' => $invalidEmail,
        'first_name' => 'Ivan',
        'last_name' => 'Ivanov',
    ])->assertSessionHasErrors('email');

    $this->assertDatabaseCount('contacts', 0);
})->with([
    'missing @' => ['not-an-email'],
    'missing domain' => ['user@'],
    'missing local part' => ['@domain.com'],
    'spaces' => ['user @domain.com'],
    'double dots' => ['user@domain..com'],
    'email exceeds max length (255 chars)' => [
        substr(str_repeat('a', 255).'@example.com', -255),
    ],
]);

test('email must be unique', function () {
    ContactFactory::new()->create(['email' => 'ivan.ivanov@example.com']);

    post(route('contacts.store'), [
        'email' => 'ivan.ivanov@example.com',
        'first_name' => 'Ivan',
        'last_name' => 'Ivanov',
    ])
        ->assertSessionHasErrors('email');

    $this->assertDatabaseCount('contacts', 1);
});

test('first_name and last_name max length is 255', function () {
    post(route('contacts.store'), [
        'email' => 'ivan.ivanov@example.com',
        'first_name' => str_repeat('a', 256),
        'last_name' => 'Ivanov',
    ])
        ->assertSessionHasErrors('first_name');

    post(route('contacts.store'), [
        'email' => 'ivan.ivanov@example.com',
        'first_name' => 'Ivan',
        'last_name' => str_repeat('a', 256),
    ])
        ->assertSessionHasErrors('last_name');
});

test('old input is preserved on validation error', function () {
    post(route('contacts.store'), [
        'email' => 'invalid',
        'first_name' => 'Ivan',
        'last_name' => 'Ivanov',
    ])
        ->assertSessionHasErrors('email');

    get(route('contacts.create'))
        ->assertOk();
});
