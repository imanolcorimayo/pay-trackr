# PayTrackr - Task Tracking

<!-- Format: - [ ] Task description | - [x] Completed task -->

## Weekly Summary Notification Feature

- [x] Create `server/handlers/GeminiHandler.js` — extracted Gemini API logic
- [x] Refactor `server/webhooks/wp_webhook.js` — uses GeminiHandler, removed inline getAIAnalysis
- [x] Create `server/scripts/send-weekly-summary.js` — CRON script with per-user stats + AI insight
- [x] Clean up `server/package.json` — removed test scripts, added weekly-summary script
- [x] Create `web/pages/weekly-summary.vue` — notification landing page with 3 cards
- [x] Create `.github/workflows/send-weekly-summary.yml` — Monday 9AM ART schedule

## UX Improvement - Laws of UX Audit

### Batch 1: Dark Theme & Touch Fixes
- [x] C-1: Summary stat cards — dark theme fix
- [x] C-2: Summary chart borders — dark theme fix
- [x] C-3: Detail modals — fix light table headers/buttons
- [x] C-4: Form inputs — fix light borders, add dark bg
- [x] C-5: Modal close icon — 44px touch target
- [x] C-6: Focus trapping + Escape key in modals
- [x] C-7: Settings link in header dropdown
- [x] C-8: Filter sort labels

### Batch 2: Experience Improvements
- [x] I-1: Consolidate modal systems
- [x] I-2: ConfirmDialogue Spanish defaults
- [x] I-3: Header dropdown simplification
- [x] I-4: Inline form validation
- [x] I-5: Recurrent page empty state
- [x] I-6: Scrollbar visibility
- [x] I-7: Button style consolidation
- [x] I-8: ARIA labels (Spanish)
- [x] I-9: Weekly summary click handlers

### Batch 3: Polish
- [x] P-1: Landing page rem → Tailwind
- [x] P-2: Icon size standardization
- [x] P-3: Form label color consistency
- [x] P-4: Tooltip dark theme
- [x] P-5: Summary progress bar tracks
- [x] P-6: Current month highlight
- [x] P-7: Loading skeleton shimmer
- [x] P-8: Recurrent page mobile title
- [x] P-9: NotificationManager button hierarchy
- [x] P-10: formatPrice deduplication
