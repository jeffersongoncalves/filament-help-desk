<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Admin\Resources;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\CannedResponseResource\Pages;
use JeffersonGoncalves\HelpDesk\Models\CannedResponse;
use JeffersonGoncalves\HelpDesk\Models\Department;

class CannedResponseResource extends Resource
{
    protected static ?string $model = CannedResponse::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

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
        return __('filament-help-desk::filament-help-desk.resource.canned_response.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament-help-desk::filament-help-desk.resource.canned_response.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-help-desk::filament-help-desk.resource.canned_response.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('department_id')
                    ->label(__('filament-help-desk::filament-help-desk.fields.department'))
                    ->options(fn (): array => Department::query()
                        ->active()
                        ->ordered()
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->placeholder(__('filament-help-desk::filament-help-desk.placeholders.all_departments')),

                TextInput::make('title')
                    ->label(__('filament-help-desk::filament-help-desk.fields.title'))
                    ->required()
                    ->maxLength(255),

                RichEditor::make('body')
                    ->label(__('filament-help-desk::filament-help-desk.fields.body'))
                    ->required()
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
                TextColumn::make('title')
                    ->label(__('filament-help-desk::filament-help-desk.fields.title'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('department.name')
                    ->label(__('filament-help-desk::filament-help-desk.fields.department'))
                    ->placeholder(__('filament-help-desk::filament-help-desk.placeholders.all_departments'))
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
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCannedResponses::route('/'),
            'create' => Pages\CreateCannedResponse::route('/create'),
            'edit' => Pages\EditCannedResponse::route('/{record}/edit'),
        ];
    }
}
