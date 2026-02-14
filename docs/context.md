# Context
- Single-user timestamp tracker for mobile (1080×2340, DPR 3) that records check-ins/check-outs, displays up to eight weeks with totals, and stores timestamps in `timestamp.db`.
- Time is shown in 24-hour `HH:mm` format and Norway/Europe/Oslo timezone; authentication by `.htaccess` per `docs/design.md`.
- Example layout material is housed in `examples/mobile.html` so the mobile UI constraints from `docs/examples.md` can be referenced and previewed.
- Use `docs/requests.md` to capture any new specifications mentioned during chat and keep `docs/architecture.md` in sync with decisions.

## Prompt log
- 2026-02-14 – Consolidated feature set across prototype and production app: missing-weekday add flow, bottom calendar date-picker flow (last 8 weeks), modal save validation, overlap hint behavior, and full-page reload persistence in `timestamp.php`.
- 2026-02-14 – Mobile input UX now uses coarse-pointer detection to switch modal time fields to native `type=time` (`step=60`) while desktop preserves compact text entry (`745`, `1712`) support.
- 2026-02-14 – PWA/web-app packaging stabilized for Android install: manifest now `manifest.json` with `start_url`/`scope`, `id`, `any` + dedicated opaque `maskable` icons, and authenticated manifest fetch support via `crossorigin=\"use-credentials\"`.
- 2026-02-14 – Documentation/traceability updated throughout (`docs/design.md`, `docs/architecture.md`, `docs/requests.md`, `docs/context.md`) to reflect accepted specs, implementation decisions, and current behavior (including overlap currently being allowed with additive totals).
