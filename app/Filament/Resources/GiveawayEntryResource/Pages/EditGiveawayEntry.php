<?php

namespace App\Filament\Resources\GiveawayEntryResource\Pages;

use App\Filament\Resources\GiveawayEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGiveawayEntry extends EditRecord
{
    protected static string $resource = GiveawayEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
