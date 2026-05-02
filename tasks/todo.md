# Bulk AI batch traceability

Goal: when a transaction was created from a bulk AI upload, persist the batch (images on Spaces + metadata in DB) so the movement view can show the source images, with the AI-matched one highlighted.

## Schema (migration 026)

- `ai_batch (id, user_id, source, transcription, created_ts)`
- `ai_batch_file (id, batch_id, idx, spaces_path, mime, created_ts)`
- `transaction.ai_batch_id`, `transaction.ai_batch_match_idx`

## Spaces layout

`<prefix>/ai-uploads/<user_id>/batches/<batch_id>/<idx>.<ext>`

## Compression

GD: re-encode JPEG q80, max-dim 1600px. HEIC/HEIF/audio pass-through (GD can't decode). Failure to compress falls back to original bytes.

## Steps

- [x] 1. Migration 026 — tables + transaction FK columns (applied to local DB)
- [x] 2. `parse-transactions`: compress + upload images, create batch rows, return `batch_id`
- [x] 3. `commit-transactions`: accept `batch_id` + per-row `screenshot_idx`, persist FK on each row (covers `create` + `mark_recurrent_paid` paths)
- [x] 4. `discard-batch` action — refuses if batch is already in use; deletes Spaces objects + DB rows otherwise
- [x] 5. Read path: `GET /api/ai/batch?id=…` returns `{id, source, transcription, created_ts, files: [{idx, mime, path}]}`. Files reuse `/ai/preview-artifact` for streaming.
- [x] 6. Frontend `capture.php` — `currentBatchId` threaded through commit; `screenshot_idx` per row; `discard()` calls `/ai/discard-batch` fire-and-forget
- [x] 7. Frontend `movimientos.php` — `renderBatchViewer` + `hideBatchViewer`; new `<details>` panels in both expense and income modals; clip icon on rows now also fires for batch rows; matched thumb gets `ring-accent` + check badge and is moved to the front of the grid

## Review

- Compression: GD-only, max-dim 1600px, JPEG q80, falls back to original bytes for HEIC/HEIF and audio.
- Spaces layout: `<prefix>/ai-uploads/<user>/batches/<batch_id>/<idx>.<ext>`. Batch deletion sweeps via DB row list.
- Auth: `/ai/batch` and `/ai/discard-batch` validate `user_id` ownership on the batch row before exposing files / deleting.
- Back-compat: if Spaces is unconfigured (dev), `parse-transactions` returns `batch_id: null` and the rest of the flow still works as before.
- **Untested in browser** — server PHP syntax checked, migration applied, but a real bulk upload + commit + view round-trip on a live droplet is needed to confirm Spaces creds + GD compression land correctly.

## Decisions

- One batch per upload session (image OR audio). Audio batches store one file at idx=0 + transcription.
- Per-row matched idx is the AI's `screenshot_idx`, stored at commit time.
- Existing `preview-artifact` endpoint already streams any path under the user's namespace — reuse it for batch image preview.
- Single-artifact flow (`parse-single` → `transaction.ai_artifact_path`) stays as-is. Both can coexist; in practice a row is either single or batch.
