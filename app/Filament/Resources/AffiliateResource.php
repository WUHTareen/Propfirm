<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AffiliateResource\Pages;
use App\Models\Affiliate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

/**
 * Affiliate performance and what the firm owes each one.
 *
 * Clicks, signups, conversions and commission totals are written by
 * AffiliateService as traffic and orders come in — they are a record of what
 * happened, so they are not editable here. What an admin does decide is the
 * commission rate, and when a payout has actually been sent.
 */
class AffiliateResource extends Resource
{
    protected static ?string $model = Affiliate::class;

    protected static ?string $navigationIcon = 'heroicon-o-share';

    protected static ?string $navigationGroup = 'Rewards';

    protected static ?string $navigationLabel = 'Affiliates';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Affiliate')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->disabled()
                        ->helperText('Generated when the trader joined the programme.'),
                    Forms\Components\TextInput::make('commission_rate')
                        ->label('Commission rate')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->helperText('Percentage of each referred order. Applies to future orders only.'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->helperText('Switching off stops new commission being credited.'),
                ]),

            Forms\Components\Section::make('Earnings')
                ->description('Written by the system as referred orders are paid.')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('total_commission')->prefix('$')->disabled(),
                    Forms\Components\TextInput::make('available_commission')->prefix('$')->disabled(),
                    Forms\Components\TextInput::make('paid_commission')->prefix('$')->disabled(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('total_commission', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Affiliate')
                    ->description(fn (Affiliate $a) => $a->user?->email)
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('clicks')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('signups')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('conversions')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('commission_rate')->label('Rate')->suffix('%'),
                Tables\Columns\TextColumn::make('available_commission')
                    ->label('Owed')
                    ->money('USD')
                    ->sortable()
                    ->color(fn (Affiliate $a) => (float) $a->available_commission > 0 ? 'warning' : 'gray'),
                Tables\Columns\TextColumn::make('paid_commission')->label('Paid')->money('USD')->toggleable(),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
                Tables\Filters\Filter::make('owed')
                    ->label('Has commission owed')
                    ->query(fn ($query) => $query->where('available_commission', '>', 0)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Recording a payout moves money between two columns; if the
                // second write failed the firm would appear to owe it twice.
                Tables\Actions\Action::make('markPaid')
                    ->label('Mark paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Affiliate $a) => (float) $a->available_commission > 0)
                    ->requiresConfirmation()
                    ->modalDescription(fn (Affiliate $a) => 'Record that $'
                        .number_format((float) $a->available_commission, 2)
                        .' has been sent. This does not move any money itself.')
                    ->form([
                        Forms\Components\TextInput::make('reference')
                            ->label('Transaction reference')
                            ->maxLength(120)
                            ->helperText('Optional — kept in the notification to the affiliate.'),
                    ])
                    ->action(function (Affiliate $record) {
                        DB::transaction(function () use ($record) {
                            $record->refresh();
                            $amount = (float) $record->available_commission;

                            if ($amount <= 0) {
                                return;
                            }

                            $record->forceFill([
                                'paid_commission' => (float) $record->paid_commission + $amount,
                                'available_commission' => 0,
                            ])->save();
                        });

                        Notification::make()->title('Commission marked as paid')->success()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            AffiliateResource\RelationManagers\ReferralsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAffiliates::route('/'),
            'edit' => Pages\EditAffiliate::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage rewards') ?? false;
    }

    // Affiliates are created by traders joining the programme, never by staff.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('manage rewards') ?? false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
