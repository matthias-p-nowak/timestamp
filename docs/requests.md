# Requests

## Active requests
- No open items are tracked here.
- Use `docs/todo.md` for actionable backlog items.

## Historical requests
- 2026-02-14 – Established and iterated the mobile sample workflow in `examples/mobile.html`, including split-shift examples, row-triggered modal editing, unlimited pairs, and a footer version-stamp preview; snapshot workflow was established with dated versions under `examples/snapshots/`.
- 2026-02-14 – Built `timestamp.php` into a working SQLite-backed app with schema initialization, check-in/check-out POST toggling, 8-week rendering, persisted day editing, and full-page reload interaction model.
- 2026-02-14 – Standardized edit/save behavior: delete row on both-empty values, compact time parsing (`745`, `1712`), and client-side validation with blocking save plus inline error/highlight feedback.
- 2026-02-14 – Finalized break handling: break counts only when a pair fully covers `11:30`–`12:00`; modal shows overlap hint text only when overlap exists and clarifies full-window requirement.
- 2026-02-14 – Finalized footer version behavior: tiny grey `yyyy-mm-dd-HH-MM` stamp sourced from `timestamp.php` file mtime.
- 2026-02-14 – Improved operational safety and docs hygiene: backend repair for multiple open sessions, removed automatic DB seeding, created `docs/todo.md` for active backlog, and declared `timestamp.db` must not be committed.
- 2026-02-14 – Added lightweight regression coverage via `tests/regression.sh` for toggle flow, save-day persistence/deletion, compact parsing, break-rule semantics, and modal overlap-hint rule checks.
- 2026-02-14 – Added a custom `favicon.ico` for work timekeeping branding (clock + briefcase visual) and linked it from `timestamp.php`.
- 2026-02-14 – Added `favicon-32x32.png` and `apple-touch-icon.png` plus corresponding head links for wider browser/device icon support.
- 2026-02-14 – Regenerated icon assets to improve sharpness and added full web app assets: `site.webmanifest`, `android-chrome-192x192.png`, `android-chrome-512x512.png`, plus manifest/theme-color wiring in `timestamp.php`.
- 2026-02-14 – Updated `.vscode/tasks.json` PHP server task to bind `0.0.0.0:8000` so Android devices on the same LAN can connect.
- 2026-02-14 – Added `.vscode/tasks.json` helper task `show-lan-url` to print the current LAN URL for quick Android access testing.
- 2026-02-14 – Updated `site.webmanifest` `start_url` to `./timestamp.php` so home-screen launches resolve directly to the app entrypoint.
- 2026-02-14 – Added explicit `scope` (`./`) in `site.webmanifest` to keep installed app navigation behavior predictable within the project root.
- 2026-02-14 – Accepted and promoted new specs: each displayed week shows a bottom list of missing weekdays (no entries), and each missing weekday opens the same add/edit flow for entry creation.
- 2026-02-14 – Implemented missing-weekday add flow in `timestamp.php`: each week card now renders bottom clickable missing-day items that open the existing modal add/edit workflow.
- 2026-02-14 – Implemented `examples/calendar.html` example page with a functional date picker and simple selected-date preview behavior.
- 2026-02-14 – Updated `examples/calendar.html` date display behavior to use `yyyy-mm-dd` format, aligned with `docs/design.md` European formats rule.
- 2026-02-14 – Updated `examples/calendar.html` so the native date input is hidden and a visible button opens the date picker.
- 2026-02-14 – Strengthened `new specs` workflow: added explicit ambiguity-resolution requirements in `AGENTS.md` and added a pre-promotion ambiguity checklist template in `docs/design.md`.
- 2026-02-14 – Accepted and promoted calendar-button spec: add a bottom calendar/date button above version note, open date picker limited to last 8 weeks, and route selected dates into the existing day-row modal add/edit flow.
- 2026-02-14 – Implemented the calendar-button flow in `examples/mobile.html`: bottom add-day button, hidden date picker input constrained to last 8 weeks, and selected-date handoff into the existing modal.
