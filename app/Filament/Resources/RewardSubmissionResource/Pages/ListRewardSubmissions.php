<?php

namespace App\Filament\Resources\RewardSubmissionResource\Pages;

use App\Filament\Resources\RewardSubmissionResource;
use Filament\Resources\Pages\ListRecords;

class ListRewardSubmissions extends ListRecords
{
    protected static string $resource = RewardSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
