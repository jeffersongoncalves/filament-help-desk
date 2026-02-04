<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */
    'navigation' => [
        'tickets' => 'Tickets',
        'departments' => 'Departments',
        'categories' => 'Categories',
        'canned_responses' => 'Canned Responses',
        'email_channels' => 'Email Channels',
    ],

    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
    'fields' => [
        'reference_number' => 'Reference',
        'title' => 'Title',
        'description' => 'Description',
        'status' => 'Status',
        'priority' => 'Priority',
        'department' => 'Department',
        'category' => 'Category',
        'assigned_to' => 'Assigned To',
        'requester' => 'Requester',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
        'closed_at' => 'Closed At',
        'due_at' => 'Due Date',
        'source' => 'Source',
        'attachments' => 'Attachments',
        'name' => 'Name',
        'slug' => 'Slug',
        'email' => 'Email',
        'email_address' => 'Email Address',
        'is_active' => 'Active',
        'sort_order' => 'Sort Order',
        'parent' => 'Parent Category',
        'body' => 'Content',
        'driver' => 'Driver',
        'settings' => 'Settings',
        'last_polled_at' => 'Last Polled',
        'last_error' => 'Last Error',
        'role' => 'Role',
        'is_internal' => 'Internal Note',
    ],

    /*
    |--------------------------------------------------------------------------
    | Statuses
    |--------------------------------------------------------------------------
    */
    'statuses' => [
        'open' => 'Open',
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'on_hold' => 'On Hold',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ],

    /*
    |--------------------------------------------------------------------------
    | Priorities
    |--------------------------------------------------------------------------
    */
    'priorities' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */
    'actions' => [
        'create_ticket' => 'Create Ticket',
        'view_ticket' => 'View Ticket',
        'edit_ticket' => 'Edit Ticket',
        'close_ticket' => 'Close Ticket',
        'reopen_ticket' => 'Reopen Ticket',
        'assign_to_me' => 'Assign to Me',
        'assign' => 'Assign',
        'unassign' => 'Unassign',
        'change_status' => 'Change Status',
        'change_priority' => 'Change Priority',
        'submit_reply' => 'Submit Reply',
        'use_canned_response' => 'Use Canned Response',
        'add_comment' => 'Add Comment',
        'delete' => 'Delete',
        'restore' => 'Restore',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tabs
    |--------------------------------------------------------------------------
    */
    'tabs' => [
        'all' => 'All',
        'my_tickets' => 'My Tickets',
        'unassigned' => 'Unassigned',
        'open' => 'Open',
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ],

    /*
    |--------------------------------------------------------------------------
    | Comments
    |--------------------------------------------------------------------------
    */
    'comments' => [
        'reply' => 'Reply',
        'note' => 'Note',
        'internal_note' => 'Internal Note',
        'system' => 'System',
        'empty' => 'No comments yet.',
        'reply_placeholder' => 'Write your reply...',
        'internal_note_help' => 'Internal notes are only visible to operators and admins.',
        'submitted' => 'Comment submitted successfully.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Widgets
    |--------------------------------------------------------------------------
    */
    'widgets' => [
        'open_tickets' => 'Open Tickets',
        'pending_tickets' => 'Pending Tickets',
        'resolved_tickets' => 'Resolved Tickets',
        'total_tickets' => 'Total Tickets',
        'unassigned_tickets' => 'Unassigned Tickets',
        'overdue_tickets' => 'Overdue Tickets',
        'tickets_by_status' => 'Tickets by Status',
        'tickets_by_priority' => 'Tickets by Priority',
        'my_assigned_tickets' => 'My Assigned Tickets',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'ticket_created' => 'Ticket created successfully.',
        'ticket_updated' => 'Ticket updated successfully.',
        'ticket_closed' => 'Ticket closed.',
        'ticket_reopened' => 'Ticket reopened.',
        'ticket_assigned' => 'Ticket assigned successfully.',
        'status_changed' => 'Status changed successfully.',
        'priority_changed' => 'Priority changed successfully.',
        'comment_added' => 'Comment added successfully.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Placeholders
    |--------------------------------------------------------------------------
    */
    'placeholders' => [
        'unassigned' => 'Unassigned',
        'no_category' => 'N/A',
        'no_department' => 'N/A',
        'all_departments' => 'All Departments',
        'select_department' => 'Select a department',
        'select_category' => 'Select a category',
        'select_priority' => 'Select a priority',
        'select_status' => 'Select a status',
        'select_operator' => 'Select an operator',
        'select_canned_response' => 'Select a canned response',
    ],

    /*
    |--------------------------------------------------------------------------
    | Operators Relation
    |--------------------------------------------------------------------------
    */
    'operators' => [
        'label' => 'Operators',
        'roles' => [
            'operator' => 'Operator',
            'manager' => 'Manager',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Drivers
    |--------------------------------------------------------------------------
    */
    'drivers' => [
        'imap' => 'IMAP',
        'mailgun' => 'Mailgun',
        'sendgrid' => 'SendGrid',
        'resend' => 'Resend',
        'postmark' => 'Postmark',
    ],

];
