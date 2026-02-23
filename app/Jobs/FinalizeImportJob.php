<?php

namespace App\Jobs;

namespace App\Jobs;

use App\Models\Import;
use App\Services\Import\ImportFinalizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class FinalizeImportJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly Import $import,
    ) {
        $this->onQueue('import-finalize');
    }

    public function handle(ImportFinalizer $finalizer): void
    {
        $fs = Storage::disk(config('import.disk'));

        $finalizer->finalize($this->import, $fs);
    }
}
