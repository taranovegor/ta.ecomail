<?php

namespace App\Http\Requests;

use App\Rules\ContactRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    public function rules(): array
    {
        return ContactRules::store();
    }
}
