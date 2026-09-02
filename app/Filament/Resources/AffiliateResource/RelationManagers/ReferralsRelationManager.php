<?php

namespace App\Filament\Resources\AffiliateResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * The individual clicks, signups and conversions behind an affiliate's totals
 * — what to look at when someone disputes their commission.
 */
class ReferralsRelationManager extends RelationManager
{
    protected static string $relationship = 'referrals';

    protected static ?string $title = 'Referrals';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'converted' => 'success',
                        'signup' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('referredUser.email')
                    ->label('Trader')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('order.order_number')->label('Order')->placeholder('—'),
                Tables\Columns\TextColumn::make('commission_amount')->label('Commission')->money('USD'),
                Tables\Columns\TextColumn::make('ip_address')->label('IP')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'click' => 'Click', 'signup' => 'Signup', 'converted' => 'Converted',
                ]),
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
