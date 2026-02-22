<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexContactRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
