<?php

use JeffersonGoncalves\FilamentHelpDesk\Admin\Widgets\OperatorWorkloadWidget;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Widgets\TicketsByCategoryWidget;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\CategoryFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\DepartmentFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\TicketFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\HelpDesk\Enums\TicketStatus;

beforeEach(function () {
    $this->admin = UserFactory::new()->create();

    filament()->setCurrentPanel(filament()->getPanel('admin'));

    $this->actingAs($this->admin);
});

function getWidgetData(string $widgetClass): array
{
    $method = new ReflectionMethod($widgetClass, 'getData');
    $method->setAccessible(true);

    return $method->invoke(new $widgetClass);
}

it('groups ticket counts by category in a single query', function () {
    $department = DepartmentFactory::new()->create();
    $support = CategoryFactory::new()->create(['department_id' => $department->id, 'name' => 'Support']);
    $billing = CategoryFactory::new()->create(['department_id' => $department->id, 'name' => 'Billing']);

    TicketFactory::new()->count(3)->create(['department_id' => $department->id, 'category_id' => $support->id]);
    TicketFactory::new()->count(2)->create(['department_id' => $department->id, 'category_id' => $billing->id]);
    TicketFactory::new()->create(['department_id' => $department->id, 'category_id' => null]);

    $data = getWidgetData(TicketsByCategoryWidget::class);

    $byLabel = array_combine($data['labels'], $data['datasets'][0]['data']);

    expect($byLabel)->toMatchArray([
        'Support' => 3,
        'Billing' => 2,
        __('filament-help-desk::filament-help-desk.placeholders.no_category') => 1,
    ]);
});

it('reports pending and resolved ticket counts per operator', function () {
    $department = DepartmentFactory::new()->create();
    $alice = UserFactory::new()->create(['name' => 'Alice']);
    $bob = UserFactory::new()->create(['name' => 'Bob']);

    TicketFactory::new()->count(2)->assignedTo($alice)->create([
        'department_id' => $department->id,
        'status' => TicketStatus::Pending,
    ]);
    TicketFactory::new()->assignedTo($alice)->create([
        'department_id' => $department->id,
        'status' => TicketStatus::Resolved,
    ]);
    TicketFactory::new()->assignedTo($bob)->create([
        'department_id' => $department->id,
        'status' => TicketStatus::Resolved,
    ]);
    // Open ticket, must not be counted in either bucket.
    TicketFactory::new()->assignedTo($alice)->create([
        'department_id' => $department->id,
        'status' => TicketStatus::Open,
    ]);

    $data = getWidgetData(OperatorWorkloadWidget::class);

    $labels = $data['labels'];
    $pending = array_combine($labels, $data['datasets'][0]['data']);
    $resolved = array_combine($labels, $data['datasets'][1]['data']);

    expect($pending)->toMatchArray(['Alice' => 2, 'Bob' => 0])
        ->and($resolved)->toMatchArray(['Alice' => 1, 'Bob' => 1]);
});
