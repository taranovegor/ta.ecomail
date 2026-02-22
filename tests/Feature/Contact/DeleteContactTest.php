<?php

use App\Models\Contact;
use Database\Factories\ContactFactory;

use function Pest\Laravel\delete;

beforeEach(function () {
    Contact::disableSearchSyncing();
});

test('contact can be deleted', function () {
    $contact = ContactFactory::new()->create();

    delete(route('contacts.destroy', $contact))
        ->assertRedirectToRoute('contacts.index')
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    $this->assertDatabaseCount('contacts', 0);
});

test('deleting nonexistent contact returns 404', function () {
    delete(route('contacts.destroy', 999999))
        ->assertNotFound();
});

test('only targeted contact is deleted', function () {
    $contacts = ContactFactory::new()->count(3)->create();
    $toDelete = $contacts->first();
    $this->assertInstanceOf(Contact::class, $toDelete);

    delete(route('contacts.destroy', $toDelete));

    $this->assertDatabaseCount('contacts', 2);
    $this->assertDatabaseMissing('contacts', ['id' => $toDelete->id]);
});
