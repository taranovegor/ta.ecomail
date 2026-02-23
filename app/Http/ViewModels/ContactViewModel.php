<?php

namespace App\Http\ViewModels;

use App\Models\Contact;
use Carbon\CarbonInterface;
use Stringable;

class ContactViewModel implements Stringable
{
    public function __construct(
        public Contact $contact,
    ) {}

    public function id(): int
    {
        return $this->contact->id;
    }

    public function email(): string
    {
        return $this->contact->email;
    }

    public function firstName(): string
    {
        return $this->contact->first_name;
    }

    public function lastName(): string
    {
        return $this->contact->last_name;
    }

    public function fullName(): string
    {
        return trim("{$this->contact->first_name} {$this->contact->last_name}");
    }

    public function createdAt(): CarbonInterface
    {
        return $this->contact->created_at;
    }

    public function updatedAt(): CarbonInterface
    {
        return $this->contact->updated_at;
    }

    public function __toString(): string
    {
        return (string) $this->contact->id;
    }
}
