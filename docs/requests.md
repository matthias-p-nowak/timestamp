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
