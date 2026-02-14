# Context
- Single-user timestamp tracker for mobile (1080×2340, DPR 3) that records check-ins/check-outs, displays up to eight weeks with totals, and stores timestamps in `timestamp.db`.
- Time is shown in 24-hour `HH:mm` format and Norway/Europe/Oslo timezone; authentication by `.htaccess` per `docs/design.md`.
- Example layout material is housed in `examples/mobile.html` so the mobile UI constraints from `docs/examples.md` can be referenced and previewed.
- Use `docs/requests.md` to capture any new specifications mentioned during chat and keep `docs/architecture.md` in sync with decisions.

## Prompt log
- 2026-02-14 – renamed web app manifest from `site.webmanifest` to `manifest.json`, updated `timestamp.php` manifest link/cache-busting target, and removed the old manifest file.
- 2026-02-14 – generated dedicated opaque maskable Android icons (`android-chrome-192x192-maskable.png`, `android-chrome-512x512-maskable.png`) and wired them in `site.webmanifest` to avoid gray adaptive launcher icons.
- 2026-02-14 – added manifest cache-busting query wiring in `timestamp.php` (`site.webmanifest?v=<filemtime>`) to force Chrome to re-fetch manifest/icon updates.
- 2026-02-14 – hardened PWA manifest for Android home-screen icon pickup by adding manifest `id` and explicit `any`/`maskable` icon entries.
- 2026-02-14 – verified web manifest pathing for subdirectory hosting (for example `/ts/timestamp.php`): retained relative `start_url`/`scope` and made icon `src` entries explicitly relative.
- 2026-02-14 – implemented mobile-only numeric time editor inputs: modal check-in/check-out fields now use native `type=time` (`step=60`) on coarse-pointer devices in both `timestamp.php` and `examples/mobile.html`, while desktop remains text/compact-input compatible.
- 2026-02-14 – removed the calendar add button tooltip from `examples/mobile.html` and aligned architecture notes to match.
- 2026-02-14 – reviewed latest local code changes, ran syntax + regression checks (`php -l`, `tests/regression.sh`, all passing), and synchronized architecture notes with the calendar-button tooltip behavior in `examples/mobile.html`.
- 2026-02-14 – added a tooltip on the `examples/mobile.html` calendar add button clarifying: "Use this to add periods from other days".
- 2026-02-14 – implemented option 1 in real app (`timestamp.php`): added bottom `Add day` button with hidden native date picker above the version note, constrained picker range to last 8 weeks, routed selected date into the existing day modal flow, and enforced the same date-window rule server-side in `save-day`; updated regression tests accordingly.
- 2026-02-14 – consolidated status: finalized manifest launch settings (`start_url`, `scope`) and completed missing-weekday feature delivery end-to-end: promoted the new specs to canonical design, implemented bottom-of-week missing-day add buttons in `timestamp.php` using the existing modal flow, expanded regression coverage to include this wiring, and synchronized architecture/request logs.
- 2026-02-14 – aligned `examples/calendar.html` date output with the design format rule by displaying selected dates as `yyyy-mm-dd`.
- 2026-02-14 – adjusted `examples/calendar.html` date-picker UX: hid the raw date input and added a dedicated button that opens the native picker while retaining `yyyy-mm-dd` selected-date display.
- 2026-02-14 – updated the specification process by adding ambiguity-resolution gating to `AGENTS.md` `new specs` and adding an ambiguity checklist template under `docs/design.md` draft specs.
- 2026-02-14 – implemented the additional example file referenced by docs: created `examples/calendar.html` with a standalone date-picker UI and basic selected-date preview interaction.
- 2026-02-14 – executed `new specs` promotion for “adding other days”: resolved ambiguity by choosing last-8-weeks constraint for picker range, moved the calendar-button flow into canonical design rules, and synchronized architecture/request logs.
- 2026-02-14 – implemented calendar-button prototype in `examples/mobile.html` with hidden date input, last-8-weeks picker bounds, and selected-date modal handoff.
