# Mangos · Notifications spec

Single source of truth for what notifications fire, when, and where they take you. Update this when adding a new notification kind.

## Channels

- **Web Push** (primary, only). VAPID keypair in `server/config.php['web_push']`. Public mirror in `app/includes/config.php['vapid_public_key']`.
- Service worker handlers: `app/service-worker.js` (`push` + `notificationclick` events).
- Per-device subscription state lives in the `push_subscription` table (one row per browser/installation).

## User-facing controls

`/notificaciones` page exposes:
- `master_off` — global kill switch
- `daily_enabled` — daily fijo reminders
- `weekly_enabled` — Sunday digest
- `anomaly_alerts_enabled` — mid-day anomaly pushes
- Per-device subscribe / unsubscribe
- "Enviar push de prueba" button

## Schedule (droplet cron)

`/etc/cron.d/mangos`:

| Kind            | When               | Endpoint                          |
| --------------- | ------------------ | --------------------------------- |
| daily-fijos     | 8:00 ART daily     | `POST /api/notifications/daily`   |
| weekly-digest   | 8:00 ART Sunday    | `POST /api/notifications/weekly`  |
| anomaly-check   | 14:00 ART daily *(later)* | `POST /api/notifications/anomalies` |

Cron-only routes require the `X-Cron-Secret` header (`server/config.php['cron_secret']`). Droplet cron file reads it from `/etc/mangos/cron.secret` (mode 0600, owner www-data).

## Catalog

Each row = one notification kind. Bundling, copy and routing get refined as we ship them.

| kind            | trigger                                    | title                       | body                            | route                                 | dedup key                          | dedup window |
| --------------- | ------------------------------------------ | --------------------------- | ------------------------------- | ------------------------------------- | ---------------------------------- | ------------ |
| `test`          | manual (button on `/notificaciones`)       | "Hola desde Mangos"         | "Las notificaciones funcionan ✅" | `/`                                   | none                               | none         |
| `fijo-due-soon` | recurrent due in next 3 days, not paid yet | "<title> vence en N días"   | "<amount> · <card or account>"  | `/movimientos?open=<recurrent_id>`    | `(user, fijo-due-soon, rec_id, ym)`| 24h          |
| `fijo-overdue`  | recurrent due in past month, no transaction| "N fijos vencidos · $T"     | "Tocá para revisar"             | `/fijos?filter=overdue`               | `(user, fijo-overdue, ym)`         | 24h          |
| `weekly-digest` | weekly cron, Sunday morning                | "Resumen <date_range>"      | <AI narrative>                  | `/analisis`                           | `(user, weekly-digest, iso_week)`  | 7d           |
| `anomaly-spend` | category spend ≥1.5× 4-week avg            | "<categoría> +<pct>%"       | "Esta semana: <amount>"         | `/analisis?category=<id>`             | `(user, anomaly-spend, cat_id, w)` | 7d           |
| `fijo-amount-anomaly` | recurrent-typed tx whose paid amount diverges from its template by ≥2× or ≤0.5× (and is non-zero on either side) | "<title> · monto inusual" | "Pagaste <actual> · esperado <template>" | `/movimientos?open=<tx_id>` | `(user, fijo-amount-anomaly, tx_id)` | once / tx |
| `account-low`   | account balance below user-set threshold   | "<account>: $<balance>"     | "Saldo bajo en <account>"       | `/cuentas?id=<account_id>`            | `(user, account-low, acct_id, d)`  | 24h          |

## Implementation notes

- All sends route through `notif_send($pdo, $user_id, $subs, $payload)` in `server/api/notifications.php`. Payload shape: `{title, body, url}`.
- Dedup is enforced via `notification_log` (insert before send; a uniqueness check on the `(user_id, kind, ref_id)` rows within the dedup window short-circuits redundant sends).
- Failed sends with `isSubscriptionExpired()` auto-delete the dead row from `push_subscription`.
- Bundling rule (when applicable, e.g. multiple fijos due on the same day): collapse into one push when ≥2 items, individual push when 1. Body lists titles + total.

## Status

- [x] Foundation: VAPID, tables, SW handlers, `/notificaciones` page, subscribe/unsubscribe/test endpoints.
- [x] PR2: daily fijo reminders endpoint (`POST /api/notifications/daily`) + droplet cron template at `deploy/cron.d/mangos`.
- [ ] PR3: weekly AI digest.
- [ ] Later: anomaly alerts + low-balance alerts.

## Notes — `fijo-amount-anomaly`

Caught in the wild on 2026-05-02: an AI-ingested credit card statement was fuzzy-matched to the "Apple pay - Youtube premium" recurrent and committed at $899.807; template was $9.460 — ~$890K of misclassified spend hid under `Fijos` for ~3 weeks before a /fijos vs /movimientos sanity check exposed it.

Implementation hints when this lands:
- Run on the existing `anomaly-check` 14:00 ART cron — no new cron slot needed.
- Scan `transaction` rows where `transaction_type='recurrent'` AND `created_ts` (or `updated_ts`) within the last 24–48h, joined to `recurrent` by `recurrent_id`. Skip rows where the recurrent has been deleted (orphans) — a separate kind could cover those if useful.
- Threshold: `ABS(tx.amount) ≥ 2 × ABS(rec.amount)` OR (`tx.amount = 0` AND `rec.amount ≠ 0`) OR (`ABS(tx.amount) ≤ 0.5 × ABS(rec.amount)` AND `rec.amount ≠ 0`). Skip when both are zero. Currency mismatch should also fire (rare but informative).
- Dedup is per-tx, no time window — once the user has seen the alert, never re-fire even if the row is edited later.
- Bonus prevention (orthogonal, in `api/ai.php`): the AI's `recurrent_match_id` step should refuse a fuzzy-match when the candidate's amount is >50× the recurrent's template — bias should be against silent matching, not for it.
