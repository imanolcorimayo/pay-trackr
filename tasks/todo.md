# AI Payment Capture — Plan

## Goal

Let the user upload one or more screenshots (MercadoPago, Deel, Binance, bank apps) and have Gemini extract the transactions, propose payment records and recurrent matches, and commit them after the user reviews/edits the draft list.

Scope: **expenses only**. Incomes are out of scope. Refunds: ignore for now (user will reconcile manually until incomes exist).

---

## Architecture

### Backend (PHP)

**Two new endpoints under a new route module** `server/api/ai.php`, registered in `server/api/index.php`:

1. `POST /api/ai/parse-payments` — JSON with base64 images. Calls Gemini vision, returns draft list.
2. `POST /api/ai/commit-payments` — accepts the user-reviewed draft list, performs all writes inside a single MySQL transaction.

Why two endpoints: the user must review before committing. Parse is read-only AI work; commit is the mutating step that runs only after the user clicks "Confirmar".

### Env loading

`server/.env` already has `GEMINI_API_KEY`. The PHP API doesn't load env yet. Add a tiny loader in `server/api/config.php`:

```php
function load_env(string $path): void {
    if (!is_file($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        $v = trim($v, " \t\"'");
        if ($k !== '') $_ENV[$k] = $v;
    }
}
load_env(__DIR__ . '/../.env');
```

### Gemini model

Reuse `gemini-2.5-flash-lite` from `GeminiHandler.js` (cheap, vision-capable, already proven on Argentine number quirks). One multimodal request per submission: all screenshots + textual context (current-month payments, recurrents, categories, today's date) + instructions in a single round-trip.

### Structured outputs (mandatory)

Use Gemini's structured output feature — `responseMimeType: "application/json"` + `responseSchema` — instead of relying on the prompt to return clean JSON. Schema lives in `ai.php`; partial-parse signal is built in via `unreadable_screenshot_idxs`.

```php
$schema = [
  'type' => 'OBJECT',
  'properties' => [
    'drafts' => [
      'type' => 'ARRAY',
      'items' => [
        'type' => 'OBJECT',
        'properties' => [
          'screenshot_idx' => ['type' => 'INTEGER'],
          'title' => ['type' => 'STRING'],
          'amount' => ['type' => 'NUMBER'],
          'date' => ['type' => 'STRING'],
          'description' => ['type' => 'STRING', 'nullable' => true],
          'suggested_category_name' => ['type' => 'STRING', 'nullable' => true],
          'existing_payment_id' => ['type' => 'INTEGER', 'nullable' => true],
          'recurrent_match_id' => ['type' => 'INTEGER', 'nullable' => true],
          'recurrent_match_confidence' => ['type' => 'STRING', 'enum' => ['high','medium','low']],
          'duplicate_in_batch_idx' => ['type' => 'INTEGER', 'nullable' => true],
        ],
        'required' => ['screenshot_idx','title','amount','date','recurrent_match_confidence']
      ]
    ],
    'unreadable_screenshot_idxs' => [
      'type' => 'ARRAY',
      'items' => ['type' => 'INTEGER']
    ]
  ],
  'required' => ['drafts','unreadable_screenshot_idxs']
];
```

Eliminates the `replace(/```json/g, ...)` hack and JSON.parse-failure path.

---

## Endpoint 1: `POST /api/ai/parse-payments`

### Request

```json
{
  "images": [
    {"mimeType": "image/png", "data": "<base64>"}
  ]
}
```

Limits: max 10 images, max ~6MB total base64. Return 413 if exceeded.

### Server-side context preparation

For the authed user, fetch:
- **Current month's payments**: `id, title, amount, paid_ts, due_ts, payment_type, recurrent_id` — for dedup against already-recorded payments.
- **All active recurrents**: `id, title, amount, due_date_day, expense_category_id` — for fuzzy match.
- **Categories**: `id, name` — so Gemini returns categories that exist.
- **Today's date** (Buenos Aires).

Pack as compact JSON and embed in the prompt.

### Gemini response shape (strict JSON)

```json
{
  "drafts": [
    {
      "screenshot_idx": 0,
      "title": "Spotify Premium",
      "amount": 2099.0,
      "date": "2026-04-12",
      "description": "Suscripcion mensual",
      "suggested_category_name": "Subscripciones",
      "existing_payment_id": null,
      "recurrent_match_id": 17,
      "recurrent_match_confidence": "high",
      "duplicate_in_batch_idx": null
    }
  ]
}
```

Rules baked into the prompt:
- **Argentine number format** (reuse the warning block from `parseTransferImage`).
- **Expenses only** — skip credits/incomings.
- **Match against `existing_payments` and `recurrents`** by name similarity + amount proximity (±20% allowed for recurrents whose amount varies, e.g. utilities).
- **Confidence**: `high` / `medium` / `low`.
- **Cross-screenshot dedup** via `duplicate_in_batch_idx`.
- If category not in user's list, return `null` (frontend will offer "Otros").

### Response post-processing (PHP)

- Resolve `suggested_category_name` → `expense_category_id`.
- Validate `existing_payment_id` and `recurrent_match_id` belong to this user.
- **Belt-and-suspenders dedup**: server-side check matching `(title_normalized, amount, date ±2 days)` against current-month payments — overrides Gemini if it missed.
- Return drafts + `meta: { image_count, processing_ms }`.

If Gemini returns invalid JSON: 502 with logged raw text snippet.

---

## Endpoint 2: `POST /api/ai/commit-payments`

### Request

```json
{
  "rows": [
    {
      "action": "create",
      "title": "Spotify Premium",
      "amount": 2099.00,
      "expense_category_id": 5,
      "card_id": null,
      "due_ts": "2026-04-12 00:00:00",
      "paid_ts": "2026-04-12 00:00:00",
      "payment_type": "one_time",
      "is_paid": true,
      "description": null
    },
    {
      "action": "mark_recurrent_paid",
      "recurrent_id": 17,
      "amount": 2099.00,
      "paid_ts": "2026-04-12 00:00:00",
      "update_recurrent_amount": true
    },
    { "action": "skip" }
  ]
}
```

### Server logic (single transaction)

```
BEGIN;
foreach row:
  - skip → noop
  - create → INSERT INTO payment (...)
  - mark_recurrent_paid:
      • find or create the current-month payment instance for this recurrent_id
      • UPDATE payment SET is_paid=1, paid_ts=?, amount=?
      • if update_recurrent_amount → UPDATE recurrent SET amount=?
