<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Admin-managed payment settings: the active gateway and (for manual mode) the
 * wallet addresses traders send crypto to. Gateway API secrets stay in .env.
 */
class PaymentSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Setup';

    protected static ?string $navigationLabel = 'Payment Settings';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.payment-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $wallets = [];
        foreach (array_keys(config('payments.methods')) as $method) {
            $wallets["wallet_{$method}"] = Setting::get("wallet_{$method}")
                ?: config("payments.gateways.manual.wallets.{$method}", '');
        }

        $this->form->fill(array_merge([
            'payment_gateway' => Setting::get('payment_gateway') ?: config('payments.default', 'manual'),
        ], $wallets));
    }

    public function form(Form $form): Form
    {
        $walletFields = [];
        foreach (config('payments.methods') as $key => $method) {
            $walletFields[] = TextInput::make("wallet_{$key}")
                ->label($method['label'])
                ->placeholder('Wallet address')
                ->maxLength(255);
        }

        return $form
            ->schema([
                Section::make('Gateway')
                    ->description('API secret keys (e.g. NOWPayments) are set in .env for security, not here.')
                    ->schema([
                        Select::make('payment_gateway')
                            ->label('Active gateway')
                            ->options([
                                'manual' => 'Manual (admin confirms payments)',
                                'nowpayments' => 'NOWPayments (automated)',
                            ])
                            ->native(false)
                            ->required(),
                    ]),

                Section::make('Manual wallet addresses')
                    ->description('Shown to traders on the payment page when the manual gateway is active.')
                    ->columns(2)
                    ->schema($walletFields),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('payment_gateway', $data['payment_gateway'], 'payments');

        foreach (array_keys(config('payments.methods')) as $method) {
            Setting::set("wallet_{$method}", $data["wallet_{$method}"] ?? '', 'payments');
        }

        Notification::make()->title('Payment settings saved')->success()->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label('Save changes')->submit('save'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
