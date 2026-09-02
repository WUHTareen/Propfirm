<?php

namespace App\Filament\Resources\CouponResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Who actually redeemed a code, and what it cost. Read-only — usages are a
 * record of what happened at checkout, not something to edit after the fact.
 */
class UsagesRelationManager extends RelationManager
{
    protected static string $relationship = 'usages';

    protected static ?string $title = 'Redemptions';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Trader')
                    ->description(fn ($record) => $record->user?->email)
                    ->searchable(),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('discount_amount')
                    ->label('Discount')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Redeemed')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
