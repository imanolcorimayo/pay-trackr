# PayTrackr - Lessons Learned

<!-- Patterns and mistakes to avoid. Updated after every correction. -->

## Mobile audit must include the header pattern

When auditing a page for mobile-improvements (vs `movimientos.php`), the header pattern is a **first-class criterion**, not optional:

- Desktop header: `hidden lg:flex` (because the mobile topbar already shows `$pageTitle`)
- Mobile-only action row: `lg:hidden` with the page's primary action(s)

**Why:** I rated `analytics.php` as "Good" once because its body grids were responsive, missing that the header still duplicated the page title on mobile. The user caught it. Body responsiveness ≠ header responsiveness.

**How to apply:** any page whose header is `flex items-center justify-between` (always visible) is NOT mobile-improved, regardless of how good the rest of the page is. Always check for `hidden lg:flex` + a sibling `lg:hidden` row before declaring a page "Good."
