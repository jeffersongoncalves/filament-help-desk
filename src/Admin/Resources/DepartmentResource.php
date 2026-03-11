<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Admin\Resources;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\DepartmentResource\Pages;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\DepartmentResource\RelationManagers\OperatorsRelationManager;
use JeffersonGoncalves\HelpDesk\Models\Department;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    public static function getNavigationGroup(): ?string
    {
        return __(config('filament-help-desk.admin.navigation_group', 'Help Desk'));
    }

    public static function getNavigationSort(): ?int
    {
        return config('filament-help-desk.admin.navigation_sort');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-help-desk::filament-help-desk.resource.department.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament-help-desk::filament-help-desk.resource.department.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-help-desk::filament-help-desk.resource.department.plural_model_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('filament-help-desk::filament-help-desk.fields.name'))
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                        if ($operation === 'create') {
                            $set('slug', Str::slug($state ?? ''));
                        }
                    }),

                TextInput::make('slug')
                    ->label(__('filament-help-desk::filament-help-desk.fields.slug'))
                    ->disabled(fn (string $operation): bool => $operation === 'edit')
                    ->dehydrated()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Textarea::make('description')
                    ->label(__('filament-help-desk::filament-help-desk.fields.description'))
                    ->columnSpanFull(),

                TextInput::make('email')
                    ->label(__('filament-help-desk::filament-help-desk.fields.email'))
                    ->email()
                    ->maxLength(255),

                Toggle::make('is_active')
                    ->label(__('filament-help-desk::filament-help-desk.fields.is_active'))
                    ->default(true),

                TextInput::make('sort_order')
                    ->label(__('filament-help-desk::filament-help-desk.fields.sort_order'))
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament-help-desk::filament-help-desk.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label(__('filament-help-desk::filament-help-desk.fields.email'))
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label(__('filament-help-desk::filament-help-desk.fields.is_active'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make('tickets_count')
                    ->label(__('filament-help-desk::filament-help-desk.fields.tickets_count'))
                    ->counts('tickets')
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label(__('filament-help-desk::filament-help-desk.fields.sort_order'))
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('filament-help-desk::filament-help-desk.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            OperatorsRelationManager::class,
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('filament-help-desk.admin.resources.department') !== null;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }
}
