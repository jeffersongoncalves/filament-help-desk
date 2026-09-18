<?php

use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\CannedResponseResource;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\CategoryResource;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\DepartmentResource;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\EmailChannelResource;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Widgets\OperatorWorkloadWidget;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Widgets\TicketsByCategoryWidget;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Widgets\TicketsByPriorityWidget;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Widgets\TicketStatsOverviewWidget;
use JeffersonGoncalves\FilamentHelpDesk\Operator\Widgets\AssignedTicketsWidget;
use JeffersonGoncalves\FilamentHelpDesk\Operator\Widgets\TicketsByStatusWidget;
use JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource;
use JeffersonGoncalves\FilamentHelpDesk\User\Widgets\UserTicketStatsWidget;

return [

    /*
    |--------------------------------------------------------------------------
    | User Panel Configuration
    |--------------------------------------------------------------------------
    */
    'user' => [
        'resource' => TicketResource::class,
        'widgets' => [
            UserTicketStatsWidget::class,
        ],
        'navigation_group' => 'Support',
        'navigation_icon' => 'heroicon-o-ticket',
        'navigation_sort' => null,
        'navigation_label' => null,
        'slug' => 'tickets',
    ],

    /*
    |--------------------------------------------------------------------------
    | Operator Panel Configuration
    |--------------------------------------------------------------------------
    */
    'operator' => [
        'resource' => JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource::class,
        'widgets' => [
            TicketsByStatusWidget::class,
            AssignedTicketsWidget::class,
        ],
        'navigation_group' => 'Help Desk',
        'navigation_icon' => 'heroicon-o-inbox-stack',
        'navigation_sort' => null,
        'navigation_label' => null,
        'slug' => 'tickets',
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Configuration
    |--------------------------------------------------------------------------
    */
    'admin' => [
        'navigation_group' => 'Help Desk',
        'navigation_icon' => 'heroicon-o-cog-6-tooth',
        'navigation_sort' => null,
        'resources' => [
            'ticket' => JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource::class,
            'department' => DepartmentResource::class,
            'category' => CategoryResource::class,
            'canned_response' => CannedResponseResource::class,
            'email_channel' => EmailChannelResource::class,
        ],
        'widgets' => [
            TicketsByPriorityWidget::class,
            TicketStatsOverviewWidget::class,
            TicketsByCategoryWidget::class,
            OperatorWorkloadWidget::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Knowledge Base (article deflection)
    |--------------------------------------------------------------------------
    |
    | Nested under its own key, rather than a single flat setting, so a
    | future default provider backed by the core package's own Knowledge
    | Base can flip `enabled` and `provider` without renaming either key.
    |
    | `provider` is a class implementing
    | JeffersonGoncalves\FilamentHelpDesk\Contracts\KnowledgeBaseProvider.
    */
    'knowledge_base' => [
        'enabled' => false,
        'provider' => null,
    ],

];
