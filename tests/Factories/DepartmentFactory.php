<?php

namespace JeffersonGoncalves\FilamentHelpDesk\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use JeffersonGoncalves\HelpDesk\Models\Department;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'email' => fake()->safeEmail(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
