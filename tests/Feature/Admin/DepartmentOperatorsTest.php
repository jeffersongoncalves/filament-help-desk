<?php

use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\DepartmentResource\Pages\EditDepartment;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\DepartmentResource\RelationManagers\OperatorsRelationManager;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\DepartmentFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\HelpDesk\Models\Department;

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

it('qualifies the pivot with the help desk database when it differs from the operator one', function () {
    config()->set('database.connections.helpdesk', ['driver' => 'mysql', 'database' => 'helpdesk_db', 'prefix' => '']);
    config()->set('help-desk.connection', 'helpdesk');

    $department = new Department;

    expect($department->operators()->getTable())->toBe('helpdesk_db.help_desk_department_operator')
        ->and($this->department->setConnection('testing')->operators()->getTable())->toBe('help_desk_department_operator');

    // The package migrations roll back on the help desk connection at teardown.
    config()->set('help-desk.connection', null);
});

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
