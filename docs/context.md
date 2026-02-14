# Context
- Single-user timestamp tracker for mobile (1080×2340, DPR 3) that records check-ins/check-outs, displays up to eight weeks with totals, and stores timestamps in `timestamp.db`.
- Time is shown in 24-hour `HH:mm` format and Norway/Europe/Oslo timezone; authentication by `.htaccess` per `docs/design.md`.
- Example layout material is housed in `examples/mobile.html` so the mobile UI constraints from `docs/examples.md` can be referenced and previewed.
- Use `docs/requests.md` to capture any new specifications mentioned during chat and keep `docs/architecture.md` in sync with decisions.

## Prompt log
- 2026-02-14 – consolidated status: finalized manifest launch settings (`start_url`, `scope`) and completed missing-weekday feature delivery end-to-end: promoted the new specs to canonical design, implemented bottom-of-week missing-day add buttons in `timestamp.php` using the existing modal flow, expanded regression coverage to include this wiring, and synchronized architecture/request logs.
