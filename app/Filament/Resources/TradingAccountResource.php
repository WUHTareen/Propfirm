<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TradingAccountResource\Pages;
use App\Models\TradingAccount;
use App\Services\Accounts\AccountManager;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TradingAccountResource extends Resource
{
    protected static ?string $model = TradingAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Trading Accounts';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Trader')
                    ->description(fn (TradingAccount $r) => $r->user?->email)
                    ->searchable(),
                Tables\Columns\TextColumn::make('account_size')->money('USD', divideBy: 1)->sortable(),
                Tables\Columns\TextColumn::make('platform')->formatStateUsing(fn (string $state) => strtoupper($state))->badge(),
                Tables\Columns\TextColumn::make('challenge_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'two_step' => '2-Step', 'one_step' => '1-Step', 'instant' => 'Instant', default => $state,
                    }),
                Tables\Columns\TextColumn::make('current_phase')->label('Phase')->badge(),
                Tables\Columns\TextColumn::make('login')->placeholder('—')->searchable(),
                Tables\Columns\TextColumn::make('current_equity')->money('USD')->placeholder('—')->label('Equity'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => str_replace('_', ' ', ucfirst($state)))
                    ->color(fn (string $state) => match ($state) {
                        'active' => 'info',
                        'passed', 'funded' => 'success',
                        'breached', 'disabled' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending_assignment' => 'Pending assignment',
                    'active' => 'Active',
                    'passed' => 'Passed',
                    'funded' => 'Funded',
                    'breached' => 'Breached',
                    'disabled' => 'Disabled',
                ]),
                Tables\Filters\SelectFilter::make('platform')->options(['mt5' => 'MT5', 'mt4' => 'MT4']),
            ])
            ->actions([
                // Issue MT5/MT4 credentials + activate.
                Tables\Actions\Action::make('assign')
                    ->label('Assign')
                    ->icon('heroicon-o-key')
                    ->color('primary')
                    ->visible(fn (TradingAccount $r) => auth()->user()?->can('assign accounts'))
                    ->form([
                        Forms\Components\TextInput::make('login')->required()->default(fn (TradingAccount $r) => $r->login),
                        Forms\Components\TextInput::make('password')->password()->revealable()->helperText('Stored encrypted.'),
                        Forms\Components\TextInput::make('server')->default(fn (TradingAccount $r) => $r->server),
                        Forms\Components\TextInput::make('investor_password')->label('Investor password (read-only)')->password()->revealable(),
                    ])
                    ->action(function (TradingAccount $record, array $data) {
                        app(AccountManager::class)->assignCredentials($record, $data);
                        Notification::make()->title('Credentials assigned — account active')->success()->send();
                    }),

                // Update live metrics (records an equity snapshot).
                Tables\Actions\Action::make('metrics')
                    ->label('Update metrics')
                    ->icon('heroicon-o-chart-bar')
                    ->color('gray')
                    ->visible(fn (TradingAccount $r) => auth()->user()?->can('manage phases') && in_array($r->status, ['active', 'funded'], true))
                    ->form([
                        Forms\Components\TextInput::make('balance')->numeric()->required()->prefix('$')
                            ->default(fn (TradingAccount $r) => $r->current_balance ?? $r->starting_balance),
                        Forms\Components\TextInput::make('equity')->numeric()->required()->prefix('$')
                            ->default(fn (TradingAccount $r) => $r->current_equity ?? $r->starting_balance),
                    ])
                    ->action(function (TradingAccount $record, array $data) {
                        app(AccountManager::class)->updateMetrics($record, (float) $data['balance'], (float) $data['equity']);
                        Notification::make()->title('Metrics updated')->success()->send();
                    }),

                Tables\Actions\ActionGroup::make([
                    // Advance a phase / fund.
                    Tables\Actions\Action::make('passPhase')
                        ->label('Pass phase / fund')
                        ->icon('heroicon-o-arrow-trending-up')
                        ->color('success')
                        ->visible(fn (TradingAccount $r) => auth()->user()?->can('manage phases') && in_array($r->status, ['active'], true))
                        ->requiresConfirmation()
                        ->modalDescription('Advance this account to the next phase, or fund it if the final phase is cleared. A certificate is issued.')
                        ->action(function (TradingAccount $record) {
                            $account = app(AccountManager::class)->passPhase($record);
                            Notification::make()->title($account->status === 'funded' ? 'Account funded' : "Advanced to phase {$account->current_phase}")->success()->send();
                        }),

                    // Breach.
                    Tables\Actions\Action::make('breach')
                        ->label('Breach')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (TradingAccount $r) => auth()->user()?->can('manage phases') && ! in_array($r->status, ['breached', 'disabled'], true))
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Select::make('reason')
                                ->required()
                                ->options([
                                    'daily_drawdown' => 'Daily loss limit exceeded',
                                    'max_drawdown' => 'Max loss limit exceeded',
                                    'prohibited_strategy' => 'Prohibited strategy',
                                    'inactivity' => 'Inactivity',
                                    'other' => 'Other',
                                ]),
                        ])
                        ->action(function (TradingAccount $record, array $data) {
                            app(AccountManager::class)->breach($record, $data['reason']);
                            Notification::make()->title('Account marked as breached')->danger()->send();
                        }),
                ])->label('Phase / breach')->icon('heroicon-m-ellipsis-vertical')->button(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTradingAccounts::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('assign accounts') ?? false;
    }
}
