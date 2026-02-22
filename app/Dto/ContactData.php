<?php

namespace App\Dto;

readonly class ContactData
{
    public function __construct(
        public string $email,
        public string $firstName,
        public string $lastName,
    ) {}

    /**
     * Create a ContactData object from a FormRequest.
     *
     * @param  array{email: string, first_name: string, last_name: string}  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            email: $data['email'],
            firstName: $data['first_name'],
            lastName: $data['last_name'],
        );
    }

    /**
     * Convert the DTO to an array for entity creation.
     *
     * @return array{email: string, first_name: string, last_name: string}
     */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
        ];
    }
}
