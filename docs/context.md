# Context
- Single-user timestamp tracker for mobile (1080×2340, DPR 3) that records check-ins/check-outs, displays up to eight weeks with totals, and stores timestamps in `timestamp.db`.
- Time is shown in 24-hour `HH:mm` format and Norway/Europe/Oslo timezone; authentication by `.htaccess` per `docs/design.md`.
- Example layout material is housed in `examples/mobile.html` so the mobile UI constraints from `docs/examples.md` can be referenced and previewed.
- Use `docs/requests.md` to capture any new specifications mentioned during chat and keep `docs/architecture.md` in sync with decisions.

## Prompt log
- 2026-02-14 – session summary: established and refined docs/workflows (`new specs`, `snapshot`, `good`), evolved `examples/mobile.html` into the approved mobile baseline (including modal editor and footer version-stamp preview), and implemented `timestamp.php` as a working SQLite-backed app with check-in/check-out toggling, 8-week rendering, day editing persistence, compact time parsing, and client-side modal validation; synchronized `docs/design.md`, `docs/architecture.md`, and `docs/requests.md` throughout.
