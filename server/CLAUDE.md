# PayTrackr — Server

PHP REST API backed by MySQL. Migrated from the previous Node.js + Firestore stack.

## Active: `/api` (PHP REST API)

- **Stack**: PHP 8.x, PDO + MySQL, dotenv, cURL (for Gemini)
- **Entry**: `api/index.php` — single front controller. CORS, then `require_auth()`, then route by URL path.
- **Auth**: session-based via `middleware/auth.php`. `$user_id` is injected into every handler.
- **DB config**: `api/config.php` (loaded from env / config file).
- **Endpoints**:
  - `GET/POST/PUT/DELETE /api/transactions` → `api/transactions.php` (joins `transaction_recipient` for transfer details)
  - `… /api/recurrents` → `api/recurrents.php` (with `recurrent_alias` join)
  - `… /api/templates` → `api/templates.php`
  - `… /api/categories` → `api/categories.php`
  - `… /api/cards` → `api/card.php`
  - `… /api/accounts` → `api/accounts.php`
  - `POST /api/ai/parse-transactions` → `api/ai.php` (image → AI-extracted draft transactions)
  - `POST /api/ai/commit-transactions` → `api/ai.php` (persist drafts after user review)

**Sign convention**: `transaction.amount` and `recurrent.amount` are stored **signed** — expenses are negative. Write endpoints normalize input via `-abs()`. Read endpoints return signed values; the frontend `Math.abs()`es for display.

**Account & currency**: every `transaction` and `recurrent` carries `account_id` (FK → `account`, the wallet/source-of-money) and `currency` (`ENUM('ARS','USD','USDT')`, default `'ARS'`). `account_id` defaults to the user's `is_default` account (seeded as "Sin cuenta" on first login via `seed_default_account_for_user`); `currency` defaults to that account's currency. Pass `?account_id=` or `?currency=` to filter list endpoints. Dashboard/analytics aggregate ARS-only until Phase 3 brings FX rates.

**Account balance**: each `account` has `opening_balance` + `opening_balance_date`. `GET /api/accounts` returns a computed `current_balance = opening_balance + SUM(amount)` over **paid** transactions on/after that date (NULL date = include all). Pending rows do not move the balance. Logic lives in `compute_account_balance()` in `accounts.php`.

## AI: `handlers/GeminiHandler.php`

Reusable Gemini wrapper. Model rotation: `gemini-2.5-flash` → `gemini-3.1-flash-lite-preview` → `gemini-2.5-flash-lite` → `gemini-2.5-pro`. Per-request timeout 45s, total budget 110s. Daily-exhaustion cache at `sys_get_temp_dir()/mangos-gemini-exhausted.json`. Vision-capable; accepts `inlineData` parts.

`api/ai.php` (parse-transactions) builds context per request: current-month transactions, recurrents + their aliases, and category names — all passed into the prompt so the model can reconcile uploads against existing data instead of duplicating. Amounts in the prompt context are sent as positive magnitudes (`ABS()`) so the model compares like-for-like.

## Migrations

- `migrate.php` — runner. Applies SQL files from `migrations/` in order, tracks applied state in DB.
- `migrations/*.sql` — schema definitions (`transaction`, `transaction_recipient`, `recurrent`, `recurrent_alias`, `expense_category`, `transaction_template`, `card`, `account`, `user`, `fcm_token`, `weekly_summary`).

## Deprecated (reference only, not deployed)

- `webhooks/wp_webhook.js` — old WhatsApp Business chatbot (Node/Express + Firestore). Will be removed once the AI input flow on `/app` covers the same use cases.
- `handlers/GeminiHandler.js` — old JS Gemini wrapper. Replaced by the PHP version.
- `scripts/send-reminders.js`, `scripts/send-weekly-summary.js`, `scripts/test-notifications.js` — Node cron scripts for FCM push. Tied to the deprecated Nuxt PWA (`/web`). In transition.
- `scripts/migrate-firestore-to-mysql.js` — one-shot Firestore→MySQL data migration tool. Kept for re-runs during the cutover.

## Environment

```
DB credentials       # via api/config.php (DB_HOST, DB_NAME, DB_USER, DB_PASS)
GEMINI_API_KEY       # required for /api/ai/* endpoints
```

## Language & Locale

- Spanish (Argentine) for all user-facing strings.
- Currency: ARS, formatted with `es-AR` locale.
- Timezone: `America/Argentina/Buenos_Aires` (used in `api/ai.php` for "today" / month boundaries).
