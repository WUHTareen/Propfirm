<?php

namespace App\Filament\Resources\ChallengePlanResource\Pages;

use App\Filament\Resources\ChallengePlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChallengePlan extends EditRecord
{
    protected static string $resource = ChallengePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
