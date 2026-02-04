<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\CannedResponseResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\CannedResponseResource;

class CreateCannedResponse extends CreateRecord
{
    protected static string $resource = CannedResponseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
