<?php

namespace App\Models;

use App\Enums\IssueType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $import_id
 * @property IssueType $type
 * @property string $email
 * @property string $first_name
 * @property string $last_name
 * @property string $reason
 * @property CarbonInterface $created_at
 * @property Import $import
 */
class ImportIssue extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'import_id',
        'type',
        'email',
        'first_name',
        'last_name',
        'reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => IssueType::class,
            'created_at' => 'datetime',
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
