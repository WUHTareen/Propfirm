<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * The trader's MT5/MT4 accounts and where each one stands. Read-only here —
 * credentials, phase changes and breaches are handled on the Trading Accounts
 * screen, which notifies the trader when it acts.
 */
class TradingAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'tradingAccounts';

    protected static ?string $title = 'Trading accounts';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('login')->label('Login')->placeholder('Not assigned'),
                Tables\Columns\TextColumn::make('platform')->formatStateUsing(fn (?string $s) => strtoupper((string) $s)),
                Tables\Columns\TextColumn::make('account_size')->money('USD', divideBy: 1),
                Tables\Columns\TextColumn::make('current_phase')->label('Phase'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'funded', 'passed' => 'success',
                        'breached' => 'danger',
                        'active' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('current_balance')->money('USD')->placeholder('—'),
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
