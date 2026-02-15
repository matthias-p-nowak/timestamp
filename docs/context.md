# Context
- Single-user timestamp tracker for mobile (1080×2340, DPR 3) that records check-ins/check-outs, displays up to eight weeks with totals, and stores timestamps in `timestamp.db`.
- Time is shown in 24-hour `HH:mm` format and Norway/Europe/Oslo timezone; authentication by `.htaccess` per `docs/design.md`.
- Example layout material is housed in `examples/mobile.html` so the mobile UI constraints from `docs/examples.md` can be referenced and previewed.
- Use `docs/requests.md` to capture any new specifications mentioned during chat and keep `docs/architecture.md` in sync with decisions.

## Prompt log
- 2026-02-15 – Refactored `timestamp.js` to centralize front-end selectors, labels, validation messages, and numeric constants in a single `UI_CONFIG` block; updated regression source checks accordingly.
- 2026-02-15 – Split inline front-end assets from `timestamp.php` into `timestamp.css` and `timestamp.js`, and updated HTML to load external stylesheet/script files.
- 2026-02-15 – Executed option 3 PDO migration in `timestamp.php`: converted `SQLite3` calls to PDO, removed unused `findOpenEntry`, added shared `fetchAllAssoc(PDOStatement)` helper, and verified with lint + regression tests.
- 2026-02-15 – Scoped a PDO migration plan for `timestamp.php` (touchpoints, risk options, and rollout choices) before any code conversion.
- 2026-02-15 – Added explicit example outputs to the `formatWeekRange` PHPDoc in `timestamp.php` to clarify same-month and cross-month range formatting.
- 2026-02-15 – Reviewed `timestamp.php` function comments and standardized PHPDoc coverage: corrected existing descriptions and added missing docs for data, validation, action-routing, and week-loading helpers.
- 2026-02-14 – Reworded `linkedin.md` user-summary again to be more casual/conversational while keeping the same intent (chat guidance + `docs/design.md` refinement).
- 2026-02-14 – Reverted `linkedin.md` user-summary tone back to a more human/less formal style on request.
- 2026-02-14 – Revised `linkedin.md` user-summary language to a more formal/professional tone while preserving the same meaning (chat-driven requirements + `docs/design.md` refinement).
- 2026-02-14 – Consolidated feature set across prototype and production app: missing-weekday add flow, bottom calendar date-picker flow (last 8 weeks), modal save validation, overlap hint behavior, and full-page reload persistence in `timestamp.php`.
- 2026-02-14 – Mobile input UX now uses coarse-pointer detection to switch modal time fields to native `type=time` (`step=60`) while desktop preserves compact text entry (`745`, `1712`) support.
- 2026-02-14 – PWA/web-app packaging stabilized for Android install: manifest now `manifest.json` with `start_url`/`scope`, `id`, `any` + dedicated opaque `maskable` icons, and authenticated manifest fetch support via `crossorigin=\"use-credentials\"`.
- 2026-02-14 – Documentation/traceability updated throughout (`docs/design.md`, `docs/architecture.md`, `docs/requests.md`, `docs/context.md`) to reflect accepted specs, implementation decisions, and current behavior (including overlap currently being allowed with additive totals).
