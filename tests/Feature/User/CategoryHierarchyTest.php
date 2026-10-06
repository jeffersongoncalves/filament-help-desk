<?php

use Filament\Forms\Components\Select;
use JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource\Pages\EditTicket;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\CategoryFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\DepartmentFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\TicketFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Models\User;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages\CreateTicket;
use JeffersonGoncalves\HelpDesk\Models\Ticket;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->department = DepartmentFactory::new()->create();

    $category = fn (string $name, int $sort, ?int $parentId = null, bool $active = true) => CategoryFactory::new()->create([
        'department_id' => $this->department->id,
        'parent_id' => $parentId,
        'name' => $name,
        'sort_order' => $sort,
        'is_active' => $active,
    ]);

    $this->help = $category('Help', 1);
    $this->bug = $category('Found a bug', 2, $this->help->id);
    $this->login = $category('Login', 3, $this->bug->id);
    $this->idea = $category('Suggest an improvement', 4, $this->help->id);
    $this->billing = $category('Billing', 5);
    $this->retired = $category('Retired', 6, $this->billing->id, active: false);

    $this->user = UserFactory::new()->create();
    $this->actingAs($this->user);
});

it('groups child categories under their root and leaves parents unselectable', function () {
    filament()->setCurrentPanel(filament()->getPanel('user'));

    livewire(CreateTicket::class)
        ->fillForm(['department_id' => $this->department->id])
        ->assertFormFieldExists('category_id', fn (Select $field): bool => $field->getOptions() === [
            'Help' => [
                $this->login->id => 'Found a bug › Login',
                $this->idea->id => 'Suggest an improvement',
            ],
            // Its only child is inactive, so it stays a plain, selectable option.
            $this->billing->id => 'Billing',
        ]);
});

it('rejects a parent category on ticket creation', function () {
    filament()->setCurrentPanel(filament()->getPanel('user'));

    livewire(CreateTicket::class)
        ->fillForm([
            'title' => 'Parent pick',
            'description' => 'Should not be allowed.',
            'department_id' => $this->department->id,
            'category_id' => $this->help->id,
            'priority' => 'medium',
        ])
        ->call('create')
        ->assertHasFormErrors(['category_id']);

    expect(Ticket::query()->where('title', 'Parent pick')->exists())->toBeFalse();
});

it('accepts a leaf category on ticket creation', function () {
    filament()->setCurrentPanel(filament()->getPanel('user'));

    livewire(CreateTicket::class)
        ->fillForm([
            'title' => 'Leaf pick',
            'description' => 'Filed under a child.',
            'department_id' => $this->department->id,
            'category_id' => $this->idea->id,
            'priority' => 'medium',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Ticket::query()->where('title', 'Leaf pick')->value('category_id'))->toBe($this->idea->id);
});

it('keeps a legacy parent category selectable when editing that ticket', function () {
    filament()->setCurrentPanel(filament()->getPanel('operator'));

    $ticket = TicketFactory::new()->create([
        'department_id' => $this->department->id,
        'category_id' => $this->help->id,
        'user_type' => User::class,
        'user_id' => $this->user->id,
    ]);

    livewire(EditTicket::class, ['record' => $ticket->uuid])
        ->assertFormFieldExists('category_id', fn (Select $field): bool => $field->getOptions()[$this->help->id] === 'Help')
        ->call('save')
        ->assertHasNoFormErrors();
});
