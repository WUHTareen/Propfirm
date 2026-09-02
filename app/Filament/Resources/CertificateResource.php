<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CertificateResource\Pages;
use App\Models\Certificate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * Certificates issued to traders. These are normally created automatically
 * when an account passes a phase or becomes funded, so this screen is mostly
 * for looking things up — plus issuing a one-off achievement by hand, which
 * the automatic path has no way to cover.
 */
class CertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Rewards';

    protected static ?string $navigationLabel = 'Certificates';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Certificate')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Trader')
                        ->relationship('user', 'email')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('type')
                        ->required()
                        ->native(false)
                        ->default('achievement')
                        ->options([
                            'phase_pass' => 'Phase passed',
                            'funded' => 'Funded account',
                            'payout' => 'Payout',
                            'achievement' => 'Achievement',
                        ]),
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('amount')
                        ->numeric()
                        ->prefix('$')
                        ->helperText('Leave blank when the certificate is not about money.'),
                    Forms\Components\DateTimePicker::make('issued_at')
                        ->required()
                        ->default(now()),
                    Forms\Components\TextInput::make('certificate_number')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->default(fn () => 'CERT-'.Str::upper(Str::random(8)))
                        ->helperText('Must be unique. Prefilled with a generated number.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('issued_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('certificate_number')
                    ->label('Number')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Trader')
                    ->description(fn (Certificate $c) => $c->user?->email)
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'phase_pass' => 'Phase passed',
                        'funded' => 'Funded',
                        'payout' => 'Payout',
                        default => 'Achievement',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'funded' => 'success',
                        'payout' => 'info',
                        'phase_pass' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('title')->limit(36),
                Tables\Columns\TextColumn::make('amount')->money('USD')->placeholder('—'),
                Tables\Columns\TextColumn::make('issued_at')->dateTime('d M Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options([
                    'phase_pass' => 'Phase passed',
                    'funded' => 'Funded account',
                    'payout' => 'Payout',
                    'achievement' => 'Achievement',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->modalDescription('The trader will stop seeing this on their Achievement page.'),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertificates::route('/'),
            'create' => Pages\CreateCertificate::route('/create'),
            'edit' => Pages\EditCertificate::route('/{record}/edit'),
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
