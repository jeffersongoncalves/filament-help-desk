<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\CategoryResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\CategoryResource;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
