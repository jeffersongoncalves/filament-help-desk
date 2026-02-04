<?php

use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\DepartmentResource\Pages\CreateDepartment;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\DepartmentResource\Pages\EditDepartment;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\DepartmentResource\Pages\ListDepartments;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\DepartmentFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\HelpDesk\Models\Department;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = UserFactory::new()->create();

    filament()->setCurrentPanel(filament()->getPanel('admin'));

    $this->actingAs($this->admin);
});

it('can render the department list page', function () {
    livewire(ListDepartments::class)
        ->assertSuccessful();
});

it('can create a department', function () {
    livewire(CreateDepartment::class)
        ->fillForm([
            'name' => 'Technical Support',
            'slug' => 'technical-support',
            'description' => 'Technical support department.',
            'email' => 'support@example.com',
            'is_active' => true,
            'sort_order' => 0,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Department::query()->where('name', 'Technical Support')->exists())->toBeTrue();
});

it('can edit a department', function () {
    $department = DepartmentFactory::new()->create();

    livewire(EditDepartment::class, [
        'record' => $department->getRouteKey(),
    ])
        ->assertSuccessful()
        ->fillForm([
            'name' => 'Updated Department Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $department->refresh();

    expect($department->name)->toBe('Updated Department Name');
});
