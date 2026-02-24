<?php

namespace App\Http\Requests;

use App\Rules\ContactRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateContactRequest extends FormRequest
{
    public function rules(): array
    {
        return ContactRules::update($this->route('contact'));
    }
}
