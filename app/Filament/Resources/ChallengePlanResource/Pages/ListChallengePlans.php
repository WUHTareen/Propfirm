<?php

namespace App\Filament\Resources\ChallengePlanResource\Pages;

use App\Filament\Resources\ChallengePlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChallengePlans extends ListRecords
{
    protected static string $resource = ChallengePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
