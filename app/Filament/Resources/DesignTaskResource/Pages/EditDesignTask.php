<?php

namespace App\Filament\Resources\DesignTaskResource\Pages;

use App\Filament\Resources\DesignTaskResource;
use App\Services\DesignService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDesignTask extends EditRecord
{
    protected static string $resource = DesignTaskResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Delegate to DesignService to handle business logic
        try {
            app(DesignService::class)->updateDesignTask($this->record->id, $data, auth()->id());
        } catch (\RuntimeException $e) {
            $this->halt();
            \Filament\Notifications\Notification::make()->title($e->getMessage())->danger()->send();
        }

        return $data;
    }
}
