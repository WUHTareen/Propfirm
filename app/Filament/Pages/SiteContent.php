<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Content Manager for the public marketing site. Everything the visitor sees —
 * hero copy, features, how-it-works, trading-rule cards, about, contact,
 * tracking IDs and legal text — is edited here and stored in `settings`.
 */
class SiteContent extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Site Content';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.site-content';

    public ?array $data = [];

    /**
     * Plain string settings: form key => [setting key, group].
     */
    private const STRINGS = [
        'hero_badge' => 'content',
        'hero_title' => 'content',
        'hero_subtitle' => 'content',
        'about_heading' => 'content',
        'about_body' => 'content',
        'contact_email' => 'content',
        'contact_telegram' => 'content',
        'contact_whatsapp' => 'content',
        'contact_address' => 'content',
        'trustpilot_url' => 'content',
        'facebook_pixel_id' => 'tracking',
        'google_analytics_id' => 'tracking',
        'tawk_to_id' => 'widgets',
        'legal_terms' => 'content',
        'legal_privacy' => 'content',
        'legal_refund' => 'content',
    ];

    public function mount(): void
    {
        $state = [];
        foreach (array_keys(self::STRINGS) as $key) {
            $state[$key] = Setting::get($key, '');
        }
        $state['features'] = Setting::get('features', []);
        $state['how_it_works'] = Setting::get('how_it_works', []);
        $state['prohibited_rules'] = Setting::get('prohibited_rules', []);
        $state['allowed_rules'] = Setting::get('allowed_rules', []);

        $this->form->fill($state);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Content')->tabs([
                    Tabs\Tab::make('Homepage')->schema([
                        Section::make('Hero')->schema([
                            TextInput::make('hero_badge')->label('Badge text')->maxLength(120),
                            TextInput::make('hero_title')->label('Headline')->maxLength(160),
                            Textarea::make('hero_subtitle')->label('Subheading')->rows(2)->maxLength(400),
                        ]),
                        Section::make('Feature highlights')->schema([
                            Repeater::make('features')->hiddenLabel()
                                ->schema([
                                    TextInput::make('title')->required()->maxLength(80),
                                    TextInput::make('body')->required()->maxLength(200),
                                ])->columns(2)->addActionLabel('Add feature')->defaultItems(0),
                        ]),
                        Section::make('How it works')->schema([
                            Repeater::make('how_it_works')->hiddenLabel()
                                ->schema([
                                    TextInput::make('step')->numeric()->required(),
                                    TextInput::make('title')->required()->maxLength(80),
                                    TextInput::make('body')->required()->maxLength(200),
                                ])->columns(3)->addActionLabel('Add step')->defaultItems(0),
                        ]),
                    ]),

                    Tabs\Tab::make('Trading Rules')->schema([
                        Section::make('Prohibited activities')->schema([
                            Repeater::make('prohibited_rules')->hiddenLabel()
                                ->schema([
                                    TextInput::make('title')->required()->maxLength(120),
                                    Textarea::make('body')->required()->rows(2)->maxLength(400),
                                ])->addActionLabel('Add prohibited activity')->defaultItems(0),
                        ]),
                        Section::make('Allowed activities')->schema([
                            Repeater::make('allowed_rules')->hiddenLabel()
                                ->schema([
                                    TextInput::make('title')->required()->maxLength(120),
                                    Textarea::make('body')->required()->rows(2)->maxLength(400),
                                ])->addActionLabel('Add allowed activity')->defaultItems(0),
                        ])->description('Per-plan targets and drawdown numbers come from Challenge Plans automatically.'),
                    ]),

                    Tabs\Tab::make('About & Contact')->schema([
                        Section::make('About page')->schema([
                            TextInput::make('about_heading')->maxLength(160),
                            Textarea::make('about_body')->rows(6)->helperText('Separate paragraphs with a blank line.'),
                        ]),
                        Section::make('Contact details')->columns(2)->schema([
                            TextInput::make('contact_email')->label('Support email')->email(),
                            TextInput::make('contact_address')->label('Address'),
                            TextInput::make('contact_telegram')->label('Telegram link')->url(),
                            TextInput::make('contact_whatsapp')->label('WhatsApp link')->url(),
                            TextInput::make('trustpilot_url')->label('Trustpilot URL')->url(),
                        ]),
                    ]),

                    Tabs\Tab::make('Tracking & Chat')->schema([
                        Section::make('Analytics & pixels')->columns(2)
                            ->description('Left blank, the scripts are not loaded on the site.')
                            ->schema([
                                TextInput::make('google_analytics_id')->label('Google Analytics ID')->placeholder('G-XXXXXXX'),
                                TextInput::make('facebook_pixel_id')->label('Facebook Pixel ID'),
                                TextInput::make('tawk_to_id')->label('Tawk.to widget ID')->placeholder('xxxxx/default')->columnSpanFull(),
                            ]),
                    ]),

                    Tabs\Tab::make('Legal')->schema([
                        Section::make('Legal documents')->schema([
                            Textarea::make('legal_terms')->label('Terms of Service')->rows(8),
                            Textarea::make('legal_privacy')->label('Privacy Policy')->rows(8),
                            Textarea::make('legal_refund')->label('Refund & Risk Disclosure')->rows(8),
                        ])->description('Separate paragraphs with a blank line.'),
                    ]),
                ])->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach (self::STRINGS as $key => $group) {
            Setting::set($key, $data[$key] ?? '', $group);
        }

        foreach (['features', 'how_it_works', 'prohibited_rules', 'allowed_rules'] as $key) {
            Setting::set($key, array_values($data[$key] ?? []), 'content');
        }

        Notification::make()->title('Site content saved')->success()->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label('Save changes')->submit('save'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage content') ?? false;
    }
}
