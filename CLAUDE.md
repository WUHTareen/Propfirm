# CLAUDE.md — Prop Firm Trading Platform

> Project context file. Read this first in every session before writing code.

---

## 1. What This Project Is

A **proprietary trading firm (prop firm) platform** built on **Laravel**, for a client who runs a funded-trader business.

**Critical to understand:** This website is **NOT a trading platform**. All actual trading happens on **MetaTrader 5 / MetaTrader 4** desktop/mobile apps. This platform does three things:

1. **Sells challenges** — e-commerce style checkout where traders buy an evaluation
2. **Tracks performance** — displays each trader's account status, phase progress, profit targets, drawdown, rule compliance
3. **Runs the business** — orders, MT5 account assignment, KYC, withdrawals, rewards, reporting

### Business Model (Trader Journey)

| Step | Stage | What Happens |
|---|---|---|
| 1 | Sign Up | Trader registers, lands in personal dashboard |
| 2 | Buy Challenge | Selects account size ($3K–$200K), platform (MT5/MT4), challenge type (2-Step / 1-Step / Instant), pays via crypto |
| 3 | Account Delivery | Admin issues MT5 login credentials from the firm's brokerage. Trader downloads MT5 from the dashboard |
| 4 | Evaluation | Trader must hit profit target (e.g. 7% Phase 1, 5% Phase 2) while staying inside 5% daily loss and 10% max loss, min trading days, and prohibited-strategy rules |
| 5 | Funded Account | On passing all phases, trader completes KYC and receives a funded account |
| 6 | Payout | Trader requests withdrawal from dashboard; admin reviews eligibility (phase, KYC, trading days) and pays agreed profit % (e.g. 80%). Remainder = firm revenue |

**Revenue streams:** (a) challenge fees — the majority, since most traders fail and repurchase; (b) firm's share of funded-trader profits.

### Reference Platform

`tradeprofunded.com` — analysed screen-by-screen. Their dashboard structure is the model to follow (they also run Laravel; their broker appears to be IC Markets).

---

## 2. Reference Platform Rules (from tradeprofunded.com)

**2-Step evaluation:**
- Phase 1 profit target: 7%
- Phase 2 profit target: 5%
- Max static loss: 10%
- Daily loss: 5%
- Leverage: 1:100
- Min trading days: 4 per phase
- No consistency rule

**Account sizes:** 3,000 / 5,000 / 10,000 / 25,000 / 50,000 / 100,000 / 200,000 USD
**Challenge types:** 2-STEP, 1-STEP, INSTANT
**Platforms:** MetaTrader 5, MetaTrader 4

**Prohibited activities** (each shown as an expandable card with explanation):
- High-Frequency Trading (HFT) — no open+close within 15 seconds
- Grid Trading — no 3+ trades on same pair in same direction simultaneously
- Hedging — no buy and sell on same pair at same time
- Expert Advisors (EAs) / bot misuse — no exploiting price gaps or low-liquidity (e.g. rollover)
- System Exploitation & Abuse — no exploiting platform errors, latency, or weaknesses; changing MT5/MT4 password to block monitoring is prohibited
- Martingale Strategy — no increasing trade size after losses
- Inactivity — accounts must remain active
- Account Sharing — individual use only

Tabs also exist for **Allowed Activities** and **Trading Conditions**.

> All of the above must be **admin-configurable**, not hardcoded. The client will set their own numbers.

---

## 3. Application Scope

### Module A — Marketing Website (Public)

Dark-theme, conversion-focused, fully responsive, SEO-ready.
- Home: hero, live pricing tables per account size, 4-step "how it works", features, testimonials, Trustpilot embed, FAQ, CTAs
- Challenges / Pricing — dynamic, pulled from admin (no code changes to update prices)
- Trading Rules — challenge-type tabs, expandable rule cards
- About, FAQ, Contact, Affiliate landing, Blog
- Legal: Terms, Privacy, Refund & Risk Disclosure
- Facebook Pixel, Google Analytics, conversion tracking
- Optional: GSAP animations / 3D-WebGL hero to stand out from templated competitors

### Module B — Trader Dashboard (Client Side)

Sidebar navigation, exactly matching the reference platform's structure:

