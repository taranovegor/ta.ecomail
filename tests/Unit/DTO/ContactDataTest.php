<?php

use App\Dto\ContactData;

test('creates from constructor', function () {
    $dto = new ContactData(
        email: 'john@example.com',
        firstName: 'John',
        lastName: 'Doe',
    );

    expect($dto->email)->toBe('john@example.com')
        ->and($dto->firstName)->toBe('John')
        ->and($dto->lastName)->toBe('Doe');
});

test('creates from request array', function () {
    $dto = ContactData::fromRequest([
        'email' => 'john@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    expect($dto->email)->toBe('john@example.com')
        ->and($dto->firstName)->toBe('John')
        ->and($dto->lastName)->toBe('Doe');
});

test('converts to array with snake_case keys', function () {
    $dto = new ContactData(
        email: 'john@example.com',
        firstName: 'John',
        lastName: 'Doe',
    );

    expect($dto->toArray())->toBe([
        'email' => 'john@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
});
