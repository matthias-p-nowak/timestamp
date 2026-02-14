# Context
- Single-user timestamp tracker for mobile (1080×2340, DPR 3) that records check-ins/check-outs, displays up to eight weeks with totals, and stores timestamps in `timestamp.db`.
- Time is shown in 24-hour `HH:mm` format and Norway/Europe/Oslo timezone; authentication by `.htaccess` per `docs/design.md`.
- Example layout material is housed in `examples/mobile.html` so the mobile UI constraints from `docs/examples.md` can be referenced and previewed.
- Use `docs/requests.md` to capture any new specifications mentioned during chat and keep `docs/architecture.md` in sync with decisions.

## Prompt log
- 2026-02-14 – logged the instruction interpretation request about `AGENTS.md` line 18 and its implications for `docs/requests.md`.
- 2026-02-14 – user referenced `docs/examples.md` and asked to “do this”, which currently points to creating an example `mobile.html` page.
- 2026-02-14 – moved the mocked-up mobile layout inside `examples/mobile.html` and created the `examples` folder so the documentation accurately reflects the samples location.
- 2026-02-14 – updated `examples/mobile.html` so each total/break pair uses `white-space: nowrap` and stays on one line per the latest feedback.
- 2026-02-14 – added made-up days with up to three check-in/check-out pairs in `examples/mobile.html` to cover split-shift examples.
- 2026-02-14 – created snapshot workflow: copied approved layout to `examples/snapshots/mobile-2026-02-14-v1.html` and added `examples/snapshots/README.md`.
- 2026-02-14 – created `examples/snapshots/mobile-2026-02-14-v2.html` from current `examples/mobile.html` and set it as the active color-preference baseline.
- 2026-02-14 – added a sample-only modal day-editor preview in `examples/mobile.html` so day edit controls can be visually tested without persistence.
- 2026-02-14 – updated `AGENTS.md` abbreviations by adding `snapshot` with the current snapshot workflow, including staging and committing.
- 2026-02-14 – ran `snapshot`: copied current `examples/mobile.html` to `examples/snapshots/mobile-2026-02-14-v3.html`, updated snapshot index, and prepared commit.
- 2026-02-14 – changed the modal trigger to use clickable/focusable `.day-row` elements directly and removed `edit-day-btn` controls.
- 2026-02-14 – removed `div.editor-header` from the modal editor and kept title/close layout with direct elements.
- 2026-02-14 – removed modal header elements entirely by deleting both the title element and close button in `examples/mobile.html`.
- 2026-02-14 – removed modal `span.pair-label` text so the editor shows only input fields in a two-column layout.
- 2026-02-14 – ran `snapshot`: copied current `examples/mobile.html` to `examples/snapshots/mobile-2026-02-14-v4.html` and updated snapshot index.
