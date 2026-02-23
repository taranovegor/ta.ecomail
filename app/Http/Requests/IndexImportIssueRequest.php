<?php

namespace App\Http\Requests;

use App\Enums\IssueType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexImportIssueRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'nullable', Rule::enum(IssueType::class)],
        ];
    }
}
