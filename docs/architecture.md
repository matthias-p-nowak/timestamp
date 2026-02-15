# Architecture

## Application landscape
- Single-page PHP entrypoint (`timestamp.php`) now exists as a real mobile-focused scaffold and reuses the established `examples/mobile.html` visual/interaction patterns.
- Front-end presentation and behavior assets are now split into dedicated static files (`timestamp.css`, `timestamp.js`) loaded by `timestamp.php` instead of inline blocks.
- Cache busting is intentionally disabled for the primary front-end assets: `timestamp.css` and `timestamp.js` are referenced without version query strings or hashed filenames.
- Client-side script organization in `timestamp.js` now centralizes selectors, UI text, and behavior constants in one top-level `UI_CONFIG` object to simplify future maintenance.
- Authentication occurs at the web server layer via `.htaccess` protections; no additional user accounts exist beyond the single authorized user.
- `timestamp.php` now initializes and reads SQLite data from `timestamp.db`, including first-run schema creation.
- Storage engine remains SQLite, and database access is now implemented via PDO (`sqlite:` DSN, exception mode, associative fetch mode).
- Core backend helpers and request handlers in `timestamp.php` now maintain explicit PHPDoc comments to keep function intent and validation behavior auditable in-code.
- HTML output escaping in `timestamp.php` is now centralized through a tiny `e(string): string` helper that wraps `htmlspecialchars` with `ENT_QUOTES` and UTF-8.
- Header check-in/check-out control now performs real POST toggle actions: check-in inserts an open row and check-out closes the latest open row, followed by full-page reload.
- Day editor modal now submits persisted edits: `save-day` POST replaces all entries for the selected date in `time_entries` with submitted pairs.

## Data model & flow (derived from design)
- Data is stored in `time_entries` (`check_in_at`, `check_out_at`) and loaded from SQLite for rendering.
- On empty databases, no sample rows are auto-created; only real user-created entries are rendered.
- Time output is formatted as `HH:mm` in Europe/Oslo; writes currently occur only for schema setup and initial seed data.
- The front-end renders week cards and lists only days that have time events.
- The sample UI demonstrates split shifts and supports an unlimited number of check-in/check-out pairs while still presenting a single day-level total/break summary.
- For each pair, break is applied only when the full `11:30` to `12:00` interval is contained inside that pair; partial overlap does not count as break.
- Editing accepts compact time input without `:` (for example `745` -> `07:45`, `1712` -> `17:12`) and normalizes values before persistence; deleting both values in a pair removes that row.
- Modal editing persists via `save-day` POST actions that replace selected-day entries in SQLite; full-page reload remains the update model.
- Header state is now data-driven from open entry state: when checked in, `header h1` shows check-in time and action button shows `Check out`; when not checked in, `header h1` shows `Timestamp` and action button shows current time.
- Save-day handling treats rows where both fields are empty as deletions (skipped), and replacing a day with only empty rows removes that day from storage.
- Client-side modal validation now runs before submit: invalid rows are highlighted, first validation error is shown inline, and save is blocked until all rows are valid.
- Modal editor now shows a conditional informational hint when any valid row overlaps `11:30`–`12:00`, while clarifying that break still counts only if the full window is covered.
- Day-row click/tap is the canonical edit entrypoint for modal editing; no separate edit button is required.
- Day-editor pair inputs use native `type=time` controls on coarse-pointer/mobile devices (`step=60`) to surface numeric time keyboards; desktop keeps text inputs for compact entries.
- Backend safety handling enforces a single open check-in row: if multiple open rows are detected, the newest remains open and older ones are auto-closed.
- A lightweight regression script exists at `tests/regression.sh` and verifies toggle behavior, save-day persistence/deletion, compact time parsing, break-rule semantics, overlap-hint source logic, and missing-weekday add-flow wiring.
- Each displayed week now also carries a bottom list of missing weekdays (no entries), and those items use the same modal add/edit entrypoint as existing day rows.
- A bottom calendar/date button opens a date picker constrained to the last 8 weeks; selected dates enter the same modal add/edit flow used by day rows.
- Save-day backend handling now enforces the same edit window and rejects out-of-range dates (for example future dates), matching the picker constraint.

## Front-end intent
- Mobile presentation is tuned for 1080×2340 screens with DPR 3: a sticky header for the “check-in/check-out” button and stacked week cards are delivered via simple HTML/CSS (see `examples/mobile.html`).
- Sample data page (`examples/mobile.html`) mirrors the production layout so designers/testers can see the Monday–Sunday rows, week separators, and total/break labeling that the live app needs to deliver.
- Sample data page (`examples/mobile.html`) now also includes a tiny grey footer version-stamp preview so the bottom-of-page treatment can be validated visually.
- Approved example states are frozen as dated files under `examples/snapshots/` so UI decisions can be referenced without diffing commit history.
- Active color-preference baseline is snapshot `examples/snapshots/mobile-2026-02-14-v2.html`.
- The sample page also includes a modal day-editor prototype triggered per row to preview editing UI; the modal is front-end only and does not persist changes.
- Header state reflects check-in status: when checked in, `header h1` shows the check-in time and the action button says `Check out`; when not checked in, `header h1` shows `Timestamp` and the action button shows current time.
- Footer includes a tiny grey version stamp rendered as `yyyy-mm-dd-HH-MM`, sourced from `timestamp.php` file last-modified time.
- Browser branding includes a custom `favicon.ico` reflecting work timekeeping (clock + briefcase motif) and is referenced from `timestamp.php`.
- Additional app icon assets are provided for broader device support: `favicon-32x32.png` and `apple-touch-icon.png`.
- Web app metadata is provided via `manifest.json` with Android icon assets (`android-chrome-192x192.png`, `android-chrome-512x512.png`) plus dedicated opaque maskable assets (`android-chrome-192x192-maskable.png`, `android-chrome-512x512-maskable.png`) and `theme-color` wiring in `timestamp.php`.
