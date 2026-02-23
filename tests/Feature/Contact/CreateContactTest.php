<?php

use App\Models\Contact;
use Database\Factories\ContactFactory;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    Contact::disableSearchSyncing();
});

test('contact can be created with valid data', function (string $validEmail) {
    $rawData = [
        'email' => $validEmail,
        'first_name' => 'Ivan',
        'last_name' => 'Ivanov',
    ];

    post(route('contacts.store'), $rawData)
        ->assertRedirect()
        ->assertSessionHas('success');

    $data = [
        'email' => strtolower(trim($validEmail)),
        'first_name' => 'Ivan',
        'last_name' => 'Ivanov',
    ];

    $this->assertDatabaseHas('contacts', $data);
})->with([
    'standard email' => 'ivan.ivanov@example.com',
    'email with plus addressing' => 'egor.smirnov+test@example-domain.com',
    /** @link https://en.wikipedia.org/wiki/Email_address#Examples */
    'case is always ignored after the @ and usually before' => 'FirstName.LastName@EasierReading.org',
    'space between the quotes' => '" "@example.org',
    'very very long email' => 'long.email-address-with-hyphens@and.subdomains.example.com',
    'cringe' => '"(),:;<>[\]\"!#$%&\'*+-/=?^_`{}|~"@weird.com',
    'very cringe email' => '"very.(),:;<>[]\".VERY.\"very@\\ \"very\".unusual"@strange.example.com',
    'Pv6 uses a different syntax' => 'postmaster@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370:7334]',
    'begin with underscore different syntax' => '_test@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370:7334]',
    'local domain name with no TLD, although ICANN highly discourages dotless email addresses' => 'admin@example',
]);

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
    'double dots' => ['user@domain..com'],
    'email exceeds max length (255 chars)' => [
        substr(str_repeat('a', 256).'@example.com', -256),
    ],
    /** @see https://en.wikipedia.org/wiki/Email_address#Examples */
    'quoted strings must be dot separated or be the only element making up the local-part' => 'just"not"right@example.com',
    /**
     * https://datatracker.ietf.org/doc/html/rfc5321#section-4.5.3.1.1
     *
     * @see \Egulias\EmailValidator\Warning\LocalTooLong
     *
     * This exception only works when strict is enabled, which will actually filter out valid emails (for example,
     * "very cringe email"). I couldn't quickly figure out the reason from the source code.
     */
    // 'local-part is longer than 64 characters' => str_repeat('a', 65).'@example.com',
    'underscore is not allowed in domain part' => 'i.like.underscores@but_they_are_not_allowed_in_this_part',
    'no @ character' => 'abc.example.com',
    'only one @ is allowed outside quotation marks' => 'a@b@c@example.com',
    'none of the special characters in this local-part are allowed outside quotation marks' => 'a"b(c)d,e:f;g<h>i[j\k]l@example.com',
    'spaces, quotes, and backslashes may only exist when within quoted strings and preceded by a backslash' => 'this is"not\allowed@example.com',
    'even if escaped (preceded by a backslash), spaces, quotes, and backslashes must still be contained by quotes' => 'this\ still\"not\\allowed@example.com',
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
