<?php

namespace JeffersonGoncalves\FilamentHelpDesk\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Models\User;
use JeffersonGoncalves\HelpDesk\Enums\TicketPriority;
use JeffersonGoncalves\HelpDesk\Enums\TicketStatus;
use JeffersonGoncalves\HelpDesk\Models\Ticket;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'reference_number' => 'HD-' . str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'department_id' => DepartmentFactory::new(),
            'category_id' => null,
            'user_type' => User::class,
            'user_id' => UserFactory::new(),
            'assigned_to_type' => null,
            'assigned_to_id' => null,
            'title' => fake()->sentence(),
            'description' => fake()->paragraphs(2, true),
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::Medium,
            'source' => 'web',
        ];
    }

    public function withUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => User::class,
            'user_id' => $user->id,
        ]);
    }

    public function assignedTo(User $operator): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to_type' => User::class,
            'assigned_to_id' => $operator->id,
        ]);
    }
}
