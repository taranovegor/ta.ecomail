<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreImportRequest extends FormRequest
{
    public function rules(): array
    {
        $maxSizeKb = config('import.max_file_size_kb', 51200);

        return [
            'file' => [
                'required',
                'file',
                'mimetypes:text/xml,application/xml',
                "max:{$maxSizeKb}",
            ],
        ];
    }
}
