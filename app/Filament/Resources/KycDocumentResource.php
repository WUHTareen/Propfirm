<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KycDocumentResource\Pages;
use App\Models\KycDocument;
use App\Services\Kyc\KycService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class KycDocumentResource extends Resource
{
    protected static ?string $model = KycDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'KYC Queue';

    protected static ?int $navigationSort = 3;

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
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Trader')
                    ->description(fn (KycDocument $r) => $r->user?->email)
                    ->searchable(),
                Tables\Columns\TextColumn::make('document_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => str_replace('_', ' ', ucfirst($state))),
                Tables\Columns\TextColumn::make('original_name')->label('File')->limit(24),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'approved' => 'success', 'rejected' => 'danger', default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View file')
                    ->icon('heroicon-o-eye')
                    ->url(fn (KycDocument $record) => route('staff.kyc.download', $record), shouldOpenInNewTab: true),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (KycDocument $r) => $r->status !== 'approved')
                    ->requiresConfirmation()
                    ->action(function (KycDocument $record) {
                        app(KycService::class)->approve($record, auth()->user());
                        Notification::make()->title('Document approved — trader is KYC verified')->success()->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (KycDocument $r) => $r->status !== 'rejected')
                    ->form([
                        Forms\Components\Textarea::make('remarks')->label('Reason')->required(),
                    ])
                    ->action(function (KycDocument $record, array $data) {
                        app(KycService::class)->reject($record, auth()->user(), $data['remarks']);
                        Notification::make()->title('Document rejected')->danger()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKycDocuments::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('review kyc') ?? false;
    }
}
