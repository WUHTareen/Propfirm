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
}
