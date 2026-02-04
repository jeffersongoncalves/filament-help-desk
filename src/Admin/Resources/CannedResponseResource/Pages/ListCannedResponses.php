<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\CannedResponseResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\CannedResponseResource;

class ListCannedResponses extends ListRecords
{
    protected static string $resource = CannedResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
