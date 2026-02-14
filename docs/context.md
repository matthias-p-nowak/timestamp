# Context
- Single-user timestamp tracker for mobile (1080×2340, DPR 3) that records check-ins/check-outs, displays up to eight weeks with totals, and stores timestamps in `timestamp.db`.
- Time is shown in 24-hour `HH:mm` format and Norway/Europe/Oslo timezone; authentication by `.htaccess` per `docs/design.md`.
- Example layout material is housed in `examples/mobile.html` so the mobile UI constraints from `docs/examples.md` can be referenced and previewed.
- Use `docs/requests.md` to capture any new specifications mentioned during chat and keep `docs/architecture.md` in sync with decisions.

## Prompt log
- 2026-02-14 – consolidated status: delivered a SQLite-backed `timestamp.php` app with real check-in/check-out toggling, 8-week rendering, persisted modal day editing, compact time parsing, client-side modal validation, footer version stamp from file mtime, refined break logic (full `11:30`–`12:00` coverage only), and overlap-triggered helper hint text; synchronized `docs/design.md`, `docs/architecture.md`, and `docs/requests.md` through multiple `new specs` updates.
