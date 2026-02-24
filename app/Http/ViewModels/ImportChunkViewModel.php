<?php

namespace App\Http\ViewModels;

use App\Models\ImportChunk;
use Carbon\CarbonInterface;

class ImportChunkViewModel
{
    public function __construct(
        public ImportChunk $chunk,
    ) {}

    public function id(): int
    {
        return $this->chunk->id;
    }

    public function importId(): int
    {
        return $this->chunk->import_id;
    }

    public function chunkNumber(): int
    {
        return $this->chunk->chunk_number;
    }

    public function filePath(): string
    {
        return $this->chunk->file_path;
    }

    public function status(): string
    {
        return $this->chunk->status->value;
    }

    public function total(): int
    {
        return $this->chunk->total;
    }

    public function imported(): int
    {
        return $this->chunk->imported;
    }

    public function duplicates(): int
    {
        return $this->chunk->duplicates;
    }

    public function invalid(): int
    {
        return $this->chunk->invalid;
    }

    public function errorMessage(): ?string
    {
        return $this->chunk->error_message;
    }

    public function createdAt(): ?CarbonInterface
    {
        return $this->chunk->created_at;
    }

    public function updatedAt(): ?CarbonInterface
    {
        return $this->chunk->updated_at;
    }

    public function progressPercentage(): int
    {
        if ($this->chunk->total === 0) {
            return 0;
        }

        return (int) (($this->chunk->imported / $this->chunk->total) * 100);
    }

    public function successRate(): int
    {
        if ($this->chunk->total === 0) {
            return 0;
        }

        return (int) (($this->chunk->imported / $this->chunk->total) * 100);
    }
}
