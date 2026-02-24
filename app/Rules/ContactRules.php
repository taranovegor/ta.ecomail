<?php

namespace App\Rules;

use Illuminate\Validation\Rule;

readonly class ContactRules
{
    public static function base(): array
    {
        return [
            'email' => ['required', 'string', 'max:255', 'email:rfc'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
        ];
    }

    public static function store(): array
    {
        $base = self::base();
        $base['email'][] = 'unique:contacts,email';

        return $base;
    }

    public static function update(mixed $id): array
    {
        $base = self::base();
        $base['email'][] = Rule::unique('contacts', 'email')->ignore($id);

        return $base;
    }
}
