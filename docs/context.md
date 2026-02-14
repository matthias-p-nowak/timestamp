# Context
- Single-user timestamp tracker for mobile (1080×2340, DPR 3) that records check-ins/check-outs, displays up to eight weeks with totals, and stores timestamps in `timestamp.db`.
- Time is shown in 24-hour `HH:mm` format and Norway/Europe/Oslo timezone; authentication by `.htaccess` per `docs/design.md`.
- Example layout material is housed in `examples/mobile.html` so the mobile UI constraints from `docs/examples.md` can be referenced and previewed.
- Use `docs/requests.md` to capture any new specifications mentioned during chat and keep `docs/architecture.md` in sync with decisions.

## Prompt log
- 2026-02-14 – initialized project docs and sample structure: interpreted AGENTS instructions, added `docs/context.md`, `docs/architecture.md`, `docs/requests.md`, created `examples/mobile.html`, and aligned docs references to the `examples/` folder.
- 2026-02-14 – iterated the mobile sample UI: enforced non-wrapping totals, added split-shift examples, added/removed color experiments, built and refined a sample-only modal day editor, switched edit trigger to clickable/focusable `.day-row`, removed modal header chrome, removed pair labels, and moved to dynamic unlimited pair inputs with an `Add pair` control.
- 2026-02-14 – established and used snapshot workflow: documented `snapshot` abbreviation, created snapshots `v1` through `v4`, maintained `examples/snapshots/README.md`, and committed snapshot baselines.
- 2026-02-14 – promoted stable rules into canonical docs: reorganized `docs/design.md` into structured sections, incorporated accepted draft specs (backend/storage, check-in header behavior, compact time input parsing, full-page reload model), and synchronized `docs/architecture.md`.
