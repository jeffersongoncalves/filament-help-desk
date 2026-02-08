<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\DepartmentResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
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
                    ]),
            ])
            ->recordActions([
                DetachAction::make()
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                DetachBulkAction::make(),
            ]);
    }
}