| Route | Section | Functionality |
|---|---|---|
| `/dashboard/overview` | **Account Overview** | All purchased accounts: status, phase, balance, profit-target progress, drawdown usage, equity chart. Empty state → "Buy Now" CTA |
| `/dashboard/buynow` | **Buy Now** | Step flow: Account Size → Platform (MT5/MT4) → Challenge Type (2-Step/1-Step/Instant) → Payment Method → coupon / cashback points → checkout |
| `/dashboard/orders` | **Orders** | Order history table, searchable by Order ID, with status and payment reference |
| `/dashboard/certificates` | **Achievement** | Payout certificates generated on qualifying milestones. "Request Reward" button + form → admin review. History table: Reward ID, Categories, Current Status, Remarks, Reward Amount, Date |
| `/dashboard/withdrawal` | **Withdrawals** | *Account Withdrawal Status* table: Account ID, Account Size, Challenge Type, Phase, KYC, Trading Days, Eligible. Plus *Withdrawal History*: ID, Account ID, Date, Method, Address/ID, Status |
| `/dashboard/kyc` | **KYC** | Locked until funded account exists ("You must first have a funded account before applying for KYC"). Video tutorial embed, document upload, status tracking (pending/approved/rejected + remarks) |
| `/dashboard/affiliation` | **Affiliation Program** | Points wallet + rewards engine (details in §4) |
| `/dashboard/leaderboard` | **Leaderboard** | Filter tabs by account size (3K–200K). Stat cards: Highest Total Rewards, Longest Master Acc Duration, Highest Single Reward, Highest Total Rewards Count. Rankings table: Rank, Trader, Profit, Profit %, Country |
| `/dashboard/heatmap` | **Heatmap** | TradingView crypto/market heatmap widget embed |
| `/dashboard/calendar` | **Calendar** | TradingView Economic Calendar widget embed |
| `/dashboard/downloads` | **Downloads** | MT5/MT4 toggle → installers for Windows, Mac, Android, iOS |
| `/dashboard/notifications` | **Notifications** | In-app notification centre |
| `/dashboard/profile` | **Profile** | Account settings, password, 2FA |
| `/dashboard/guideline` | **Guideline** | Rules page with 2-STEP / 1-STEP / INSTANT tabs + Prohibited / Allowed / Trading Conditions cards |

**Header elements:** "New Order" button (primary CTA), notifications bell, profile icon, support/chat icon. Live chat widget (Tawk.to / Crisp) across dashboard and site.

### Module C — Admin Dashboard (Back Office)

- **User Management** — full CRUD, statuses, activity logs
- **Challenge Plan Builder** — create/edit account sizes, prices, challenge types, profit targets, daily & max drawdown, leverage, min trading days, phase structure. This same config powers website pricing AND dashboard rules
- **Account Assignment** — view paid orders, assign MT5/MT4 credentials from brokerage, auto-notify trader
- **Phase & Breach Management** — update stage (Phase 1 → Phase 2 → Funded → Breached) with reason codes; certificates auto-generate on pass
- **KYC Queue** — review documents, approve/reject with remarks
- **Withdrawal Approvals** — eligibility surfaced automatically (KYC, phase, trading days); approve, mark paid, record transaction reference
- **Rewards & Affiliate Manager** — approve video-review / social-media submissions, credit points, run weekly giveaway, manage point transfers
- **Coupons** — discount codes with usage limits, expiry, percentage/fixed value
- **Content Manager (CMS)** — website pricing, FAQs, rules content, testimonials, blog, notification templates
- **Reports & Analytics** — revenue by period, orders by challenge type, pass/fail rates, payout obligations, affiliate performance, exportable
- **Staff Roles** — Admin / Support / Finance with separate permissions + audit trail

### Module D — MT5 Data Integration

**Option 1 — Assisted (LAUNCH WITH THIS):**
Admin assigns MT5 credentials and updates account milestones (phase pass, breach, funded) from the back-office. Trader metrics shown from these records. Works with any brokerage arrangement. No external dependency.

**Option 2 — Automated (LATER UPGRADE, quoted separately):**
Depends on what access the client's broker provides. Three possible routes:
- **Broker's MT5 Manager API** — full automation, but requires the client to own an MT5 white-label/server. A retail broker like IC Markets will *not* provide this.
- **Third-party REST wrapper** (Brokeret, Finxsol) — also requires own MT5 server
- **MetaApi.cloud via investor password** (most realistic) — each MT5 account has a read-only investor password; feed it to MetaApi to pull live equity, balance, positions, trade history. Account creation stays manual; monitoring, breach detection and equity curves become fully automated. This is what most small prop firms do.

