<?php

namespace App\Filament\Pages;

use App\Models\Faq;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Content Manager for the public marketing site. Every visible thing — copy,
 * images, section headings, and which sections show — is edited here and
 * stored in `settings`. Pricing/rules numbers live in Challenge Plans.
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
     * Plain text/flag settings: form key => group. Values are stored as-is
     * (strings, or booleans for the show_* toggles).
     */
    private const FIELDS = [
        // Branding
        'footer_tagline' => 'content',
        // Hero
        'hero_badge' => 'content',
        'hero_title' => 'content',
        'hero_subtitle' => 'content',
        'hero_primary_cta' => 'content',
        'hero_secondary_cta' => 'content',
        // Section headings + toggles
        'show_features' => 'content',
        'howitworks_heading' => 'content',
        'howitworks_subheading' => 'content',
        'show_howitworks' => 'content',
        'pricing_heading' => 'content',
        'pricing_subheading' => 'content',
        'show_pricing_preview' => 'content',
        'testimonials_heading' => 'content',
        'show_testimonials' => 'content',
        'faq_heading' => 'content',
        'show_faq' => 'content',
        'cta_heading' => 'content',
        'cta_body' => 'content',
        'cta_button' => 'content',
        // About
        'about_heading' => 'content',
        'about_body' => 'content',
        // Contact
        'contact_email' => 'content',
        'contact_telegram' => 'content',
        'contact_whatsapp' => 'content',
        'contact_address' => 'content',
        'trustpilot_url' => 'content',
        // Tracking
        'facebook_pixel_id' => 'tracking',
        'google_analytics_id' => 'tracking',
        'tawk_to_id' => 'widgets',
        // Legal
        'legal_terms' => 'content',
        'legal_privacy' => 'content',
        'legal_refund' => 'content',
    ];

    private const FAQ_CATEGORIES = [
        'general' => 'General',
        'accounts' => 'Accounts',
        'payments' => 'Payments',
        'payouts' => 'Payouts',
        'kyc' => 'KYC',
        'rewards' => 'Rewards',
    ];

    private const IMAGES = ['logo_path', 'hero_image_path', 'about_image_path'];
    private const REPEATERS = ['features', 'how_it_works', 'prohibited_rules', 'allowed_rules'];
    private const BOOLS = ['show_features', 'show_howitworks', 'show_pricing_preview', 'show_testimonials', 'show_faq', 'show_cta'];

    public function mount(): void
    {
        $state = [];
        foreach (array_keys(self::FIELDS) as $key) {
            $default = in_array($key, self::BOOLS, true) ? true : '';
            $state[$key] = Setting::get($key, $default);
        }
        $state['show_cta'] = (bool) Setting::get('show_cta', true);
        foreach (self::IMAGES as $key) {
            $state[$key] = Setting::get($key);
        }
        foreach (self::REPEATERS as $key) {
            $state[$key] = Setting::get($key, []);
        }

        // FAQs are a real table — load them so they can be managed inline.
        $state['faqs'] = Faq::orderBy('sort_order')->get()
            ->map(fn (Faq $f) => [
                'id' => $f->id,
                'question' => $f->question,
                'answer' => $f->answer,
                'category' => $f->category,
                'is_active' => (bool) $f->is_active,
            ])->all();

        $this->form->fill($state);
    }

    public function form(Form $form): Form
    {
        $image = fn (string $key, string $label, string $help = '') => FileUpload::make($key)
            ->label($label)
            ->image()
            ->imageEditor()
            ->directory('site')
            ->disk('public')
            ->maxSize(3072)
            ->helperText($help ?: 'PNG, JPG or WEBP. Max 3 MB.');

        return $form
            ->schema([
                Tabs::make('Content')->tabs([

                    Tabs\Tab::make('Branding')->icon('heroicon-o-sparkles')->schema([
                        Section::make('Logo & footer')->schema([
                            $image('logo_path', 'Site logo', 'Shown in the header and footer. Leave empty to use the text logo.'),
                            TextInput::make('footer_tagline')->label('Footer tagline')->maxLength(200),
                        ]),
                    ]),

                    Tabs\Tab::make('Hero')->icon('heroicon-o-rocket-launch')->schema([
                        Section::make('Headline')->schema([
                            TextInput::make('hero_badge')->label('Badge text')->maxLength(120),
                            TextInput::make('hero_title')->label('Headline')->maxLength(160),
                            Textarea::make('hero_subtitle')->label('Subheading')->rows(2)->maxLength(400),
                            Grid::make(2)->schema([
                                TextInput::make('hero_primary_cta')->label('Primary button')->placeholder('Start a challenge'),
                                TextInput::make('hero_secondary_cta')->label('Secondary button')->placeholder('View pricing'),
                            ]),
                            $image('hero_image_path', 'Hero image (optional)', 'Shown beside the headline if set.'),
                        ]),
                    ]),

                    Tabs\Tab::make('Homepage sections')->icon('heroicon-o-squares-2x2')->schema([
                        Section::make('Feature highlights')
                            ->headerActions([])
                            ->schema([
                                Toggle::make('show_features')->label('Show this section')->inline(false),
                                Repeater::make('features')->hiddenLabel()
                                    ->schema([
                                        TextInput::make('title')->required()->maxLength(80),
                                        TextInput::make('body')->required()->maxLength(200),
                                    ])->columns(2)->addActionLabel('Add feature')->defaultItems(0)->collapsible(),
                            ]),
                        Section::make('How it works')->schema([
                            Toggle::make('show_howitworks')->label('Show this section')->inline(false),
                            Grid::make(2)->schema([
                                TextInput::make('howitworks_heading')->label('Heading')->placeholder('How it works'),
                                TextInput::make('howitworks_subheading')->label('Subheading'),
                            ]),
                            Repeater::make('how_it_works')->hiddenLabel()
                                ->schema([
                                    TextInput::make('step')->numeric()->required(),
                                    TextInput::make('title')->required()->maxLength(80),
                                    TextInput::make('body')->required()->maxLength(200),
                                ])->columns(3)->addActionLabel('Add step')->defaultItems(0)->collapsible(),
                        ]),
                        Section::make('Pricing preview')->schema([
                            Toggle::make('show_pricing_preview')->label('Show this section')->inline(false),
                            Grid::make(2)->schema([
                                TextInput::make('pricing_heading')->label('Heading')->helperText('Leave blank to auto-use the challenge type name.'),
                                TextInput::make('pricing_subheading')->label('Subheading'),
                            ]),
                        ])->description('Prices themselves come from Challenge Plans and update everywhere automatically.'),
                        Section::make('Testimonials')->schema([
                            Toggle::make('show_testimonials')->label('Show this section')->inline(false),
                            TextInput::make('testimonials_heading')->label('Heading')->placeholder('Trusted by traders'),
                        ])->description('Add and edit testimonials (with photos) under Content → Testimonials.'),
                        Section::make('FAQ')->schema([
                            Toggle::make('show_faq')->label('Show the FAQ preview on the homepage')->inline(false),
                            TextInput::make('faq_heading')->label('Homepage heading')->placeholder('Frequently asked'),
                            Repeater::make('faqs')->hiddenLabel()
                                ->schema([
                                    Hidden::make('id'),
                                    TextInput::make('question')->required()->maxLength(255)->columnSpanFull(),
                                    Textarea::make('answer')->required()->rows(3)->columnSpanFull(),
                                    Select::make('category')->options(self::FAQ_CATEGORIES)->default('general')->native(false)->required(),
                                    Toggle::make('is_active')->label('Published')->default(true)->inline(false),
                                ])
                                ->columns(2)
                                ->addActionLabel('Add question')
                                ->defaultItems(0)
                                ->collapsible()
                                ->reorderable()
                                ->itemLabel(fn (array $state) => $state['question'] ?? 'New question'),
                        ])->description('These questions appear on the FAQ page and the homepage preview. Drag to reorder.'),
                        Section::make('Call to action (bottom)')->schema([
                            Toggle::make('show_cta')->label('Show this section')->inline(false),
                            Grid::make(2)->schema([
                                TextInput::make('cta_heading')->label('Heading')->placeholder('Ready to get funded?'),
                                TextInput::make('cta_button')->label('Button label')->placeholder('Start a challenge'),
                            ]),
                            Textarea::make('cta_body')->label('Text')->rows(2),
                        ]),
                    ]),

                    Tabs\Tab::make('Trading Rules')->icon('heroicon-o-shield-check')->schema([
                        Section::make('Prohibited activities')->schema([
                            Repeater::make('prohibited_rules')->hiddenLabel()
                                ->schema([
                                    TextInput::make('title')->required()->maxLength(120),
                                    Textarea::make('body')->required()->rows(2)->maxLength(400),
                                ])->addActionLabel('Add prohibited activity')->defaultItems(0)->collapsible()->itemLabel(fn (array $state) => $state['title'] ?? null),
                        ]),
                        Section::make('Allowed activities')->schema([
                            Repeater::make('allowed_rules')->hiddenLabel()
                                ->schema([
                                    TextInput::make('title')->required()->maxLength(120),
                                    Textarea::make('body')->required()->rows(2)->maxLength(400),
                                ])->addActionLabel('Add allowed activity')->defaultItems(0)->collapsible()->itemLabel(fn (array $state) => $state['title'] ?? null),
                        ])->description('Per-plan targets and drawdown numbers come from Challenge Plans automatically.'),
                    ]),

                    Tabs\Tab::make('About & Contact')->icon('heroicon-o-identification')->schema([
                        Section::make('About page')->schema([
                            TextInput::make('about_heading')->maxLength(160),
                            Textarea::make('about_body')->rows(6)->helperText('Separate paragraphs with a blank line.'),
                            $image('about_image_path', 'About image (optional)'),
                        ]),
                        Section::make('Contact details')->columns(2)->schema([
                            TextInput::make('contact_email')->label('Support email')->email(),
                            TextInput::make('contact_address')->label('Address'),
                            TextInput::make('contact_telegram')->label('Telegram link')->url(),
                            TextInput::make('contact_whatsapp')->label('WhatsApp link')->url(),
                            TextInput::make('trustpilot_url')->label('Trustpilot URL')->url(),
                        ]),
                    ]),

                    Tabs\Tab::make('Tracking & Chat')->icon('heroicon-o-chart-bar')->schema([
                        Section::make('Analytics & pixels')->columns(2)
                            ->description('Left blank, the scripts are not loaded on the site.')
                            ->schema([
                                TextInput::make('google_analytics_id')->label('Google Analytics ID')->placeholder('G-XXXXXXX'),
                                TextInput::make('facebook_pixel_id')->label('Facebook Pixel ID'),
                                TextInput::make('tawk_to_id')->label('Tawk.to widget ID')->placeholder('xxxxx/default')->columnSpanFull(),
                            ]),
                    ]),

                    Tabs\Tab::make('Legal')->icon('heroicon-o-document-text')->schema([
                        Section::make('Legal documents')->schema([
                            Textarea::make('legal_terms')->label('Terms of Service')->rows(8),
                            Textarea::make('legal_privacy')->label('Privacy Policy')->rows(8),
                            Textarea::make('legal_refund')->label('Refund & Risk Disclosure')->rows(8),
                        ])->description('Separate paragraphs with a blank line.'),
                    ]),

                ])->columnSpanFull()->persistTabInQueryString(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach (self::FIELDS as $key => $group) {
            $value = $data[$key] ?? '';
            if (in_array($key, self::BOOLS, true)) {
                $value = (bool) $value;
            }
            Setting::set($key, $value, $group);
        }

        foreach (self::IMAGES as $key) {
            Setting::set($key, $data[$key] ?? '', 'content');
        }

        foreach (self::REPEATERS as $key) {
            Setting::set($key, array_values($data[$key] ?? []), 'content');
        }

        $this->syncFaqs($data['faqs'] ?? []);

        Notification::make()->title('Site content saved')->success()->send();
    }

    /**
     * Persist the inline FAQ repeater to the faqs table: upsert each row (keeping
     * repeater order as sort_order) and delete any rows removed in the form.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncFaqs(array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $order => $row) {
            if (empty($row['question']) || empty($row['answer'])) {
                continue;
            }

            $faq = Faq::updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'question' => $row['question'],
                    'answer' => $row['answer'],
                    'category' => $row['category'] ?? 'general',
                    'is_active' => (bool) ($row['is_active'] ?? true),
                    'sort_order' => $order,
                ],
            );

            $keptIds[] = $faq->id;
        }

        Faq::whereNotIn('id', $keptIds ?: [0])->delete();
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
