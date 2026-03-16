<?php

return [

    /*
    |--------------------------------------------------------------------------
    | User Panel Configuration
    |--------------------------------------------------------------------------
    */
    'user' => [
        'resource' => \JeffersonGoncalves\FilamentHelpDesk\User\Resources\TicketResource::class,
        'widgets' => [
            \JeffersonGoncalves\FilamentHelpDesk\User\Widgets\UserTicketStatsWidget::class,
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
        'resource' => \JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource::class,
        'widgets' => [
            \JeffersonGoncalves\FilamentHelpDesk\Operator\Widgets\TicketsByStatusWidget::class,
            \JeffersonGoncalves\FilamentHelpDesk\Operator\Widgets\AssignedTicketsWidget::class,
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
            'ticket' => \JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\TicketResource::class,
            'department' => \JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\DepartmentResource::class,
            'category' => \JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\CategoryResource::class,
            'canned_response' => \JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\CannedResponseResource::class,
            'email_channel' => \JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\EmailChannelResource::class,
        ],
        'widgets' => [
            \JeffersonGoncalves\FilamentHelpDesk\Admin\Widgets\TicketsByPriorityWidget::class,
            \JeffersonGoncalves\FilamentHelpDesk\Admin\Widgets\TicketStatsOverviewWidget::class,
        ],
    ],

];
