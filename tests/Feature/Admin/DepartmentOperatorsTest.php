<?php

use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\DepartmentResource\Pages\EditDepartment;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\DepartmentResource\RelationManagers\OperatorsRelationManager;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\DepartmentFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;

use function Pest\Livewire\livewire;

beforeEach(function () {
    filament()->setCurrentPanel(filament()->getPanel('admin'));
    $this->actingAs(UserFactory::new()->create());

    $this->department = DepartmentFactory::new()->create();
    $this->operator = UserFactory::new()->create();
    $this->department->operators()->attach($this->operator->id, ['role' => 'operator']);
});

function emailEnabled($department, $operator): bool
{
    return (bool) $department->operators()->whereKey($operator->id)->first()->pivot->email_notifications;
}

it('toggles an operator email notifications for the department', function () {
    expect(emailEnabled($this->department, $this->operator))->toBeTrue();

    $component = livewire(OperatorsRelationManager::class, [
        'ownerRecord' => $this->department,
        'pageClass' => EditDepartment::class,
    ]);

    $component->callTableAction('toggleEmailNotifications', $this->operator);
    expect(emailEnabled($this->department, $this->operator))->toBeFalse();

    $component->callTableAction('toggleEmailNotifications', $this->operator);
    expect(emailEnabled($this->department, $this->operator))->toBeTrue();
});
