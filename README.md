# Prop Firm Trading Platform

A proprietary trading firm ("prop firm") platform built on **Laravel 11 / PHP 8.3+**.

> **Important:** this is **not** a trading platform. All trading happens on
> MetaTrader 5 / MetaTrader 4. This application (1) sells evaluation
> challenges, (2) tracks each trader's account status and rule compliance, and
> (3) runs the business — orders, MT5 assignment, KYC, withdrawals, rewards.

See `CLAUDE.md` for the full product context, business model and roadmap.

## Status

Foundation milestone — build order steps 1–2:

- [x] Laravel 11 project scaffold
- [x] Complete database schema (19 migrations) + Eloquent models
- [x] Seed data: challenge plans (7 sizes × 3 types), settings, FAQs
- [x] Production config (`.env.example`) + deploy tooling (`deploy.sh`, `ops/`)
- [x] Auth (Fortify): register, login, email verification, password reset, 2FA
- [x] Roles (Spatie): admin / support / finance / trader + first admin user
- [x] Admin (Filament): panel at `/admin` (staff-gated) + Challenge Plan Builder
- [x] Buy Challenge flow + crypto checkout (coupons, points, manual/NOWPayments,
      webhook, admin Orders + Mark-paid → provisions a pending account)
- [x] Account Overview: metric tiles, profit-target & drawdown progress, SVG
      equity chart, per-account detail with credentials + rules
- [x] Admin: Trading Accounts — assign credentials, update metrics, pass phase /
      fund (auto certificate), breach; in-app notifications to the trader
- [x] KYC (locked until funded) — upload, admin approve/reject, private download
- [x] Withdrawals — eligibility (funded + KYC + profit), request, admin
      approve / mark-paid / reject
- [x] Rewards / points / affiliation — points wallet + ledger, share points,
      video/social submissions (admin approval credits points), referral links
      with click/signup/conversion tracking and commission on referred orders
- [x] Leaderboard (profit ranking + size filter), TradingView heatmap &
      economic calendar, MT5/MT4 downloads, in-app notifications centre
- [ ] Marketing website + CMS — next milestone
- [ ] MT5 automation (Module D, Option 2)

## Data model (high level)

`challenge_plans` is the single source of truth for pricing **and** evaluation
rules (admin-configurable — nothing hardcoded). Traders place `orders`, which
become `trading_accounts` (MT5/MT4 credentials encrypted at rest) that move
through phases; `equity_snapshots` power the equity curve and drawdown checks.
Supporting tables: `kyc_documents`, `withdrawals`, `certificates`,
`reward_points` (ledger), `reward_submissions`, `giveaway_entries`,
`affiliates`/`referrals`, `coupons`, `settings`, `faqs`, `testimonials`.

## Local development

```bash
composer install
cp .env.example .env
php artisan key:generate

# Quick start with SQLite:
#   set DB_CONNECTION=sqlite in .env, then
touch database/database.sqlite
php artisan migrate --seed

php artisan serve
```

For a MySQL setup (matches production), create the `propfirm` database and set
the `DB_*` values in `.env` instead.

## Deployment

The droplet pulls from Git and runs `./deploy.sh`. See `ops/README.md` for the
one-time Supervisor / cron / SSL setup.
