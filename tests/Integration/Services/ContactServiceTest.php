<?php

use App\Contracts\ContactSearchInterface;
use App\Dto\ContactData;
use App\Models\Contact;
use App\Services\ContactService;
use Database\Factories\ContactFactory;
use Illuminate\Pagination\LengthAwarePaginator;

beforeEach(function () {
    Contact::disableSearchSyncing();

    $this->searchMock = Mockery::mock(ContactSearchInterface::class);
    $this->service = new ContactService($this->searchMock);
});

test('list returns paginated contacts without search', function () {
    ContactFactory::new()->count(5)->create();

    $result = $this->service->list(searchQuery: null, perPage: 10);

    expect($result)
        ->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->total())->toBe(5);
});

test('list delegates to search interface when search query provided', function () {
    $paginator = new LengthAwarePaginator([], 0, 10);

    $this->searchMock
        ->shouldReceive('search')
        ->once()
        ->with('henri', 10)
        ->andReturn($paginator);

    $result = $this->service->list(searchQuery: 'henri', perPage: 10);

    expect($result)->toBe($paginator);
});

test('list does not delegate to search for empty string', function () {
    ContactFactory::new()->count(3)->create();

    $this->searchMock->shouldNotReceive('search');

    $result = $this->service->list(searchQuery: '', perPage: 10);

    expect($result->total())->toBe(3);
});

test('create persists contact to database', function () {
    $data = new ContactData(
        email: 'new@example.com',
        firstName: 'New',
        lastName: 'Contact',
    );

    $contact = $this->service->create($data);

    expect($contact)
        ->toBeInstanceOf(Contact::class)
        ->and($contact->email)->toBe('new@example.com')
        ->and($contact->first_name)->toBe('New')
        ->and($contact->last_name)->toBe('Contact')
        ->and($contact->exists)->toBeTrue();
});

test('update modifies existing contact', function () {
    $contact = ContactFactory::new()->create([
        'email' => 'old@example.com',
        'first_name' => 'Old',
        'last_name' => 'Name',
    ]);

    $data = new ContactData(
        email: 'new@example.com',
        firstName: 'New',
        lastName: 'Name',
    );

    $updated = $this->service->update($contact, $data);

    expect($updated->email)->toBe('new@example.com')
        ->and($updated->first_name)->toBe('New');

    $this->assertDatabaseHas('contacts', [
        'id' => $contact->id,
        'email' => 'new@example.com',
    ]);
});

test('delete removes contact from database', function () {
    $contact = ContactFactory::new()->create();

    $this->service->delete($contact);

    $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
});
