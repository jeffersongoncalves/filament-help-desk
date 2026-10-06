<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Concerns;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;
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
                ->options(fn (Get $get): array => static::getTicketCategoryOptions($get('department_id')))
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
                ->options(fn (Get $get, ?Model $record): array => static::getTicketCategoryOptions(
                    $get('department_id'),
                    keep: $record?->getAttribute('category_id'),
                ))
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

    /**
     * The categories a ticket may be filed under, grouped by their root
     * category: a parent becomes the group label and is not selectable
     * itself once it has an active child, so tickets land on the most
     * specific category. Filament option groups are one level deep, so a
     * grandchild sits in its root's group as a `Child › Grandchild` path.
     * A category with no active children stays selectable.
     *
     * `$keep` lets a ticket filed under a parent before this rule keep its
     * category on edit instead of failing validation.
     *
     * @return array<int|string, string|array<int, string>>
     */
    protected static function getTicketCategoryOptions(mixed $departmentId, mixed $keep = null): array
    {
        if (blank($departmentId)) {
            return [];
        }

        $categories = HelpDesk::departments()
            ->categoriesFor((int) $departmentId)
            ->keyBy('id');

        $parentIds = $categories->pluck('parent_id')->filter()->flip();
        $options = [];

        foreach ($categories as $category) {
            if ($parentIds->has($category->id) && $category->id !== (int) $keep) {
                continue;
            }

            // Walk up to the root, collecting the path below it. An inactive
            // parent is not in this list, so the walk stops beneath it.
            $path = [$category->name];
            $root = $category;

            while ($root->parent_id !== null && $categories->has($root->parent_id)) {
                $root = $categories->get($root->parent_id);
                $path[] = $root->name;
            }

            if ($root === $category) {
                $options[$category->id] = $category->name;

                continue;
            }

            array_pop($path);
            $options[$root->name][$category->id] = implode(' › ', array_reverse($path));
        }

        return $options;
    }
}