**Architecture when automated:**
```
Trader trades on MT5
      ↓
Sync worker (Laravel queued job / scheduler) polls API every 15–30s
      ↓
Latest equity/balance/positions → Redis
      ↓
Rule Engine evaluates: daily drawdown? max drawdown? profit target? trading days?
      ↓
Event fired → DB updated (phase pass / breach / funded)
      ↓
Notifications (email + dashboard) + admin alert
      ↓
Dashboard reads from MySQL + Redis for live metrics and equity curve
```

Components: `AccountSyncJob` (queued, Horizon-managed), `RuleEngine` service (rules loaded from the Challenge Plan Builder config), daily-drawdown reset scheduler (snapshot day-start balance at 5 PM EST — **this is where most prop firm disputes happen, get it right**), `equity_snapshots` table (one row per sync, powers the chart), breach handler (set status, email trader, disable trading if Manager API available).

**Recurring cost note:** MetaApi charges roughly $1–3 per account per month; Manager API wrappers have licensing fees. Client must be told about this upfront.

---

## 4. Rewards / Affiliation Engine (from reference platform)

Conversion: **100 points = $1.00**

| Program | Type | Value | Rules |
|---|---|---|---|
| **Cashback Points** | EARN | 100 per $1 | Choose cashback at checkout. Earn 10 points per $1 spent. Spend $100 → 1000 points ($10). Usable on future purchases |
| **Share Points with Friends** | SHARE | Any amount | Send points to fellow traders. Both accounts must be active and must have participated in at least one point-earning activity |
| **Video Review Points** | EARN | 500 points ($5), one-time | Submit 30-second video review, face visible, explain experience. Portrait mode, max 20MB MP4. Points credited after admin approval. One per user |
| **Social Media Points** | EARN | 300 points ($3), one-time | Record 30-second video, post on Instagram/TikTok/Facebook, share post link. Credited after approval. One per user |
| **Weekly Giveaway** | REWARD | $3,000–$10,000 accounts, 7 winners/week | Trustpilot reviews entered into a weekly lucky draw. 7 random winners get free accounts |

Plus: **Affiliation Task Submission** — "Submit Task" form for completed tasks, reviewed by admin.
Plus: referral links with click/conversion/commission tracking and withdrawal.

---

## 5. Tech Stack

| Layer | Choice |
|---|---|
| Framework | **Laravel 11**, PHP 8.3 (client explicitly requested Laravel) |
| Frontend | Blade + Livewire/Vue, Tailwind CSS, dark theme design system, GSAP |
| Database | **MySQL 8** — required for ACID transactions (payments, withdrawals, phase changes must be atomic). NoSQL is not appropriate here |
| Cache/Queue | Redis (a complementary layer, *not* the database) |
| Admin scaffolding | Filament (fast CRUD) |
| Auth | Breeze/Fortify + 2FA |
| Roles | Spatie laravel-permission |
| Payments | **Crypto-first** — reference platform accepts ONLY crypto: USDT (BSC — marked "LOW FEE", TRC20, ERC20, SOL), BTC, USDC (ETH). Gateway options: NOWPayments / CoinPayments / Cryptomus, webhook-driven order confirmation. Card gateway optional/later |
| Market widgets | TradingView (heatmap + economic calendar) |
| Email | SMTP/SES, branded templates (order, credentials, phase pass, breach, payout) |
| File storage | **S3-compatible object storage** (DO Spaces / Cloudflare R2 / S3) for KYC docs + certificates — NOT local disk. Keeps files decoupled from the server and makes migration trivial |
| Backups | Spatie laravel-backup, daily off-site dumps |
| Security | 2FA, encrypted credential storage, rate limiting, role permissions, activity logs, HTTPS |

---

## 6. Server Environment (ALREADY SET UP)

**Host:** DigitalOcean Droplet — Ubuntu 24.04, currently 1 vCPU / 1 GB RAM ($6/mo), NYC region.

> ⚠️ **Known risk:** 1 GB is tight for MySQL + Redis + PHP-FPM + queue worker running together. Resize to **2 GB ($12/mo)** before going to production, and 4 GB when MT5 automation is added.

