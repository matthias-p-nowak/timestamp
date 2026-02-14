# Architecture

## Application landscape
- Single-page PHP entrypoint (`timestamp.php`) renders the mobile-focused tracker and handles every check-in/check-out toggle.
- Authentication occurs at the web server layer via `.htaccess` protections; no additional user accounts exist beyond the single authorized user.
- Data persistence is handled through the bundled `timestamp.db` SQLite file so deployments stay self-contained and lightweight.

## Data model & flow (derived from design)
- Each row represents a pair of check-in/check-out timestamps plus an optional break duration; totals are calculated per day and summarized per week.
- Time data is formatted as `HH:mm` in Europe/Oslo; the server logic normalizes to that timezone before storing/displaying to keep entries consistent.
- The front-end renders up to eight weeks, always covering Monday through Sunday, with daily totals in two-decimal precision so clients can compare hours/breaks at a glance.
- The sample UI demonstrates split shifts and supports an unlimited number of check-in/check-out pairs while still presenting a single day-level total/break summary.
- Editing accepts compact time input without `:` (for example `745` -> `07:45`, `1712` -> `17:12`) and deleting both values in a pair removes that row.
- After edits, the application can use full-page reloads to refresh recalculated totals; no partial-page update mechanism is required.

## Front-end intent
- Mobile presentation is tuned for 1080×2340 screens with DPR 3: a sticky header for the “check-in/check-out” button and stacked week cards are delivered via simple HTML/CSS (see `examples/mobile.html`).
- Sample data page (`examples/mobile.html`) mirrors the production layout so designers/testers can see the Monday–Sunday rows, week separators, and total/break labeling that the live app needs to deliver.
- Approved example states are frozen as dated files under `examples/snapshots/` so UI decisions can be referenced without diffing commit history.
- Active color-preference baseline is snapshot `examples/snapshots/mobile-2026-02-14-v2.html`.
- The sample page also includes a modal day-editor prototype triggered per row to preview editing UI; the modal is front-end only and does not persist changes.
- Header state reflects check-in status: when checked in, `header h1` shows the check-in time and the action button says `Check out`; when not checked in, `header h1` shows `Timestamp` and the action button shows current time.
