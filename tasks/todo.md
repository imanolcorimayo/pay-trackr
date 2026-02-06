# PayTrackr - Task Tracking

<!-- Format: - [ ] Task description | - [x] Completed task -->

## Weekly Summary Notification Feature

- [x] Create `server/handlers/GeminiHandler.js` — extracted Gemini API logic
- [x] Refactor `server/webhooks/wp_webhook.js` — uses GeminiHandler, removed inline getAIAnalysis
- [x] Create `server/scripts/send-weekly-summary.js` — CRON script with per-user stats + AI insight
- [x] Clean up `server/package.json` — removed test scripts, added weekly-summary script
- [x] Create `web/pages/weekly-summary.vue` — notification landing page with 3 cards
- [x] Create `.github/workflows/send-weekly-summary.yml` — Monday 9AM ART schedule
