<?php

namespace App\Filament\Resources\BoxResource\Pages;

use App\Filament\Resources\BoxResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditBox extends EditRecord
{
    protected static string $resource = BoxResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        if ($this->getRecord()->trashed()) {
            $this->redirect($this->getRedirectUrl());
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Box Updated')
            ->body('The box has been saved successfully');
    }

    public function getBreadcrumbs(): array
    {
        return [
            $this->getResource()::getUrl() => $this->getResource()::getBreadcrumb(),
            $this->getResource()::getUrl('view', [
                'record' => $this->getRecord()
            ]) => 'View',
            'Edit'
        ];
    }
}
