<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\EmailChannelResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\EmailChannelResource;

class CreateEmailChannel extends CreateRecord
{
    protected static string $resource = EmailChannelResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
