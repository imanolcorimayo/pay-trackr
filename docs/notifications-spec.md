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
