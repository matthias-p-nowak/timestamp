# Context
- Single-user timestamp tracker for mobile (1080×2340, DPR 3) that records check-ins/check-outs, displays up to eight weeks with totals, and stores timestamps in `timestamp.db`.
- Time is shown in 24-hour `HH:mm` format and Norway/Europe/Oslo timezone; authentication by `.htaccess` per `docs/design.md`.
- Example layout material is housed in `examples/mobile.html` so the mobile UI constraints from `docs/examples.md` can be referenced and previewed.
- Use `docs/requests.md` to capture any new specifications mentioned during chat and keep `docs/architecture.md` in sync with decisions.

## Prompt log
- 2026-02-14 to 2026-02-15 – Core app behavior and mobile UX were consolidated in `timestamp.php`: toggle check-in/out POST flow, day-editor persistence, missing-weekday add flow, bottom date-picker entry flow, compact-time parsing, edit-window enforcement, and overlap-hint validation behavior.
- 2026-02-14 to 2026-02-15 – Platform packaging and UI scaffolding were stabilized: externalized `timestamp.css`/`timestamp.js`, maintained PWA/icon metadata, and aligned example/snapshot artifacts with the production layout constraints.
- 2026-02-15 – Backend data access was migrated from `SQLite3` APIs to PDO (`sqlite:`), including shared fetch helpers and regression/lint verification.
- 2026-02-15 – In-code documentation was normalized (PHPDoc coverage, formatting examples), plus redirect intent around POST handlers was clarified.
- 2026-02-15 – Output escaping was centralized by introducing `e(string): string` in `timestamp.php` and replacing template-wide direct `htmlspecialchars(...)` calls; regression checks passed after the refactor.
