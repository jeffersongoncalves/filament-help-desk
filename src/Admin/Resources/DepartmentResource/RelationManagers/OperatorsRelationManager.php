<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\DepartmentResource\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Actions\DetachAction;
use Filament\Tables\Actions\DetachBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OperatorsRelationManager extends RelationManager
{
    protected static string $relationship = 'operators';

    protected static ?string $title = null;

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('filament-help-desk::filament-help-desk.resource.department.relation_managers.operators');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament-help-desk::filament-help-desk.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label(__('filament-help-desk::filament-help-desk.fields.email'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role')
                    ->label(__('filament-help-desk::filament-help-desk.fields.role'))
                    ->getStateUsing(fn ($record): string => $record->pivot->role ?? 'operator')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'manager' => 'success',
                        default => 'info',
                    }),

                IconColumn::make('email_notifications')
                    ->label(__('filament-help-desk::filament-help-desk.fields.email_notifications'))
                    ->getStateUsing(fn ($record): bool => (bool) ($record->pivot->email_notifications ?? true))
                    ->boolean(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelect(function (Select $select): Select {
                        $operatorModel = config('help-desk.models.operator');

                        return $select
                            ->label(__('filament-help-desk::filament-help-desk.fields.operator'))
                            ->options(function () use ($operatorModel): array {
                                return $operatorModel::query()
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable();
                    })
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),

                        Select::make('role')
                            ->label(__('filament-help-desk::filament-help-desk.fields.role'))
                            ->options([
                                'operator' => __('filament-help-desk::filament-help-desk.enums.department_role.operator'),
                                'manager' => __('filament-help-desk::filament-help-desk.enums.department_role.manager'),
                            ])
                            ->default('operator')
                            ->required(),

                        Toggle::make('email_notifications')
                            ->label(__('filament-help-desk::filament-help-desk.fields.email_notifications'))
                            ->default(true),
                    ]),
            ])
            ->actions([
                // Updates the pivot row directly: the row's record is the
                // operator model, which has no such column of its own.
                Action::make('toggleEmailNotifications')
                    ->label(fn ($record): string => ($record->pivot->email_notifications ?? true)
                        ? __('filament-help-desk::filament-help-desk.actions.disable_email_notifications')
                        : __('filament-help-desk::filament-help-desk.actions.enable_email_notifications'))
                    ->icon(fn ($record): string => ($record->pivot->email_notifications ?? true) ? 'heroicon-o-bell-slash' : 'heroicon-o-bell')
                    ->color('gray')
                    ->action(function ($record): void {
                        $record->pivot->update([
                            'email_notifications' => ! ($record->pivot->email_notifications ?? true),
                        ]);
                    }),

                DetachAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                DetachBulkAction::make(),
            ]);
    }
}
