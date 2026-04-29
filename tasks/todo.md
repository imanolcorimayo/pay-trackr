# Plan: rename `payment` → `transaction` + flip signs

**Phase 1 of the income/wallets/multi-currency expansion. Isolated, no behavior change.**

After this lands: every expense is stored as a **negative amount** in a `transaction` table. Sign + category disambiguates expense vs income later. Account/currency work happens in Phase 2.

---

## Schema renames (single migration)

- `payment` → `transaction`
- `payment_recipient` → `transaction_recipient` (FK col `payment_id` → `transaction_id`)
- `payment_template` → `transaction_template`
- All FK references rebound.

## Sign flip (same migration)

- `UPDATE transaction SET amount = -amount`
- `UPDATE recurrent SET amount = -amount`
- `UPDATE transaction_template SET amount = -amount`

(All current rows are expenses → all become negative.)

## Code conventions after rename

- API stores raw signed value. Display layer shows `abs(amount)` until income lands so UI stays the same.
- Validation: "expense" forms auto-negate the input on save.
- `SUM(amount)` becomes "net" semantics from this point on.

---

## File changes

### New
- `server/migrations/20260428_016_rename_payment_to_transaction.sql`

### Renamed (file rename + content updates)
- `server/api/payments.php` → `server/api/transactions.php`
- `app/pages/payments.php` → `app/pages/movimientos.php`

### Modified — server
- `server/api/index.php` — route `/transactions` (drop `/payments`); rename `/ai/parse-payments` → `/ai/parse-transactions` and `/ai/commit-payments` → `/ai/commit-transactions` for consistency.
- `server/api/recurrents.php` — table references
- `server/api/templates.php` — table references
- `server/api/ai.php` — table references; commit logic stores negative amounts
- `server/handlers/SpacesHandler.php` — only if it references `payment` directly

### Modified — app
- `app/router.php` — `/pagos` → `/movimientos`
- `app/includes/header.php` — sidebar label "Pagos" → "Movimientos" + route
- `app/includes/footer.php` — any payment refs
- `app/pages/dashboard.php` — route + label refs, `abs()` on display
- `app/pages/fixed.php` — route + label refs
- `app/pages/analytics.php` — route + label refs, `abs()` on sums
- `app/pages/capture.php` — route + label refs, AI capture stores negative
- `app/assets/js/api.js` — endpoint URLs
- `app/assets/js/app.js` — endpoint URLs + display helpers

### UI strings (Spanish)
- "Pago" / "Pagos" / "Nuevo pago" → "Movimiento" / "Movimientos" / "Nuevo movimiento"
- Sidebar nav, page titles, buttons, toasts.

---

## Out of scope (deferred to next phases)

- `account_id` on transaction (Phase 2)
- `currency` per transaction (Phase 2)
- `expense_category` rename to `category` (Phase 4, when income categories land)
- Income categories table (Phase 4)
- FX rates table (Phase 3)
- Recurrent incomes / AI for incomes / Analytics rework (filed as future GH issues)

---

## Implementation order

- [ ] Write migration `016_rename_payment_to_transaction.sql` (renames + FK rebinds + sign flips, all in one transaction)
- [ ] Run migration on local DB; verify table renames + sign flips with `SELECT SUM(amount) FROM transaction` (should be negative of old `SUM(payment.amount)`)
- [ ] Rename `server/api/payments.php` → `transactions.php`; update internal table refs
- [ ] Update `server/api/index.php` routes + AI endpoint renames
- [ ] Update `server/api/recurrents.php`, `templates.php`, `ai.php` table/column refs
- [ ] Confirm AI capture commits negative amounts
- [ ] Rename `app/pages/payments.php` → `movimientos.php`
- [ ] Update `app/router.php` `/pagos` → `/movimientos`
- [ ] Sweep all `app/` files for "payment"/"Pago"/"/pagos"/"/api/payments" → new names; ensure displays use `abs()`
- [ ] Update `app/assets/js/api.js` + `app.js` endpoint URLs
- [ ] Manual smoke test: list, create, edit, delete, mark recurrent paid, AI capture, dashboard totals, analytics charts
- [ ] Final verify: `git grep -iE "\bpayment(s)?\b|/pagos|/api/payments" -- app/ server/api server/handlers server/middleware server/migrations` returns no hits in active code (excluding migration history strings)
- [ ] File future GH issues: recurrent incomes, AI for incomes, analytics rework
- [ ] Commit (single commit, message: `refactor: rename payment → transaction (signed amounts)`)
- [ ] Update `tasks/lessons.md` if anything surprising came up

---

## Verification before "done"

- All existing flows behave identically (create, list, edit, delete, AI capture, recurrent mark-paid).
- Dashboard sums match pre-migration (in absolute terms).
- No "payment" references left in active code.
- Single atomic migration; rollback path documented in migration comments.
