<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    // Orders originate at checkout — no "create" button here.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
