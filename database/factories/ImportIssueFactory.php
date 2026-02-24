<?php

namespace Database\Factories;

use App\Enums\IssueType;
use App\Models\ImportIssue;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImportIssueFactory extends Factory
{
    protected $model = ImportIssue::class;

    public function definition(): array
    {
        return [
            'import_id' => ImportFactory::new(),
            'type' => $this->faker->randomElement(IssueType::cases()),
            'email' => $this->faker->email(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'reason' => $this->faker->sentence(),
            'created_at' => now(),
        ];
    }

    public function duplicate(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => IssueType::Duplicate,
                'reason' => 'Email already exists in database.',
            ];
        });
    }

    public function invalid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => IssueType::Invalid,
                'reason' => $this->faker->randomElement([
                    'Invalid email format.',
                    'Missing required field.',
                    'Email is too long.',
                ]),
            ];
        });
    }
}
