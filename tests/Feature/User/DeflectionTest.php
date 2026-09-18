<?php

use JeffersonGoncalves\FilamentHelpDesk\Contracts\KnowledgeBaseProvider;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\DepartmentFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Fakes\FakeKnowledgeBaseProvider;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource\Pages\CreateTicket;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = UserFactory::new()->create();

    filament()->setCurrentPanel(filament()->getPanel('user'));

    $this->actingAs($this->user);

    FakeKnowledgeBaseProvider::$results = [];
    FakeKnowledgeBaseProvider::$lastQuery = null;
    FakeKnowledgeBaseProvider::$lastDepartmentId = null;
});

it('shows no deflection card when no provider is bound', function () {
    DepartmentFactory::new()->create();

    livewire(CreateTicket::class)
        ->assertSuccessful()
        ->assertDontSee(__('filament-help-desk::filament-help-desk.deflection.heading'));
});

it('shows the deflection card and searches the bound provider as the title is typed', function () {
    $department = DepartmentFactory::new()->create();

    app()->bind(KnowledgeBaseProvider::class, FakeKnowledgeBaseProvider::class);

    FakeKnowledgeBaseProvider::$results = [
        ['title' => 'Resetting your password', 'url' => 'https://example.com/kb/reset-password'],
    ];

    livewire(CreateTicket::class)
        ->fillForm([
            'department_id' => $department->id,
            'title' => 'I forgot my password',
        ])
        ->assertSuccessful()
        ->assertSee(__('filament-help-desk::filament-help-desk.deflection.heading'))
        ->assertSee('Resetting your password');

    expect(FakeKnowledgeBaseProvider::$lastQuery)->toBe('I forgot my password')
        ->and(FakeKnowledgeBaseProvider::$lastDepartmentId)->toBe($department->id);
});

it('shows the empty message when the provider finds nothing', function () {
    $department = DepartmentFactory::new()->create();

    app()->bind(KnowledgeBaseProvider::class, FakeKnowledgeBaseProvider::class);

    FakeKnowledgeBaseProvider::$results = [];

    livewire(CreateTicket::class)
        ->fillForm([
            'department_id' => $department->id,
            'title' => 'Something nobody wrote about',
        ])
        ->assertSuccessful()
        ->assertSee(__('filament-help-desk::filament-help-desk.deflection.empty'));
});

it('hides the deflection card until the requester types a title', function () {
    $department = DepartmentFactory::new()->create();

    app()->bind(KnowledgeBaseProvider::class, FakeKnowledgeBaseProvider::class);

    livewire(CreateTicket::class)
        ->fillForm(['department_id' => $department->id])
        ->assertSuccessful()
        ->assertDontSee(__('filament-help-desk::filament-help-desk.deflection.heading'));
});
