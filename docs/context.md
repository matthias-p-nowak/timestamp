# Context
- Single-user timestamp tracker for mobile (1080×2340, DPR 3) that records check-ins/check-outs, displays up to eight weeks with totals, and stores timestamps in `timestamp.db`.
- Time is shown in 24-hour `HH:mm` format and Norway/Europe/Oslo timezone; authentication by `.htaccess` per `docs/design.md`.
- Example layout material is housed in `examples/mobile.html` so the mobile UI constraints from `docs/examples.md` can be referenced and previewed.
- Use `docs/requests.md` to capture any new specifications mentioned during chat and keep `docs/architecture.md` in sync with decisions.

## Prompt log
- 2026-02-14 – consolidated status: finalized the SQLite-backed `timestamp.php` workflow with check-in/check-out toggling, persisted modal editing, compact time parsing, client-side validation, full-window break rule enforcement, overlap-triggered helper messaging, footer version stamp from file mtime, backend repair for multiple open sessions, and removal of automatic DB seeding; promoted and synchronized canonical rules in `docs/design.md`/`docs/architecture.md`, kept request history in `docs/requests.md`, established actionable backlog in `docs/todo.md`, and enforced the rule to never commit `timestamp.db`.
- 2026-02-14 – cleaned up `docs/requests.md` structure by separating `Active requests` from `Historical requests`, with active actionable work kept in `docs/todo.md`.
- 2026-02-14 – compressed `docs/requests.md` historical entries into grouped summaries to keep the request history readable while preserving key decisions and outcomes.
