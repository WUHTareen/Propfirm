<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChallengePlanResource\Pages;
use App\Models\ChallengePlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ChallengePlanResource extends Resource
{
    protected static ?string $model = ChallengePlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationGroup = 'Setup';

    protected static ?string $navigationLabel = 'Challenge Plans';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Plan')
                ->description('Basic identity and visibility. The slug is used in URLs.')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                            if ($operation === 'create') {
                                $set('slug', Str::slug($state));
                            }
                        }),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Auto-filled from the name; edit if needed.'),
                    Forms\Components\Select::make('challenge_type')
                        ->required()
                        ->options([
                            'two_step' => '2-Step',
                            'one_step' => '1-Step',
                            'instant' => 'Instant',
                        ])
                        ->native(false),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active (shown on the site)')
                        ->default(true),
                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Lower numbers appear first.'),
                ]),

            Forms\Components\Section::make('Pricing')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('account_size')
                        ->required()
                        ->numeric()
                        ->prefix('$')
                        ->helperText('Simulated capital, e.g. 10000.'),
                    Forms\Components\TextInput::make('price')
                        ->required()
                        ->numeric()
                        ->prefix('$')
                        ->helperText('One-time challenge fee.'),
                    Forms\Components\TextInput::make('currency')
                        ->required()
                        ->default('USD')
                        ->maxLength(3),
                    Forms\Components\TextInput::make('profit_split_percent')
                        ->label('Profit split to trader (%)')
                        ->numeric()
                        ->default(80)
                        ->suffix('%'),
                ]),

            Forms\Components\Section::make('Evaluation rules')
                ->description('These power both the dashboard rule display and breach checks.')
                ->columns(2)
                ->schema([
                    Forms\Components\Repeater::make('phases')
                        ->helperText('One row per evaluation phase. Leave empty for Instant funding.')
                        ->columnSpanFull()
                        ->schema([
                            Forms\Components\TextInput::make('phase')
                                ->numeric()
                                ->required()
                                ->default(1),
                            Forms\Components\TextInput::make('profit_target_percent')
                                ->numeric()
                                ->required()
                                ->suffix('%'),
                            Forms\Components\TextInput::make('min_trading_days')
                                ->numeric()
                                ->default(0),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->addActionLabel('Add phase')
                        ->reorderable(false),

                    Forms\Components\TextInput::make('phase1_target_percent')
                        ->label('Phase 1 target (%)')
                        ->numeric()
                        ->suffix('%'),
                    Forms\Components\TextInput::make('phase2_target_percent')
                        ->label('Phase 2 target (%)')
                        ->numeric()
                        ->suffix('%'),
                    Forms\Components\TextInput::make('min_trading_days')
                        ->numeric()
                        ->default(0),
                    Forms\Components\Select::make('drawdown_type')
                        ->options(['static' => 'Static', 'trailing' => 'Trailing'])
                        ->default('static')
                        ->native(false),
                    Forms\Components\TextInput::make('daily_drawdown_percent')
                        ->label('Daily loss limit (%)')
                        ->numeric()
                        ->default(5)
                        ->suffix('%'),
                    Forms\Components\TextInput::make('max_drawdown_percent')
                        ->label('Max loss limit (%)')
                        ->numeric()
                        ->default(10)
                        ->suffix('%'),
                    Forms\Components\TextInput::make('leverage')
                        ->numeric()
                        ->default(100)
                        ->prefix('1:'),
                    Forms\Components\Toggle::make('has_consistency_rule')
                        ->label('Consistency rule')
                        ->live(),
                    Forms\Components\TextInput::make('consistency_percent')
                        ->numeric()
                        ->suffix('%')
                        ->visible(fn (Forms\Get $get) => $get('has_consistency_rule')),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('challenge_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'two_step' => '2-Step',
                        'one_step' => '1-Step',
                        'instant' => 'Instant',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'two_step' => 'success',
                        'one_step' => 'info',
                        'instant' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('account_size')
                    ->money('USD', divideBy: 1)
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('profit_split_percent')
                    ->label('Split')
                    ->suffix('%')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('challenge_type')
                    ->options([
                        'two_step' => '2-Step',
                        'one_step' => '1-Step',
                        'instant' => 'Instant',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChallengePlans::route('/'),
            'create' => Pages\CreateChallengePlan::route('/create'),
            'edit' => Pages\EditChallengePlan::route('/{record}/edit'),
        ];
    }

    // Only staff with the 'manage challenge plans' permission (admins) may use this.
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage challenge plans') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('manage challenge plans') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('manage challenge plans') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('manage challenge plans') ?? false;
    }
}
