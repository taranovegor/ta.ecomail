<?php

namespace App\Services;

use App\Contracts\ContactSearchInterface;
use App\Dto\ContactData;
use App\Models\Contact;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ContactService
{
    public function __construct(
        private readonly ContactSearchInterface $search,
    ) {}

    public function list(?string $searchQuery, int $perPage = 10): LengthAwarePaginator
    {
        if ($searchQuery !== null && $searchQuery !== '') {
            return $this->search->search($searchQuery, $perPage);
        }

        return Contact::query()
            ->latest()
            ->paginate($perPage);
    }

    public function create(ContactData $data): Contact
    {
        return Contact::create($data->toArray());
    }

    public function update(Contact $contact, ContactData $data): Contact
    {
        $contact->update($data->toArray());

        return $contact;
    }

    public function delete(Contact $contact): void
    {
        $contact->delete();
    }
}
