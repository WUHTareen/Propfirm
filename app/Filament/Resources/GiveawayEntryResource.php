<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GiveawayEntryResource\Pages;
use App\Models\GiveawayEntry;
use App\Services\Rewards\GiveawayService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * The weekly Trustpilot-review giveaway: who entered, and who won.
 *
 * Drawing winners is the point of this screen, and it runs from the list
 * header rather than per row — see GiveawayService for how a week is drawn.
 */
class GiveawayEntryResource extends Resource
{
    protected static ?string $model = GiveawayEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Rewards';

    protected static ?string $navigationLabel = 'Weekly Giveaway';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Entry')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Trader')
                        ->relationship('user', 'email')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\DatePicker::make('week_start')
                        ->label('Giveaway week')
                        ->required()
                        ->default(fn () => now()->startOfWeek())
                        ->helperText('The Monday of the week this entry belongs to.'),
                    Forms\Components\TextInput::make('trustpilot_review_link')
                        ->label('Trustpilot review')
                        ->url()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\Select::make('status')
                        ->native(false)
                        ->required()
                        ->default('entered')
                        ->options([
                            'entered' => 'Entered',
                            'won' => 'Won',
                            'lost' => 'Not drawn',
                        ]),
                    Forms\Components\TextInput::make('prize_account_size')
                        ->label('Prize account size')
                        ->numeric()
                        ->prefix('$')
                        ->helperText('Only for winners.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('week_start', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('week_start')
                    ->label('Week')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Trader')
                    ->description(fn (GiveawayEntry $e) => $e->user?->email)
                    ->searchable(),
                Tables\Columns\TextColumn::make('trustpilot_review_link')
                    ->label('Review')
                    ->url(fn (GiveawayEntry $e) => $e->trustpilot_review_link, shouldOpenInNewTab: true)
                    ->limit(24)
                    ->placeholder('—')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'won' => 'Won', 'lost' => 'Not drawn', default => 'Entered',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'won' => 'success', 'lost' => 'gray', default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('prize_account_size')
                    ->label('Prize')
                    ->money('USD', divideBy: 1)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('drawn_at')->label('Drawn')->dateTime('d M Y')->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'entered' => 'Entered', 'won' => 'Won', 'lost' => 'Not drawn',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([]);
    }

    /**
     * Fields the draw asks for. Lives here so the list header and any future
     * caller share one definition.
     */
    public static function drawFormSchema(): array
    {
        return [
            Forms\Components\DatePicker::make('week_start')
                ->label('Giveaway week')
                ->required()
                ->default(fn () => now()->startOfWeek())
                ->helperText('The Monday of the week to draw.'),
            Forms\Components\TextInput::make('winners')
                ->label('Number of winners')
                ->numeric()
                ->minValue(1)
                ->required()
                ->default(fn () => GiveawayService::defaultWinnerCount()),
            Forms\Components\TextInput::make('prize_account_size')
                ->label('Prize account size')
                ->numeric()
                ->prefix('$')
                ->required()
                ->default(fn () => GiveawayService::defaultPrizeSize()),
        ];
    }

    /**
     * Run the draw and report what happened.
     */
    public static function runDraw(array $data): void
    {
        $won = app(GiveawayService::class)->draw(
            $data['week_start'],
            (int) $data['winners'],
            (float) $data['prize_account_size'],
        );

        if ($won === 0) {
            Notification::make()
                ->title('Nothing to draw')
                ->body('That week has no entries still waiting to be drawn.')
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title($won === 1 ? '1 winner drawn' : "{$won} winners drawn")
            ->success()
            ->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGiveawayEntries::route('/'),
            'create' => Pages\CreateGiveawayEntry::route('/create'),
            'edit' => Pages\EditGiveawayEntry::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage rewards') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('manage rewards') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('manage rewards') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('manage rewards') ?? false;
    }
}
