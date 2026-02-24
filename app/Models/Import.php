<?php

namespace App\Models;

use App\Enums\ImportStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property ImportStatus $status
 * @property string $original_filename
 * @property string $file_path
 * @property string $mime_type
 * @property int|null $total_records
 * @property int $imported_count
 * @property int $duplicates_count
 * @property int $invalid_count
 * @property float|null $processing_time_seconds
 * @property string|null $error_message
 * @property CarbonInterface|null $started_at
 * @property CarbonInterface|null $completed_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property ImportChunk[] $chunks
 * @property ImportIssue[] $issues
 */
class Import extends Model
{
    protected $fillable = [
        'status',
        'original_filename',
        'file_path',
        'mime_type',
        'total_records',
        'imported_count',
        'duplicates_count',
        'invalid_count',
        'processing_time_seconds',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ImportStatus::class,
            'total_records' => 'integer',
            'imported_count' => 'integer',
            'duplicates_count' => 'integer',
            'invalid_count' => 'integer',
            'processing_time_seconds' => 'float',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<ImportChunk, $this>
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(ImportChunk::class);
    }

    /**
     * @return HasMany<ImportIssue, $this>
     */
    public function issues(): HasMany
    {
        return $this->hasMany(ImportIssue::class);
    }
}
