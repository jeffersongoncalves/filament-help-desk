<?php

use Filament\Panel;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\CategoryResource\Pages\CreateCategory;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\CategoryResource\Pages\ListCategories;
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskAdminPlugin;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\DepartmentFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\HelpDesk\Models\Category;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = UserFactory::new()->create();

    $panel = Panel::make()
        ->default()
        ->id('admin')
        ->path('admin')
        ->login()
        ->plugin(FilamentHelpDeskAdminPlugin::make());

    filament()->registerPanel($panel);
    filament()->setCurrentPanel($panel);

    $this->actingAs($this->admin);
});

it('can render the category list page', function () {
    livewire(ListCategories::class)
        ->assertSuccessful();
});

it('can create a category', function () {
    $department = DepartmentFactory::new()->create();

    livewire(CreateCategory::class)
        ->fillForm([
            'department_id' => $department->id,
            'name' => 'Billing Issues',
            'slug' => 'billing-issues',
            'is_active' => true,
            'sort_order' => 0,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Category::query()->where('name', 'Billing Issues')->exists())->toBeTrue();
});
