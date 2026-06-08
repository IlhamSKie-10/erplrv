<?php

namespace App\Filament\Resources\ProductionWorkOrderResource\Pages;

use App\Filament\Resources\ProductionWorkOrderResource;
use App\Services\ProductionService;
use Filament\Resources\Pages\EditRecord;

class EditProductionWorkOrder extends EditRecord
{
    protected static string $resource = ProductionWorkOrderResource::class;

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
        try {
            app(ProductionService::class)->updateProductionStatus(
                $this->record->id,
                $data,
                auth()->id()
            );
        } catch (\RuntimeException $e) {
            $this->halt();
            \Filament\Notifications\Notification::make()->title($e->getMessage())->danger()->send();
        }

        return $data;
    }
}
