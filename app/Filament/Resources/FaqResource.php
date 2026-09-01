<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Models\Faq;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'FAQs';

    protected static ?int $navigationSort = 2;

    private const CATEGORIES = [
        'general' => 'General',
        'accounts' => 'Accounts',
        'payments' => 'Payments',
        'payouts' => 'Payouts',
        'kyc' => 'KYC',
        'rewards' => 'Rewards',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('question')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Forms\Components\Textarea::make('answer')
                ->required()
                ->rows(4)
                ->columnSpanFull(),
            Forms\Components\Select::make('category')
                ->options(self::CATEGORIES)
                ->default('general')
                ->native(false)
                ->required(),
            Forms\Components\TextInput::make('sort_order')
                ->numeric()
                ->default(0)
                ->helperText('Lower numbers appear first.'),
            Forms\Components\Toggle::make('is_active')
                ->label('Published')
                ->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('question')
                    ->searchable()
                    ->limit(60)
                    ->wrap(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => self::CATEGORIES[$state] ?? ucfirst($state)),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Published')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')->options(self::CATEGORIES),
                Tables\Filters\TernaryFilter::make('is_active')->label('Published'),
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
            'index' => Pages\ListFaqs::route('/'),
            'create' => Pages\CreateFaq::route('/create'),
            'edit' => Pages\EditFaq::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage content') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('manage content') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('manage content') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('manage content') ?? false;
    }
}
