<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Admin\Resources;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\EmailChannelResource\Pages;
use JeffersonGoncalves\HelpDesk\Models\Department;
use JeffersonGoncalves\HelpDesk\Models\EmailChannel;

class EmailChannelResource extends Resource
{
    protected static ?string $model = EmailChannel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

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
        return __('filament-help-desk::filament-help-desk.resource.email_channel.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament-help-desk::filament-help-desk.resource.email_channel.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-help-desk::filament-help-desk.resource.email_channel.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(null)
            ->schema([
                TextInput::make('name')
                    ->label(__('filament-help-desk::filament-help-desk.fields.name'))
                    ->required()
                    ->maxLength(255),

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
                    ->placeholder(__('filament-help-desk::filament-help-desk.placeholders.none')),

                Select::make('driver')
                    ->label(__('filament-help-desk::filament-help-desk.fields.driver'))
                    ->options([
                        'imap' => __('filament-help-desk::filament-help-desk.drivers.imap'),
                        'mailgun' => __('filament-help-desk::filament-help-desk.drivers.mailgun'),
                        'sendgrid' => __('filament-help-desk::filament-help-desk.drivers.sendgrid'),
                        'resend' => __('filament-help-desk::filament-help-desk.drivers.resend'),
                        'postmark' => __('filament-help-desk::filament-help-desk.drivers.postmark'),
                    ])
                    ->required()
                    ->searchable(),

                TextInput::make('email_address')
                    ->label(__('filament-help-desk::filament-help-desk.fields.email_address'))
                    ->required()
                    ->email()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                KeyValue::make('settings')
                    ->label(__('filament-help-desk::filament-help-desk.fields.settings'))
                    ->keyLabel(__('filament-help-desk::filament-help-desk.fields.setting_key'))
                    ->valueLabel(__('filament-help-desk::filament-help-desk.fields.setting_value'))
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label(__('filament-help-desk::filament-help-desk.fields.is_active'))
                    ->default(true),
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

                TextColumn::make('email_address')
                    ->label(__('filament-help-desk::filament-help-desk.fields.email_address'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('driver')
                    ->label(__('filament-help-desk::filament-help-desk.fields.driver'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('department.name')
                    ->label(__('filament-help-desk::filament-help-desk.fields.department'))
                    ->placeholder(__('filament-help-desk::filament-help-desk.placeholders.none'))
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label(__('filament-help-desk::filament-help-desk.fields.is_active'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make('last_polled_at')
                    ->label(__('filament-help-desk::filament-help-desk.fields.last_polled_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('last_error')
                    ->label(__('filament-help-desk::filament-help-desk.fields.last_error'))
                    ->limit(30)
                    ->color(fn (?string $state): ?string => $state !== null ? 'danger' : null)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailChannels::route('/'),
            'create' => Pages\CreateEmailChannel::route('/create'),
            'edit' => Pages\EditEmailChannel::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('filament-help-desk.admin.resources.email_channel') !== null;
    }
}
