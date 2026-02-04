<?php

namespace JeffersonGoncalves\FilamentHelpDesk\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JeffersonGoncalves\HelpDesk\Models\EmailChannel;

class EmailChannelFactory extends Factory
{
    protected $model = EmailChannel::class;

    public function definition(): array
    {
        return [
            'department_id' => null,
            'name' => fake()->words(2, true),
            'driver' => 'imap',
            'email_address' => fake()->unique()->safeEmail(),
            'settings' => [],
            'is_active' => true,
        ];
    }
}
