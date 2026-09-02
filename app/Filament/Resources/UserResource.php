<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\Permission\Models\Role;

/**
 * Traders and staff in one place: who they are, whether they can log in, and
 * what they are allowed to do.
 *
 * Roles are the back office's access control, so handing one out is a
 * privileged act — only someone with 'manage staff' can change them, and
 * nobody can strip their own admin role and lock themselves out.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'People';

    protected static ?string $navigationLabel = 'Users';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'email';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identity')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('phone')
                        ->tel()
                        ->maxLength(40),
                    Forms\Components\TextInput::make('country')
                        ->maxLength(2)
                        ->helperText('Two-letter country code, e.g. AE.'),
                ]),

            Forms\Components\Section::make('Access')
                ->columns(2)
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Switching this off blocks the account without deleting anything.'),
                    Forms\Components\Select::make('kyc_status')
                        ->native(false)
                        ->options([
                            'unverified' => 'Unverified',
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                        ])
                        ->helperText('Set by the KYC queue — change here only to correct a mistake.'),
                    Forms\Components\Select::make('roles')
                        ->label('Roles')
                        ->multiple()
                        ->preload()
                        ->relationship('roles', 'name')
                        ->disabled(fn () => ! (auth()->user()?->can('manage staff') ?? false))
                        ->helperText(fn () => (auth()->user()?->can('manage staff') ?? false)
                            ? 'admin / support / finance grant back-office access. trader is the customer role.'
                            : 'Only an administrator can change roles.')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->dehydrated(fn (?string $state) => filled($state))
                        ->required(fn (string $operation) => $operation === 'create')
                        ->helperText('Leave blank when editing to keep the current password.')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Rewards')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('points_balance')
                        ->numeric()
                        ->disabled()
                        ->helperText('Cached from the reward_points ledger — adjust via Reward Submissions, not here.'),
                    Forms\Components\TextInput::make('referral_code')
                        ->disabled()
                        ->helperText('Generated on signup.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (User $u) => $u->email),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'admin' => 'danger',
                        'support', 'finance' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('country')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('kyc_status')
                    ->label('KYC')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('trading_accounts_count')
                    ->label('Accounts')
                    ->counts('tradingAccounts')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('points_balance')
                    ->label('Points')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->label('Role'),
                Tables\Filters\SelectFilter::make('kyc_status')->options([
                    'unverified' => 'Unverified',
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ]),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Suspending is the everyday action; deleting a trader with
                // orders and accounts behind them almost never is.
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn (User $u) => $u->is_active ? 'Suspend' : 'Reactivate')
                    ->icon(fn (User $u) => $u->is_active ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
                    ->color(fn (User $u) => $u->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalDescription(fn (User $u) => $u->is_active
                        ? 'They will not be able to log in. Nothing is deleted.'
                        : 'They will be able to log in again.')
                    ->visible(fn (User $u) => auth()->id() !== $u->id)
                    ->action(function (User $record) {
                        $record->forceFill(['is_active' => ! $record->is_active])->save();

                        Notification::make()
                            ->title($record->is_active ? 'Account reactivated' : 'Account suspended')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (User $u) => auth()->id() !== $u->id),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getRelations(): array
    {
        return [
            UserResource\RelationManagers\TradingAccountsRelationManager::class,
            UserResource\RelationManagers\OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    /**
     * Roles that exist to be granted, for the staff-role picker.
     */
    public static function assignableRoles(): array
    {
        return Role::query()->orderBy('name')->pluck('name', 'name')->all();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage users') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('manage users') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('manage users') ?? false;
    }

    public static function canDelete($record): bool
    {
        // Never let someone delete the account they are signed in with.
        return (auth()->user()?->can('manage users') ?? false)
            && auth()->id() !== $record->id;
    }
}
