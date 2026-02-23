<?php

namespace App\Models;

use App\Enums\ImportChunkStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $import_id
 * @property int $chunk_number
 * @property string $file_path
 * @property ImportChunkStatus $status
 * @property int $total
 * @property int $imported
 * @property int $duplicates
 * @property int $invalid
 * @property string|null $error_message
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property Import $import
 */
class ImportChunk extends Model
{
    protected $fillable = [
        'import_id',
        'chunk_number',
        'file_path',
        'status',
        'total',
        'imported',
        'duplicates',
        'invalid',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'status' => ImportChunkStatus::class,
            'chunk_number' => 'integer',
            'total' => 'integer',
            'imported' => 'integer',
            'duplicates' => 'integer',
            'invalid' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Import, $this>
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}
