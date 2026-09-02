<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * Discount codes for the checkout. The rules here are the same ones
 * PricingService::couponApplies() enforces at checkout — window, caps and
 * minimum order — so what an admin sets is exactly what a trader gets.
 */
class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Rewards';

    protected static ?string $navigationLabel = 'Coupons';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Code')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->required()
                        ->maxLength(40)
                        ->unique(ignoreRecord: true)
                        ->helperText('What the trader types at checkout. Case is normalised to uppercase.')
                        ->dehydrateStateUsing(fn (?string $state) => Str::upper(trim((string) $state)))
                        ->suffixAction(
                            Forms\Components\Actions\Action::make('generate')
                                ->icon('heroicon-m-sparkles')
                                ->label('Generate')
                                ->action(fn (Forms\Set $set) => $set('code', Str::upper(Str::random(8))))
                        ),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Switch off to retire a code without deleting its history.'),
                ]),

            Forms\Components\Section::make('Discount')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('type')
                        ->required()
                        ->live()
                        ->native(false)
                        ->default('percent')
                        ->options([
                            'percent' => 'Percentage off',
                            'fixed' => 'Fixed amount off',
                        ]),
                    Forms\Components\TextInput::make('value')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(fn (Forms\Get $get) => $get('type') === 'percent' ? 100 : null)
                        ->prefix(fn (Forms\Get $get) => $get('type') === 'fixed' ? '$' : null)
                        ->suffix(fn (Forms\Get $get) => $get('type') === 'percent' ? '%' : null),
                    Forms\Components\TextInput::make('min_order_amount')
                        ->label('Minimum order')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('$')
                        ->helperText('Leave blank for no minimum.'),
                ]),

            Forms\Components\Section::make('Limits')
                ->description('Leave a limit blank to allow unlimited use.')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('max_uses')
                        ->label('Total uses allowed')
                        ->numeric()
                        ->minValue(1),
                    Forms\Components\TextInput::make('per_user_limit')
                        ->label('Uses per trader')
                        ->numeric()
                        ->minValue(1),
                    Forms\Components\DateTimePicker::make('starts_at')
                        ->label('Valid from')
                        ->helperText('Blank means it works immediately.'),
                    Forms\Components\DateTimePicker::make('expires_at')
                        ->label('Expires')
                        ->helperText('Blank means it never expires.')
                        ->after('starts_at'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('value')
                    ->label('Discount')
                    ->formatStateUsing(fn (Coupon $c) => $c->type === 'percent'
                        ? rtrim(rtrim((string) $c->value, '0'), '.').'%'
                        : '$'.number_format((float) $c->value, 2)),
                Tables\Columns\TextColumn::make('used_count')
                    ->label('Used')
                    ->formatStateUsing(fn (Coupon $c) => $c->max_uses
                        ? "{$c->used_count} / {$c->max_uses}"
                        : (string) $c->used_count),
                Tables\Columns\TextColumn::make('per_user_limit')
                    ->label('Per trader')
                    ->placeholder('∞')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('d M Y')
                    ->placeholder('Never')
                    ->sortable(),
                // The real answer a support agent needs: would this code work
                // right now? Rolls up active, window and cap in one badge.
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Coupon $c) => static::statusOf($c))
                    ->color(fn (string $state) => match ($state) {
                        'Usable' => 'success',
                        'Disabled' => 'gray',
                        default => 'danger',
                    }),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
                Tables\Filters\SelectFilter::make('type')->options([
                    'percent' => 'Percentage', 'fixed' => 'Fixed',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Why a code would or wouldn't apply if a trader typed it right now.
     */
    protected static function statusOf(Coupon $coupon): string
    {
        if (! $coupon->is_active) {
            return 'Disabled';
        }
        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return 'Scheduled';
        }
        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return 'Expired';
        }
        if ($coupon->max_uses !== null && $coupon->used_count >= $coupon->max_uses) {
            return 'Used up';
        }

        return 'Usable';
    }

    public static function getRelations(): array
    {
        return [
            CouponResource\RelationManagers\UsagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage coupons') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('manage coupons') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('manage coupons') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('manage coupons') ?? false;
    }
}
