<?php

use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\DepartmentFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\TicketFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Models\User;
use JeffersonGoncalves\HelpDesk\Services\TicketService;

it('stores the watcher identity snapshot', function () {
    // The test harness carries its own copy of the help desk schema. When the
    // core package adds a column, that copy has to follow or every test
    // touching the table dies on an insert — which is how a missing app_key
    // once took the whole suite down. laravel-help-desk 1.6 added metadata to
    // help_desk_ticket_watchers, the last table without one.
    $watcher = UserFactory::new()->create(['name' => 'Ada Lovelace']);

    $ticket = TicketFactory::new()->create([
        'department_id' => DepartmentFactory::new()->create()->id,
        'user_type' => User::class,
        'user_id' => UserFactory::new()->create()->id,
    ]);

    app(TicketService::class)->addWatcher($ticket, $watcher);

    expect($ticket->watchers()->firstOrFail()->watcher_name)->toBe('Ada Lovelace');
});
