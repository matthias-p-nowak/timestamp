# Context
- Single-user timestamp tracker for mobile (1080×2340, DPR 3) that records check-ins/check-outs, displays up to eight weeks with totals, and stores timestamps in `timestamp.db`.
- Time is shown in 24-hour `HH:mm` format and Norway/Europe/Oslo timezone; authentication by `.htaccess` per `docs/design.md`.
- Example layout material is housed in `examples/mobile.html` so the mobile UI constraints from `docs/examples.md` can be referenced and previewed.
- Use `docs/requests.md` to capture any new specifications mentioned during chat and keep `docs/architecture.md` in sync with decisions.

## Prompt log
- 2026-02-14 – consolidated status: finalized icon and app-branding updates by regenerating sharp work-timekeeping assets (`favicon.ico`, `favicon-32x32.png`, `apple-touch-icon.png`, Android icons), adding `site.webmanifest` plus head wiring (`manifest`, `theme-color`, icon links) in `timestamp.php`, and synchronizing architecture/request documentation.
