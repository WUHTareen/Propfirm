<?php

namespace App\Filament\Resources\GiveawayEntryResource\Pages;

use App\Filament\Resources\GiveawayEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGiveawayEntries extends ListRecords
{
    protected static string $resource = GiveawayEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('draw')
                ->label('Draw winners')
                ->icon('heroicon-o-gift')
                ->color('success')
                ->modalDescription('Picks random winners from the chosen week\'s entries that have not been drawn yet, and notifies them.')
                ->form(GiveawayEntryResource::drawFormSchema())
                ->action(fn (array $data) => GiveawayEntryResource::runDraw($data)),
            Actions\CreateAction::make()->label('Add entry'),
        ];
    }
}
