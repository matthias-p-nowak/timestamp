# Context
- Single-user timestamp tracker for mobile (1080×2340, DPR 3) that records check-ins/check-outs, displays up to eight weeks with totals, and stores timestamps in `timestamp.db`.
- Time is shown in 24-hour `HH:mm` format and Norway/Europe/Oslo timezone; authentication by `.htaccess` per `docs/design.md`.
- Example layout material is housed in `examples/mobile.html` so the mobile UI constraints from `docs/examples.md` can be referenced and previewed.
- Use `docs/requests.md` to capture any new specifications mentioned during chat and keep `docs/architecture.md` in sync with decisions.

## Prompt log
- 2026-02-14 to 2026-02-15 – Built and stabilized the production `timestamp.php` flow: PDO-backed storage, check-in/check-out POST handling, day-editor save/delete behavior, compact time parsing, break-rule handling, missing-weekday add flow, and date-picker constrained to the last eight weeks.
- 2026-02-14 to 2026-02-15 – Consolidated front-end and platform packaging: moved assets to `timestamp.css`/`timestamp.js`, kept mobile-first interaction patterns, and maintained icon/manifest/PWA metadata plus regression coverage.
- 2026-02-15 – Normalized maintainability details: standardized PHPDoc coverage, clarified POST redirect intent, and centralized HTML escaping in `timestamp.php` via `e(string): string`.
- 2026-02-15 – Completed `new specs` acceptance for cache-busting scope (option 2): no cache-busting mechanisms for `timestamp.css` and `timestamp.js`; promoted in `docs/design.md` and synchronized in `docs/architecture.md` and `docs/requests.md`.
