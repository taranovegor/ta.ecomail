<?php

namespace Database\Factories;

use App\Enums\ImportStatus;
use App\Models\Import;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImportFactory extends Factory
{
    protected $model = Import::class;

    public function definition(): array
    {
        return [
            'status' => ImportStatus::Pending,
            'original_filename' => $this->faker->word().'.csv',
            'file_path' => 'imports/'.$this->faker->uuid().'.csv',
            'mime_type' => 'text/csv',
            'total_records' => $this->faker->numberBetween(10, 1000),
            'imported_count' => 0,
            'duplicates_count' => 0,
            'invalid_count' => 0,
            'processing_time_seconds' => null,
            'error_message' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $total = $attributes['total_records'];
            $imported = (int) ($total * 0.8);
            $duplicates = (int) ($total * 0.1);

            return [
                'status' => ImportStatus::Completed,
                'imported_count' => $imported,
                'duplicates_count' => $duplicates,
                'invalid_count' => $total - $imported - $duplicates,
                'processing_time_seconds' => $this->faker->randomFloat(2, 1, 120),
                'started_at' => now()->subHours(1),
                'completed_at' => now(),
            ];
        });
    }

    public function failed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => ImportStatus::Failed,
                'error_message' => $this->faker->sentence(),
                'started_at' => now()->subHours(1),
                'completed_at' => now(),
            ];
        });
    }
}
