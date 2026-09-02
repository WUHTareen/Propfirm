<?php

namespace App\Http\Controllers;

use App\Models\ChallengePlan;
use App\Models\Faq;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Support\ChallengeCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;

class PublicController extends Controller
{
    public function home()
    {
        return view('public.home', [
            'plans' => $this->pricingMatrix(),
            'types' => $this->availableTypes(),
            'features' => Setting::get('features', []),
            'howItWorks' => Setting::get('how_it_works', []),
            'testimonials' => Testimonial::active()->orderByDesc('is_featured')->orderBy('sort_order')->take(6)->get(),
            'faqs' => Faq::active()->orderBy('sort_order')->take(6)->get(),
        ]);
    }

    public function pricing()
    {
        return view('public.pricing', [
            'plans' => $this->pricingMatrix(),
            'types' => $this->availableTypes(),
        ]);
    }

    public function rules()
    {
        return view('public.rules', [
            'types' => $this->availableTypes(),
            'plansByType' => $this->plansByType(),
            'prohibited' => Setting::get('prohibited_rules', []),
            'allowed' => Setting::get('allowed_rules', []),
        ]);
    }

    public function about()
    {
        return view('public.about', [
            'heading' => Setting::get('about_heading', 'About us'),
            'body' => Setting::get('about_body', ''),
            'howItWorks' => Setting::get('how_it_works', []),
        ]);
    }

    public function faq()
    {
        return view('public.faq', [
            'grouped' => Faq::active()->orderBy('sort_order')->get()->groupBy('category'),
        ]);
    }

    public function contact()
    {
        return view('public.contact', [
            'email' => Setting::get('contact_email', 'support@example.com'),
            'telegram' => Setting::get('contact_telegram'),
            'whatsapp' => Setting::get('contact_whatsapp'),
            'address' => Setting::get('contact_address'),
        ]);
    }

    public function contactSubmit(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:4000'],
        ]);

        // Simple abuse guard — 5 messages per 10 minutes per IP.
        $key = 'contact:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withInput()->withErrors(['message' => 'Too many messages. Please try again later.']);
        }
        RateLimiter::hit($key, 600);

        $to = Setting::get('contact_email', config('mail.from.address'));

        // Best-effort: mail is not configured in every environment yet, so a
        // failure here must not 500 the public site.
        try {
            if ($to) {
                Notification::route('mail', $to)->notify(new \App\Notifications\ContactMessage($data));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('status', 'Thanks — your message has been sent. We\'ll get back to you shortly.');
    }

    public function legal(string $doc)
    {
        $map = [
            'terms' => ['Terms of Service', 'legal_terms'],
            'privacy' => ['Privacy Policy', 'legal_privacy'],
            'refund' => ['Refund & Risk Disclosure', 'legal_refund'],
        ];

        abort_unless(isset($map[$doc]), 404);
        [$title, $key] = $map[$doc];

        return view('public.legal', [
            'title' => $title,
            'body' => Setting::get($key, ''),
        ]);
    }

    // ---- Data helpers ------------------------------------------------------

    /**
     * Challenge types that actually have active plans, in canonical order.
     *
     * @return array<string, string>
     */
    private function availableTypes(): array
    {
        return ChallengeCatalog::availableTypes();
    }

    /**
     * All active plans keyed by [challenge_type][account_size] → plan.
     * Powers the pricing tables — one row per size, one column per type.
     *
     * @return array{sizes: array<int>, byType: array<string, array<int, ChallengePlan>>}
     */
    private function pricingMatrix(): array
    {
        $plans = ChallengePlan::active()->orderBy('account_size')->get();

        $sizes = $plans->pluck('account_size')->map(fn ($s) => (int) $s)->unique()->sort()->values()->all();

        $byType = [];
        foreach ($plans as $plan) {
            $byType[$plan->challenge_type][(int) $plan->account_size] = $plan;
        }

        return ['sizes' => $sizes, 'byType' => $byType];
    }

    /**
     * One representative active plan per challenge type (smallest size),
     * used to show the rule set on the Trading Rules page.
     *
     * @return array<string, ChallengePlan>
     */
    private function plansByType(): array
    {
        return ChallengeCatalog::plansByType();
    }
}
