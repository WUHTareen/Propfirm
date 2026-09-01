<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ContactMessage;
use Database\Seeders\ChallengePlanSeeder;
use Database\Seeders\FaqSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SiteContentSeeder;
use Database\Seeders\TestimonialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MarketingSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolePermissionSeeder::class,
            ChallengePlanSeeder::class,
            SiteContentSeeder::class,
            FaqSeeder::class,
            TestimonialSeeder::class,
        ]);
    }

    // ---- Public pages ------------------------------------------------------

    public function test_home_page_shows_hero_pricing_and_testimonials(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Prove your edge')
            ->assertSee('How it works')
            ->assertSee('Ahmed R.'); // seeded testimonial
    }

    public function test_pricing_page_lists_plans_from_admin_config(): void
    {
        $this->get(route('pricing'))
            ->assertOk()
            ->assertSee('Challenge pricing')
            ->assertSee('$10K'); // an account size from the seeded catalogue
    }

    public function test_rules_page_shows_prohibited_and_conditions(): void
    {
        $this->get(route('rules'))
            ->assertOk()
            ->assertSee('Trading rules')
            ->assertSee('Grid Trading')       // seeded prohibited rule
            ->assertSee('Prohibited');
    }

    public function test_static_pages_render(): void
    {
        $this->get(route('about'))->assertOk()->assertSee('How it works');
        $this->get(route('faq'))->assertOk()->assertSee('Frequently asked');
        $this->get(route('contact'))->assertOk()->assertSee('Contact us');
    }

    public function test_legal_pages_render_and_unknown_is_404(): void
    {
        $this->get(route('legal.terms'))->assertOk()->assertSee('Terms of Service');
        $this->get(route('legal.privacy'))->assertOk()->assertSee('Privacy Policy');
        $this->get(route('legal.refund'))->assertOk()->assertSee('Refund & Risk Disclosure');
        $this->get('/legal/nonsense')->assertNotFound();
    }

    // ---- Contact form ------------------------------------------------------

    public function test_contact_form_sends_a_message(): void
    {
        Notification::fake();

        $this->post(route('contact.submit'), [
            'name' => 'Jane Trader',
            'email' => 'jane@example.com',
            'subject' => 'Question',
            'message' => 'How do payouts work?',
        ])->assertRedirect()->assertSessionHas('status');

        Notification::assertSentOnDemand(ContactMessage::class);
    }

    public function test_contact_form_validates(): void
    {
        $this->post(route('contact.submit'), ['name' => '', 'email' => 'nope', 'message' => ''])
            ->assertSessionHasErrors(['name', 'email', 'message']);
    }

    // ---- Admin CMS ---------------------------------------------------------

    public function test_content_staff_can_reach_cms_pages(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->get('/admin/faqs')->assertOk();
        $this->actingAs($admin)->get('/admin/testimonials')->assertOk();
        $this->actingAs($admin)->get('/admin/site-content')->assertOk();
    }

    public function test_finance_staff_cannot_manage_content(): void
    {
        // Finance has back-office access but not the 'manage content' permission.
        $finance = User::factory()->create(['email_verified_at' => now()]);
        $finance->assignRole('finance');

        $this->actingAs($finance)->get('/admin/faqs')->assertForbidden();
        $this->actingAs($finance)->get('/admin/site-content')->assertForbidden();
    }

    // ---- CMS control: toggles, headings, images ----------------------------

    public function test_a_home_section_can_be_hidden_from_the_cms(): void
    {
        $this->get(route('home'))->assertSee('Trusted by traders');

        \App\Models\Setting::set('show_testimonials', false, 'content');

        $this->get(route('home'))->assertDontSee('Trusted by traders');
    }

    public function test_section_headings_and_cta_are_editable(): void
    {
        \App\Models\Setting::set('howitworks_heading', 'Your path to funding', 'content');
        \App\Models\Setting::set('cta_heading', 'Join hundreds of funded traders', 'content');

        $this->get(route('home'))
            ->assertSee('Your path to funding')
            ->assertSee('Join hundreds of funded traders')
            ->assertDontSee('How it works');
    }

    public function test_uploaded_logo_and_hero_image_appear(): void
    {
        \App\Models\Setting::set('logo_path', 'site/logo.png', 'content');
        \App\Models\Setting::set('hero_image_path', 'site/hero.jpg', 'content');

        $this->get(route('home'))
            ->assertSee('/storage/site/logo.png', false)
            ->assertSee('/storage/site/hero.jpg', false);
    }

    public function test_pricing_is_single_source_home_and_pricing_agree(): void
    {
        // Change a plan's price; both the home preview and the pricing page
        // must reflect it, proving they read the same source.
        $plan = \App\Models\ChallengePlan::where('challenge_type', 'two_step')
            ->orderBy('account_size')->first();
        $plan->update(['price' => 137]);

        $this->get(route('home'))->assertSee('$137');
        $this->get(route('pricing'))->assertSee('$137');
    }
}
