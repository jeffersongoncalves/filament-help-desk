<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Concerns;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use JeffersonGoncalves\FilamentHelpDesk\Driver;
use JeffersonGoncalves\HelpDesk\Enums\TicketPriority;
use JeffersonGoncalves\HelpDesk\Enums\TicketStatus;
use JeffersonGoncalves\HelpDesk\Facades\HelpDesk;
use Symfony\Component\Mime\MimeTypes;

/**
 * Provides reusable Filament form schemas for ticket creation and editing.
 *
 * This trait defines static methods that return arrays of Filament form
 * components, allowing consistent form structures across User, Operator,
 * and Admin panels.
 */
trait HasTicketForm
{
    /**
     * Get the form schema for creating a new ticket.
     *
     * @param  bool  $isUser  When true, hides operator-only fields (assigned_to, status).
     * @return array<int, Component>
     */
    public static function getTicketFormSchema(bool $isUser = false): array
    {
        $schema = [
            TextInput::make('title')
                ->label(__('filament-help-desk::filament-help-desk.fields.title'))
                ->required()
                ->maxLength(255),

            RichEditor::make('description')
                ->label(__('filament-help-desk::filament-help-desk.fields.description'))
                ->required()
                ->columnSpanFull(),

            Select::make('department_id')
                ->label(__('filament-help-desk::filament-help-desk.fields.department'))
                ->options(fn (): array => HelpDesk::departments()
                    ->all()
                    ->pluck('name', 'id')
                    ->toArray()
                )
                ->required()
                ->searchable()
                ->live()
                ->afterStateUpdated(function (Set $set): void {
                    $set('category_id', null);
                }),

            Select::make('category_id')
                ->label(__('filament-help-desk::filament-help-desk.fields.category'))
                ->options(function (Get $get): array {
                    $departmentId = $get('department_id');

                    if (! $departmentId) {
                        return [];
                    }

                    return HelpDesk::departments()
                        ->categoriesFor((int) $departmentId)
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->searchable()
                ->visible(fn (Get $get): bool => filled($get('department_id'))),

            Select::make('priority')
                ->label(__('filament-help-desk::filament-help-desk.fields.priority'))
                ->options(
                    collect(TicketPriority::cases())
                        ->mapWithKeys(fn (TicketPriority $priority): array => [
                            $priority->value => $priority->label(),
                        ])
                        ->toArray()
                )
                ->default(TicketPriority::Medium->value)
                ->required(),

            FileUpload::make('attachments')
                ->label(__('filament-help-desk::filament-help-desk.fields.attachments'))
                ->multiple()
                ->disk(config('help-desk.ticket.attachment_disk', 'local'))
                ->directory(config('help-desk.ticket.attachment_path', 'help-desk/attachments'))
                ->acceptedFileTypes(
                    collect(config('help-desk.ticket.allowed_extensions', []))
                        ->flatMap(fn (string $ext): array => MimeTypes::getDefault()->getMimeTypes($ext))
                        ->unique()
                        ->values()
                        ->toArray()
                )
                ->maxSize(Driver::maxAttachmentSize())
                ->maxFiles(config('help-desk.ticket.max_attachments_per_comment', 5))
                ->columnSpanFull(),
        ];

        if ($isUser) {
            return $schema;
        }

        return array_merge($schema, [
            Select::make('assigned_to_id')
                ->label(__('filament-help-desk::filament-help-desk.fields.assigned_to'))
                ->options(function (): array {
                    $operatorModel = config('help-desk.models.operator');

                    return $operatorModel::query()->pluck('name', 'id')->toArray();
                })
                ->searchable()
                ->nullable(),

            Select::make('status')
                ->label(__('filament-help-desk::filament-help-desk.fields.status'))
                ->options(
                    collect(TicketStatus::cases())
                        ->mapWithKeys(fn (TicketStatus $status): array => [
                            $status->value => $status->label(),
                        ])
                        ->toArray()
                )
                ->default(TicketStatus::Open->value),
        ]);
    }

    /**
     * Get the form schema for editing an existing ticket.
     *
     * Includes all editable ticket fields plus the status selector.
     *
     * @return array<int, Component>
     */
    public static function getTicketEditFormSchema(): array
    {
        return [
            TextInput::make('title')
                ->label(__('filament-help-desk::filament-help-desk.fields.title'))
                ->required()
                ->maxLength(255),

            RichEditor::make('description')
                ->label(__('filament-help-desk::filament-help-desk.fields.description'))
                ->required()
                ->columnSpanFull(),

            Select::make('department_id')
                ->label(__('filament-help-desk::filament-help-desk.fields.department'))
                ->options(fn (): array => HelpDesk::departments()
                    ->all()
                    ->pluck('name', 'id')
                    ->toArray()
                )
                ->required()
                ->searchable()
                ->live()
                ->afterStateUpdated(function (Set $set): void {
                    $set('category_id', null);
                }),

            Select::make('category_id')
                ->label(__('filament-help-desk::filament-help-desk.fields.category'))
                ->options(function (Get $get): array {
                    $departmentId = $get('department_id');

                    if (! $departmentId) {
                        return [];
                    }

                    return HelpDesk::departments()
                        ->categoriesFor((int) $departmentId)
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->searchable(),

            Select::make('priority')
                ->label(__('filament-help-desk::filament-help-desk.fields.priority'))
                ->options(
                    collect(TicketPriority::cases())
                        ->mapWithKeys(fn (TicketPriority $priority): array => [
                            $priority->value => $priority->label(),
                        ])
                        ->toArray()
                )
                ->required(),

            Select::make('status')
                ->label(__('filament-help-desk::filament-help-desk.fields.status'))
                ->options(
                    collect(TicketStatus::cases())
                        ->mapWithKeys(fn (TicketStatus $status): array => [
                            $status->value => $status->label(),
                        ])
                        ->toArray()
                )
                ->required(),
        ];
    }
}
