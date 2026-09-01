<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WithdrawalResource\Pages;
use App\Models\Withdrawal;
use App\Services\Withdrawals\WithdrawalService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WithdrawalResource extends Resource
{
    protected static ?string $model = Withdrawal::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Withdrawals';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::where('status', 'pending')->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('withdrawal_number')->label('ID')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Trader')
                    ->description(fn (Withdrawal $r) => $r->user?->email)
                    ->searchable(),
                Tables\Columns\TextColumn::make('trading_account_id')->label('Acct'),
                Tables\Columns\TextColumn::make('amount')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('method'),
                Tables\Columns\TextColumn::make('wallet_address')->label('Wallet')->limit(18)->copyable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'paid' => 'success',
                        'approved', 'processing' => 'info',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending', 'under_review' => 'Under review', 'approved' => 'Approved',
                    'processing' => 'Processing', 'paid' => 'Paid', 'rejected' => 'Rejected',
                ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('info')
                        ->visible(fn (Withdrawal $r) => in_array($r->status, ['pending', 'under_review'], true))
                        ->requiresConfirmation()
                        ->action(function (Withdrawal $record) {
                            app(WithdrawalService::class)->approve($record, auth()->user());
                            Notification::make()->title('Withdrawal approved')->success()->send();
                        }),

                    Tables\Actions\Action::make('markPaid')
                        ->label('Mark paid')
                        ->icon('heroicon-o-currency-dollar')
                        ->color('success')
                        ->visible(fn (Withdrawal $r) => in_array($r->status, ['approved', 'processing'], true))
                        ->form([
                            Forms\Components\TextInput::make('txid')->label('Transaction reference'),
                        ])
                        ->action(function (Withdrawal $record, array $data) {
                            app(WithdrawalService::class)->markPaid($record, $data['txid'] ?? null);
                            Notification::make()->title('Marked as paid')->success()->send();
                        }),

                    Tables\Actions\Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Withdrawal $r) => ! in_array($r->status, ['paid', 'rejected'], true))
                        ->form([
                            Forms\Components\Textarea::make('remarks')->label('Reason')->required(),
                        ])
                        ->action(function (Withdrawal $record, array $data) {
                            app(WithdrawalService::class)->reject($record, auth()->user(), $data['remarks']);
                            Notification::make()->title('Withdrawal rejected')->danger()->send();
                        }),
                ])->label('Manage')->icon('heroicon-m-ellipsis-vertical')->button(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWithdrawals::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage withdrawals') ?? false;
    }
}
