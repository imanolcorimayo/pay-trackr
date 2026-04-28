# Current task

_None — slate clear._

---

## Last shipped

**AI Payment Capture** (commits `3185c55` → `8161708`, ~Apr 27–28 2026)

Screenshots → Gemini → reviewable drafts → transactional commit. All 8 plan items landed plus extensions:

- `server/api/ai.php` — `parse-payments` + `commit-payments` endpoints, structured outputs, server-side dedup belt
- `app/pages/capture.php` + `/capturar` route + sidebar nav + AI buttons on `/pagos` and `/fijos`
- `load_env()` in `server/api/config.php`
- Migrations `012_add_ai_source`, `013_create_recurrent_alias`, `014_extend_ai_source`, `015_add_ai_artifact`
- Beyond plan: audio capture mode, camera-roll picker, AI artifact storage on DO Spaces (`SpacesHandler.php`), Gemini model rotation, recurrent aliases, PWA setup (manifest + service worker + offline page)