**Installed and working:**
- Nginx (config at `/etc/nginx/sites-available/propfirm`, symlinked to `sites-enabled`, default site removed)
- PHP 8.3 FPM + extensions: mysql, mbstring, xml, curl, zip, bcmath, gd, redis, intl
- MySQL 8 — database `propfirm`, user `propfirm`@`localhost`
- Redis
- Composer
- Supervisor
- Laravel 11 installed at `/var/www/propfirm-platform`, migrations run successfully, site loads over HTTP on the droplet IP
- Ownership `www-data:www-data`, `775` on `storage` and `bootstrap/cache`
- UFW firewall enabled (OpenSSH + Nginx Full)

**Still to do:**
- [ ] Supervisor queue worker config (`/etc/supervisor/conf.d/propfirm-worker.conf`)
- [ ] Cron for Laravel scheduler (`* * * * * cd /var/www/propfirm-platform && php artisan schedule:run >> /dev/null 2>&1`)
- [ ] SSH key auth from local machine (currently using DigitalOcean browser console)
- [ ] GitHub repo + deploy key, `git init` + remote in the project folder
- [ ] Domain + SSL via certbot (`certbot --nginx -d domain.com`)
- [ ] UptimeRobot monitoring
- [ ] Business email — buy separately (Hostinger Business Email or Google Workspace). Do NOT run a mail server on the droplet
- [ ] Create a non-root deploy user (currently running Composer as root)

**Deploy flow (once GitHub is connected):**
```bash
cd /var/www/propfirm-platform
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache
supervisorctl restart propfirm-worker:*
```
Wrap this in a `deploy.sh` script.

**Portability principles (keeps migration between hosts easy):**
1. All code in Git — never edit files directly on the server
2. Never store uploads on local disk — use S3-compatible storage
3. Automated off-site DB backups
4. Keep a safe copy of `.env` (it is never committed to Git)

---

## 7. Database Schema

Foundation of everything. Tables (implemented in `database/migrations`):

- `users` — traders (extended with profile, referral, KYC status, points cache)
- `challenge_plans` — account size, price, challenge type, phase config, profit targets, drawdown limits, leverage, min trading days
- `orders` — purchase records, payment status, gateway reference, coupon applied, points redeemed
- `trading_accounts` — MT5/MT4 credentials (encrypted), linked plan, current phase, status (active/passed/breached/funded), assigned date
- `equity_snapshots` — balance, equity, drawdown, timestamped
- `kyc_documents` — file refs, status, remarks, reviewed_by
- `withdrawals` — account, amount, method, wallet address, status, transaction ref
- `certificates` — payout/achievement certificates
- `reward_points` — ledger (earned, spent, transferred)
- `reward_submissions` — video review / social media tasks, status, approved_by
- `giveaway_entries`
- `affiliates` / `referrals` — link, clicks, conversions, commissions
- `coupons` / `coupon_usages`
- `settings`, `faqs`, `testimonials`
- `notifications`
- Standard Laravel: `cache`, `jobs`, `sessions`, `password_reset_tokens`

---

## 8. Build Order

1. **Database schema / ERD** ← done
2. Auth (register, login, email verification, 2FA)
3. Admin: Challenge Plan Builder (everything else depends on this config)
4. Client: Buy Challenge flow + crypto checkout (**test gateway in sandbox before building the real checkout — this is where most bugs live**)
5. Client: Orders + Account Overview
6. Admin: Account assignment + phase/breach management
7. Client: Withdrawals + KYC
8. Rewards / points / affiliation engine
9. Leaderboard, heatmap, calendar, downloads, notifications
10. Marketing website + CMS
11. MT5 automation (Module D, Option 2) — only after broker access is confirmed

---

## 9. Open Questions for the Client

1. **What is the brokerage arrangement?** Own MT5 white-label/server, or accounts from a retail broker? → This decides whether Module D Option 2 is even possible
2. Which crypto gateway do they want? Any card gateway needed?
3. KYC: manual admin review, or third-party (Sumsub / Veriff)?
4. Exact challenge structures, fees, profit targets, and profit-split percentages
5. Affiliate + loyalty program at launch, or phase 2?
6. Multi-language? (Reference platform's audience spans UAE, Ghana, Kenya, China, Spain, Croatia)
7. Brand assets — existing logo/colors, or do we design the identity?
8. Legal content — will they supply Terms / Risk Disclosure / Refund Policy, or do we draft?