COMMIT;
```

Returns `{ created, updated, skipped, payment_ids: [...] }`.

All-or-nothing avoids leaving the user with half-applied AI batches.

---

## Frontend

### New page `/capturar`

Entry points:
- Sidebar nav entry "Capturar" (AI/sparkle icon), between Pagos and Categorias.
- Button on `/pagos` (top-right, next to "Nuevo pago") with AI icon.
- Button on `/fijos` (top-right, next to "Nuevo fijo") with AI icon.

All three navigate to `/capturar`.

### Sections

1. **Drop zone / file picker** — multiple images, paste-from-clipboard. Plain `<input type="file" accept="image/*" multiple>` — no `capture` attribute, so mobile shows the standard chooser (camera OR gallery). Thumbnail strip below.
2. **"Analizar" button** — disabled until ≥1 image. POSTs to `/api/ai/parse-payments`. Skeleton loader.
3. **Review table** — one row per draft with editable fields:
   - Title, Amount (Argentine format), Date, Category dropdown
   - Card dropdown hidden behind "Más opciones" toggle (most rows won't use it)
   - **Status badge**:
     - `Nueva` (green) — default action `create`
     - `Coincide con fijo: {name}` (blue) — default action `mark_recurrent_paid`, sub-checkbox "Actualizar monto del fijo a $X" auto-checked when amounts differ
     - `Ya existe ({date})` (gray) — default `skip`, link to existing payment
     - `Duplicado en captura` (gray) — default `skip`
   - Per-row "Saltar"/"Incluir" toggle to override default.
4. **Footer summary** — "X creados, Y fijos pagados, Z saltados — Total: $W". "Confirmar todo" button.

### Submit flow

- Build `rows[]`, POST to `/api/ai/commit-payments`.
- On success: toast + "Ver en Pagos" link → `/pagos?month=YYYY-MM`.
- On failure: keep review state intact (no edit loss).
- Drafts persisted to `localStorage` so a page refresh between parse and commit doesn't lose work.

### Partial parse warnings

If Gemini returns drafts only for some screenshots, surface a banner: "X captura(s) no se pudieron leer".

---

## Files

**New**:
- `server/api/ai.php`
- `app/pages/capture.php`

**Modify**:
- `server/api/config.php` — add `load_env()`
- `server/api/index.php` — route `/ai/parse-payments` + `/ai/commit-payments`
- `app/router.php` — `/capturar`
- `app/includes/header.php` — sidebar nav

---

## Decisions (locked)

1. **UX surface** — dedicated page `/capturar`.
2. **Mobile camera** — plain file input, no `capture` attribute (user picks camera or gallery on phone).
3. **Entry points** — sidebar "Capturar" + AI-icon buttons on `/pagos` and `/fijos`.
4. **`card_id` in review row** — hidden behind per-row "Más opciones" toggle.
5. **Cost guardrail** — none (free tier limit is enough).
6. **Draft persistence** — `localStorage` only.
7. **Partial-parse warning** — yes; driven by Gemini's `unreadable_screenshot_idxs` field.
8. **Structured outputs** — Gemini `responseSchema` + `responseMimeType`.

---

## Implementation order (once approved)

- [ ] Add `load_env()` to `config.php`, verify `GEMINI_API_KEY` reachable from PHP
- [ ] Build `ai.php` with stub `parse-payments` returning fixture data (frontend can dev in parallel)
- [ ] Wire Gemini call with full prompt + context bundle
- [ ] Post-processing (category resolution, server-side dedup belt)
- [ ] Build `commit-payments` with transactional logic
- [ ] Build `/capturar` page
- [ ] Sidebar nav + router entry
- [ ] End-to-end test with 3 real MercadoPago screenshots
- [ ] Capture lessons in `tasks/lessons.md`
