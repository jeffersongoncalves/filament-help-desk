<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Navegação
    |--------------------------------------------------------------------------
    */
    'navigation' => [
        'tickets' => 'Tickets',
        'departments' => 'Departamentos',
        'categories' => 'Categorias',
        'canned_responses' => 'Respostas Prontas',
        'email_channels' => 'Canais de Email',
    ],

    /*
    |--------------------------------------------------------------------------
    | Labels de Recursos
    |--------------------------------------------------------------------------
    */
    'resource' => [
        'department' => [
            'navigation_label' => 'Departamentos',
            'model_label' => 'Departamento',
            'plural_model_label' => 'Departamentos',
            'relation_managers' => [
                'operators' => 'Operadores',
            ],
        ],
        'category' => [
            'navigation_label' => 'Categorias',
            'model_label' => 'Categoria',
            'plural_model_label' => 'Categorias',
        ],
        'canned_response' => [
            'navigation_label' => 'Respostas Prontas',
            'model_label' => 'Resposta Pronta',
            'plural_model_label' => 'Respostas Prontas',
        ],
        'email_channel' => [
            'navigation_label' => 'Canais de Email',
            'model_label' => 'Canal de Email',
            'plural_model_label' => 'Canais de Email',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Campos
    |--------------------------------------------------------------------------
    */
    'fields' => [
        'reference_number' => 'Referência',
        'title' => 'Título',
        'description' => 'Descrição',
        'status' => 'Status',
        'priority' => 'Prioridade',
        'department' => 'Departamento',
        'category' => 'Categoria',
        'assigned_to' => 'Atribuído a',
        'requester' => 'Solicitante',
        'created_at' => 'Criado em',
        'updated_at' => 'Atualizado em',
        'closed_at' => 'Fechado em',
        'due_at' => 'Data Limite',
        'source' => 'Origem',
        'attachments' => 'Anexos',
        'name' => 'Nome',
        'slug' => 'Slug',
        'email' => 'Email',
        'email_address' => 'Endereço de Email',
        'is_active' => 'Ativo',
        'sort_order' => 'Ordem',
        'parent_category' => 'Categoria Pai',
        'body' => 'Conteúdo',
        'driver' => 'Driver',
        'settings' => 'Configurações',
        'last_polled_at' => 'Última Verificação',
        'last_error' => 'Último Erro',
        'role' => 'Função',
        'is_internal' => 'Nota Interna',
        'internal_note' => 'Nota Interna',
        'reply' => 'Resposta',
        'tickets_count' => 'Tickets',
        'setting_key' => 'Chave',
        'setting_value' => 'Valor',
        'operator' => 'Operador',
    ],

    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */
    'statuses' => [
        'open' => 'Aberto',
        'pending' => 'Pendente',
        'in_progress' => 'Em Andamento',
        'on_hold' => 'Em Espera',
        'resolved' => 'Resolvido',
        'closed' => 'Fechado',
    ],

    /*
    |--------------------------------------------------------------------------
    | Prioridades
    |--------------------------------------------------------------------------
    */
    'priorities' => [
        'low' => 'Baixa',
        'medium' => 'Média',
        'high' => 'Alta',
        'urgent' => 'Urgente',
    ],

    /*
    |--------------------------------------------------------------------------
    | Enums
    |--------------------------------------------------------------------------
    */
    'enums' => [
        'department_role' => [
            'operator' => 'Operador',
            'manager' => 'Gerente',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ações
    |--------------------------------------------------------------------------
    */
    'actions' => [
        'create_ticket' => 'Criar Ticket',
        'view_ticket' => 'Ver Ticket',
        'edit_ticket' => 'Editar Ticket',
        'close_ticket' => 'Fechar Ticket',
        'reopen_ticket' => 'Reabrir Ticket',
        'assign_to_me' => 'Atribuir a Mim',
        'assign' => 'Atribuir',
        'unassign' => 'Desatribuir',
        'change_status' => 'Alterar Status',
        'change_priority' => 'Alterar Prioridade',
        'submit_reply' => 'Enviar Resposta',
        'use_canned_response' => 'Usar Resposta Pronta',
        'add_comment' => 'Adicionar Comentário',
        'delete' => 'Excluir',
        'restore' => 'Restaurar',
    ],

    /*
    |--------------------------------------------------------------------------
    | Abas
    |--------------------------------------------------------------------------
    */
    'tabs' => [
        'all' => 'Todos',
        'my_tickets' => 'Meus Tickets',
        'unassigned' => 'Não Atribuídos',
        'open' => 'Abertos',
        'pending' => 'Pendentes',
        'in_progress' => 'Em Andamento',
        'on_hold' => 'Em Espera',
        'resolved' => 'Resolvidos',
        'closed' => 'Fechados',
    ],

    /*
    |--------------------------------------------------------------------------
    | Seções
    |--------------------------------------------------------------------------
    */
    'sections' => [
        'ticket_details' => 'Detalhes do Ticket',
    ],

    /*
    |--------------------------------------------------------------------------
    | Comentários
    |--------------------------------------------------------------------------
    */
    'comments' => [
        'reply' => 'Resposta',
        'note' => 'Nota',
        'internal_note' => 'Nota Interna',
        'system' => 'Sistema',
        'empty' => 'Nenhum comentário ainda.',
        'reply_placeholder' => 'Escreva sua resposta...',
        'internal_note_help' => 'Notas internas são visíveis apenas para operadores e administradores.',
        'submitted' => 'Comentário enviado com sucesso.',
        'ticket_closed_message' => 'Este ticket está atualmente :status. Reabra-o para adicionar novas respostas.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Widgets
    |--------------------------------------------------------------------------
    */
    'widgets' => [
        'admin_stats' => [
            'total_tickets' => 'Total de Tickets',
            'open_tickets' => 'Tickets Abertos',
            'unassigned_tickets' => 'Tickets Não Atribuídos',
            'overdue_tickets' => 'Tickets Atrasados',
            'trend_description' => ':trend em relação aos :period',
            'last_7_days' => 'últimos 7 dias',
        ],
        'user_stats' => [
            'open_tickets' => 'Tickets Abertos',
            'open_tickets_description' => 'Tickets aguardando resposta',
            'pending_tickets' => 'Tickets Pendentes',
            'pending_tickets_description' => 'Tickets em processamento',
            'resolved_tickets' => 'Tickets Resolvidos',
            'resolved_tickets_description' => 'Tickets resolvidos com sucesso',
            'total_tickets' => 'Total de Tickets',
            'total_tickets_description' => 'Todos os seus tickets',
        ],
        'tickets_by_status' => 'Tickets por Status',
        'tickets_by_priority' => [
            'heading' => 'Tickets por Prioridade',
            'dataset_label' => 'Tickets',
        ],
        'my_assigned_tickets' => 'Meus Tickets Atribuídos',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notificações
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'ticket_created' => 'Ticket criado com sucesso.',
        'ticket_updated' => 'Ticket atualizado com sucesso.',
        'ticket_closed' => 'Ticket fechado.',
        'ticket_reopened' => 'Ticket reaberto.',
        'ticket_assigned' => 'Ticket atribuído com sucesso.',
        'status_changed' => 'Status alterado com sucesso.',
        'priority_changed' => 'Prioridade alterada com sucesso.',
        'comment_added' => 'Comentário adicionado com sucesso.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Placeholders
    |--------------------------------------------------------------------------
    */
    'placeholders' => [
        'unassigned' => 'Não Atribuído',
        'no_category' => 'N/A',
        'no_department' => 'N/A',
        'all_departments' => 'Todos os Departamentos',
        'select_department' => 'Selecione um departamento',
        'select_category' => 'Selecione uma categoria',
        'select_priority' => 'Selecione uma prioridade',
        'select_status' => 'Selecione um status',
        'select_operator' => 'Selecione um operador',
        'select_canned_response' => 'Selecione uma resposta pronta',
        'root' => 'Raiz',
        'na' => 'N/A',
        'none' => 'Nenhum',
    ],

    /*
    |--------------------------------------------------------------------------
    | Relação de Operadores
    |--------------------------------------------------------------------------
    */
    'operators' => [
        'label' => 'Operadores',
        'roles' => [
            'operator' => 'Operador',
            'manager' => 'Gerente',
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
