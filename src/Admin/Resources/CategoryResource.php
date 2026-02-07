<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Admin\Resources;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\CategoryResource\Pages;
use JeffersonGoncalves\HelpDesk\Models\Category;
use JeffersonGoncalves\HelpDesk\Models\Department;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

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
        return __('filament-help-desk::filament-help-desk.resource.category.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament-help-desk::filament-help-desk.resource.category.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-help-desk::filament-help-desk.resource.category.plural_model_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('department_id')
                    ->label(__('filament-help-desk::filament-help-desk.fields.department'))
                    ->options(fn (): array => Department::query()
                        ->active()
                        ->ordered()
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('parent_id', null);
                    }),

                Select::make('parent_id')
                    ->label(__('filament-help-desk::filament-help-desk.fields.parent_category'))
                    ->options(function (Get $get, ?Model $record): array {
                        $departmentId = $get('department_id');

                        if (! $departmentId) {
                            return [];
                        }

                        $query = Category::query()
                            ->where('department_id', $departmentId)
                            ->active()
                            ->ordered();

                        if ($record) {
                            $query->where('id', '!=', $record->getKey());
                        }

                        return $query->pluck('name', 'id')->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->visible(fn (Get $get): bool => filled($get('department_id'))),

                TextInput::make('name')
                    ->label(__('filament-help-desk::filament-help-desk.fields.name'))
                    ->required()
                    ->maxLength(255)
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

                TextColumn::make('department.name')
                    ->label(__('filament-help-desk::filament-help-desk.fields.department'))
                    ->sortable(),

                TextColumn::make('parent.name')
                    ->label(__('filament-help-desk::filament-help-desk.fields.parent_category'))
                    ->default(__('filament-help-desk::filament-help-desk.placeholders.root'))
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label(__('filament-help-desk::filament-help-desk.fields.is_active'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label(__('filament-help-desk::filament-help-desk.fields.sort_order'))
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
