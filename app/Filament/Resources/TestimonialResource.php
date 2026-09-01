<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TestimonialResource\Pages;
use App\Models\Testimonial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TestimonialResource extends Resource
{
    protected static ?string $model = Testimonial::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Testimonials';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('author_name')
                ->required()
                ->maxLength(120),
            Forms\Components\TextInput::make('author_country')
                ->label('Country code')
                ->maxLength(2)
                ->helperText('ISO 2-letter code, e.g. AE, GB.'),
            Forms\Components\FileUpload::make('avatar_path')
                ->label('Photo')
                ->image()
                ->avatar()
                ->imageEditor()
                ->directory('site/testimonials')
                ->disk('public')
                ->maxSize(2048)
                ->helperText('Optional. Square image works best.'),
            Forms\Components\Textarea::make('body')
                ->required()
                ->rows(4)
                ->maxLength(1000)
                ->columnSpanFull(),
            Forms\Components\Select::make('rating')
                ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
                ->default(5)
                ->native(false)
                ->required(),
            Forms\Components\TextInput::make('sort_order')
                ->numeric()
                ->default(0)
                ->helperText('Lower numbers appear first.'),
            Forms\Components\Toggle::make('is_featured')
                ->label('Featured')
                ->helperText('Featured testimonials appear first.'),
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
                Tables\Columns\ImageColumn::make('avatar_path')
                    ->label('')
                    ->circular()
                    ->disk('public')
                    ->defaultImageUrl(url('/favicon.ico')),
                Tables\Columns\TextColumn::make('author_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('author_country')
                    ->label('Country')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('body')
                    ->limit(60)
                    ->wrap(),
                Tables\Columns\TextColumn::make('rating')
                    ->badge()
                    ->formatStateUsing(fn (int $state) => str_repeat('★', $state))
                    ->color('warning'),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Published')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Published'),
                Tables\Filters\TernaryFilter::make('is_featured')->label('Featured'),
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
            'index' => Pages\ListTestimonials::route('/'),
            'create' => Pages\CreateTestimonial::route('/create'),
            'edit' => Pages\EditTestimonial::route('/{record}/edit'),
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
