<?php

namespace App\Filament\Resources\TradingAccountResource\Pages;

use App\Filament\Resources\TradingAccountResource;
use Filament\Resources\Pages\ListRecords;

class ListTradingAccounts extends ListRecords
{
    protected static string $resource = TradingAccountResource::class;

    // Accounts are provisioned from paid orders, not created by hand.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
