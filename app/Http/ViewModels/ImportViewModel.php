<?php

namespace App\Http\ViewModels;

use App\Models\Import;
use Carbon\CarbonInterface;
use Stringable;

class ImportViewModel implements Stringable
{
    public function __construct(
        public Import $import,
    ) {}

    public function id(): int
    {
        return $this->import->id;
    }

    public function status(): string
    {
        return $this->import->status->value;
    }

    public function originalFilename(): string
    {
        return $this->import->original_filename;
    }

    public function filePath(): string
    {
        return $this->import->file_path;
    }

    public function mimeType(): string
    {
        return $this->import->mime_type;
    }

    public function totalRecords(): ?int
    {
        return $this->import->total_records;
    }

    public function importedCount(): int
    {
        return $this->import->imported_count;
    }

    public function duplicatesCount(): int
    {
        return $this->import->duplicates_count;
    }

    public function invalidCount(): int
    {
        return $this->import->invalid_count;
    }

    public function processingTimeSeconds(): ?float
    {
        return $this->import->processing_time_seconds;
    }

    public function errorMessage(): ?string
    {
        return $this->import->error_message;
    }

    public function startedAt(): ?CarbonInterface
    {
        return $this->import->started_at;
    }

    public function completedAt(): ?CarbonInterface
    {
        return $this->import->completed_at;
    }

    public function createdAt(): ?CarbonInterface
    {
        return $this->import->created_at;
    }

    public function updatedAt(): ?CarbonInterface
    {
        return $this->import->updated_at;
    }

    public function progressPercentage(): ?int
    {
        if ($this->import->total_records === 0 || $this->import->total_records === null) {
            return 0;
        }

        return (int) (($this->import->imported_count / $this->import->total_records) * 100);
    }

    public function isCompleted(): bool
    {
        return $this->import->completed_at !== null;
    }

    public function isProcessing(): bool
    {
        return $this->import->started_at !== null && $this->import->completed_at === null;
    }

    public function isFinished(): bool
    {
        return $this->import->status->isFinished();
    }

    public function __toString(): string
    {
        return (string) $this->import->id;
    }
}
