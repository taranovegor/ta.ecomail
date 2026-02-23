<?php

namespace App\Services;

use App\Enums\ImportStatus;
use App\Exceptions\UnsupportedFileFormatException;
use App\Jobs\SplitImportJob;
use App\Models\Import;
use App\Services\Import\ContactImporterResolver;
use Illuminate\Http\UploadedFile;

class ImportService
{
    public function __construct(
        private readonly ContactImporterResolver $resolver,
    ) {}

    public function initiate(UploadedFile $file): Import
    {
        $mimeType = $file->getMimeType();
        if (! $this->resolver->resolvable($mimeType)) {
            throw new UnsupportedFileFormatException("Unsupported file format: {$mimeType}");
        }

        $path = $file->store('imports', config('import.disk'));

        $import = Import::create([
            'status' => ImportStatus::Pending,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $mimeType,
        ]);

        dispatch(new SplitImportJob($import));

        return $import;
    }
}
