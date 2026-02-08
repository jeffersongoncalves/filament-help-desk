<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource\Pages;

use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use JeffersonGoncalves\FilamentHelpDesk\Concerns\HasTicketForm;
use JeffersonGoncalves\FilamentHelpDesk\Operator\Resources\TicketResource;
use JeffersonGoncalves\HelpDesk\Models\Ticket;

/**
 * @property-read Ticket $record
 */
class EditTicket extends EditRecord
{
    use HasTicketForm;

    protected static string $resource = TicketResource::class;

    public function form(Schema $schema): Schema
    {
        $formSchema = static::getTicketEditFormSchema();

        $operatorModel = config('help-desk.models.operator');

        $formSchema[] = Select::make('assigned_to_id')
            ->label(__('filament-help-desk::filament-help-desk.fields.assigned_to'))
            ->options(function () use ($operatorModel): array {
                return $operatorModel::query()
                    ->pluck('name', 'id')
                    ->toArray();
            })
            ->searchable()
            ->preload()
            ->nullable()
            ->placeholder(__('filament-help-desk::filament-help-desk.placeholders.select_operator'));

        return $schema->columns(null)->schema($formSchema);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (array_key_exists('assigned_to_id', $data)) {
            if ($data['assigned_to_id'] !== null) {
                $operatorModel = config('help-desk.models.operator');
                $data['assigned_to_type'] = $operatorModel;
            } else {
                $data['assigned_to_type'] = null;
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        Notification::make()
            ->title(__('filament-help-desk::filament-help-desk.notifications.ticket_updated'))
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return __('filament-help-desk::filament-help-desk.actions.edit_ticket').': '.$this->record->reference_number;
    }
}
