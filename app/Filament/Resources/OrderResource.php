<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Services\Orders\OrderFulfillmentService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Order')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Trader')
                    ->description(fn (Order $r) => $r->user?->email)
                    ->searchable(),
                Tables\Columns\TextColumn::make('plan_snapshot.name')
                    ->label('Plan')
                    ->getStateUsing(fn (Order $r) => $r->plan_snapshot['name'] ?? '—'),
                Tables\Columns\TextColumn::make('platform')
                    ->formatStateUsing(fn (string $state) => strtoupper($state))
                    ->badge(),
                Tables\Columns\TextColumn::make('total')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'paid', 'processing', 'completed' => 'success',
                        'pending' => 'warning',
                        'cancelled', 'expired', 'refunded' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'expired' => 'Expired',
                        'refunded' => 'Refunded',
                    ]),
                Tables\Filters\SelectFilter::make('payment_gateway')
                    ->options(['manual' => 'Manual', 'nowpayments' => 'NOWPayments']),
            ])
            ->actions([
                Tables\Actions\Action::make('markPaid')
                    ->label('Mark paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Order $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalDescription('Confirm you have received payment for this order. This provisions the trader\'s account for assignment.')
                    ->form([
                        Forms\Components\TextInput::make('txid')
                            ->label('Transaction reference (optional)'),
                    ])
                    ->action(function (Order $record, array $data) {
                        app(OrderFulfillmentService::class)->markPaid($record, $data['txid'] ?? null);

                        Notification::make()
                            ->title("Order {$record->order_number} marked as paid")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
        ];
    }

    // Orders are created at checkout, never in the panel.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage orders') ?? false;
    }
}
