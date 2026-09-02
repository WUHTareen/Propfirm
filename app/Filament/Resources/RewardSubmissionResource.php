<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RewardSubmissionResource\Pages;
use App\Models\RewardSubmission;
use App\Services\Rewards\RewardSubmissionService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RewardSubmissionResource extends Resource
{
    protected static ?string $model = RewardSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Rewards';

    protected static ?string $navigationLabel = 'Reward Submissions';

    protected static ?int $navigationSort = 1;

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
                    ->description(fn (RewardSubmission $r) => $r->user?->email)
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => str_replace('_', ' ', ucfirst($state))),
                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('platform')->placeholder('—'),
                Tables\Columns\TextColumn::make('link')
                    ->url(fn (RewardSubmission $r) => $r->link, shouldOpenInNewTab: true)
                    ->limit(28)->color('primary'),
                Tables\Columns\TextColumn::make('points_value')->label('Points')->suffix(' pts'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'approved' => 'success', 'rejected' => 'danger', default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d M Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options([
                    'video_review' => 'Video review', 'social_media' => 'Social media',
                    'task' => 'Reward request',
                ]),
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (RewardSubmission $r) => $r->status === 'pending')
                    ->modalDescription('Points are credited to the trader on approval. 100 points = $1.00.')
                    ->form(fn (RewardSubmission $r) => [
                        Forms\Components\TextInput::make('points')
                            ->label('Points to credit')
                            ->helperText(fn () => $r->type === 'task'
                                ? 'This request arrived without an amount — set what it is worth.'
                                : 'Prefilled from settings; change it only if this case is different.')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->default($r->points_value),
                    ])
                    ->action(function (RewardSubmission $record, array $data) {
                        app(RewardSubmissionService::class)->approve($record, auth()->user(), (int) $data['points']);
                        Notification::make()->title("Approved — {$record->points_value} points credited")->success()->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (RewardSubmission $r) => $r->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('remarks')->label('Reason')->required(),
                    ])
                    ->action(function (RewardSubmission $record, array $data) {
                        app(RewardSubmissionService::class)->reject($record, auth()->user(), $data['remarks']);
                        Notification::make()->title('Submission rejected')->danger()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRewardSubmissions::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage rewards') ?? false;
    }
}
