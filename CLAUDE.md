# PayTrackr

Personal payment tracking & financial management system. Migrated from Firestore to MySQL+PHP (Mangos rebrand). Monorepo:

## Active

- **`/app`** — PHP frontend. Router-based (`router.php`), pages in `pages/*.php`, Tailwind CSS. User-facing web app.
- **`/server/api`** — PHP REST API (PDO + MySQL). Entry: `server/api/index.php`. Endpoints: `payments`, `recurrents`, `templates`, `categories`, `cards`, `ai/parse-payments`, `ai/commit-payments`.
- **`/server/handlers/GeminiHandler.php`** — Gemini wrapper used by `ai.php` (model rotation, exhaustion cache, 503 retry).
- **`/server/middleware/auth.php`** — session auth (provides `$user_id` to API handlers).
- **`/server/migrations/`** — MySQL schema migrations (run via `server/migrate.php`).

## Deprecated (do not modify, reference only)

- **`/web`** — old Nuxt 3 PWA frontend. Replaced by `/app`.
- **`/server/webhooks/wp_webhook.js`** — old WhatsApp Business chatbot (Express + Firestore). Will be removed.
- **`/server/handlers/GeminiHandler.js`** — old JS handler. Replaced by `GeminiHandler.php`.

## Workflow Orchestration

### 1. Plan Mode Default
- Enter plan mode for ANY non-trivial task (3+ steps or architectural decisions)
- If something goes sideways, STOP and re-plan immediately - don't keep pushing
- Use plan mode for verification steps, not just building
- Write detailed specs upfront to reduce ambiguity

### 2. Subagent Strategy
- Keep the main context window clean - offload research, exploration, and parallel analysis to subagents
- For complex problems, throw more compute at it via subagents
- One task per subagent for focused execution

### 3. Self-Improvement Loop
- After ANY correction from the user: update `tasks/lessons.md` with the pattern
- Write rules for yourself that prevent the same mistake
- Ruthlessly iterate on these lessons until mistake rate drops
- Review lessons at session start

### 4. Verification Before Done
- Never mark a task complete without proving it works
- Diff behavior between main and your changes when relevant
- Ask yourself: "Would a staff engineer approve this?"
- Run tests, check logs, demonstrate correctness

### 5. Demand Elegance (Balanced)
- For non-trivial changes: pause and ask "is there a more elegant way?"
- If a fix feels hacky: "Knowing everything I know now, implement the elegant solution"
- Skip this for simple, obvious fixes - don't over-engineer
- Challenge your own work before presenting it

### 6. Autonomous Bug Fixing
- When given a bug report: just fix it. Don't ask for hand-holding
- Point at logs, errors, failing tests -> then resolve them
- Zero context switching required from the user
- Go fix failing CI tests without being told how

## Task Management

1. **Plan First**: Write plan to `tasks/todo.md` with checkable items
2. **Verify Plan**: Check in before starting implementation
3. **Track Progress**: Mark items complete as you go
4. **Explain Changes**: High-level summary at each step
5. **Document Results**: Add review to `tasks/todo.md`
6. **Capture Lessons**: Update `tasks/lessons.md` after corrections

## Core Principles

- **Simplicity First**: Make every change as simple as possible. Impact minimal code.
- **No Laziness**: Find root causes. No temporary fixes. Senior developer standards.
- **Minimal Impact**: Changes should only touch what's necessary. Avoid introducing bugs.
