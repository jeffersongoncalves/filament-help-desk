<?php

use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource\Pages\ListTickets;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource\Pages\ViewTicket;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\DepartmentFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\TicketFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = UserFactory::new()->create();

    filament()->setCurrentPanel(filament()->getPanel('admin'));

    $this->actingAs($this->admin);

    $this->department = DepartmentFactory::new()->create();
});

it('filters the ticket list by company', function () {
    $acme = TicketFactory::new()->create([
        'department_id' => $this->department->id,
        'user_type' => User::class,
        'user_id' => UserFactory::new()->create()->id,
        'company_id' => 'acme',
    ]);

    $globex = TicketFactory::new()->create([
        'department_id' => $this->department->id,
        'user_type' => User::class,
        'user_id' => UserFactory::new()->create()->id,
        'company_id' => 'globex',
    ]);

    livewire(ListTickets::class)
        ->filterTable('company_id', 'acme')
        ->assertCanSeeTableRecords([$acme])
        ->assertCanNotSeeTableRecords([$globex]);
});

it('labels the company by the name the ticket carries, falling back to the id', function () {
    $named = TicketFactory::new()->create([
        'department_id' => $this->department->id,
        'user_type' => User::class,
        'user_id' => UserFactory::new()->create()->id,
        'company_id' => '4',
        'metadata' => ['company' => ['name' => 'Uberaba Imóveis']],
    ]);

    // Created before laravel-help-desk 1.13: no name was copied.
    TicketFactory::new()->create([
        'department_id' => $this->department->id,
        'user_type' => User::class,
        'user_id' => UserFactory::new()->create()->id,
        'company_id' => '7',
    ]);

    $filter = collect(ListTickets::getResource()::getTicketTableFilters(showCompany: true))
        ->first(fn ($filter): bool => $filter->getName() === 'company_id');

    expect($filter->getOptions())->toBe(['4' => 'Uberaba Imóveis', '7' => '7']);

    livewire(ListTickets::class)
        ->assertTableColumnStateSet('company_name', 'Uberaba Imóveis', $named);

    livewire(ViewTicket::class, ['record' => $named->uuid])
        ->assertSee('Uberaba Imóveis');
});

it('hides the company column and filter on a single-company install', function () {
    TicketFactory::new()->create([
        'department_id' => $this->department->id,
        'user_type' => User::class,
        'user_id' => UserFactory::new()->create()->id,
        'company_id' => null,
    ]);

    $columns = collect(ListTickets::getResource()::getTicketTableColumns(showUser: true, showCompany: true))
        ->map(fn ($column): string => $column->getName());

    $filters = collect(ListTickets::getResource()::getTicketTableFilters(showCompany: true))
        ->map(fn ($filter): string => $filter->getName());

    expect($columns)->not->toContain('company_name')
        ->and($filters)->not->toContain('company_id');
});
