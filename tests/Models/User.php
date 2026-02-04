<?php

namespace JeffersonGoncalves\FilamentHelpDesk\Tests\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory;

    protected $table = 'users';

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    protected static function newFactory(): \JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory
    {
        return \JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory::new();
    }
}
