<?php

namespace JeffersonGoncalves\FilamentHelpDesk\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JeffersonGoncalves\HelpDesk\Models\CannedResponse;

class CannedResponseFactory extends Factory
{
    protected $model = CannedResponse::class;

    public function definition(): array
    {
        return [
            'department_id' => null,
            'title' => fake()->sentence(3),
            'body' => fake()->paragraphs(2, true),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
