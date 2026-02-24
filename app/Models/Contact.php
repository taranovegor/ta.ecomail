<?php

namespace App\Models;

use App\Services\EmailNormalizer;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $email
 * @property string $first_name
 * @property string $last_name
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
class Contact extends Model
{
    use Searchable;

    protected $fillable = [
        'email',
        'first_name',
        'last_name',
    ];

    public function toSearchableArray(): array
    {
        return [
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
        ];
    }

    protected function email(): Attribute
    {
        return Attribute::make(set: fn (string $value): string => EmailNormalizer::normalize($value));
    }
}
