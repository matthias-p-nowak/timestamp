# TODO

## Open items
- [ ] Add a lightweight regression test script for key flows:
  - check-in/check-out toggle behavior
  - save-day persistence (including delete on empty row)
  - compact time parsing (`745`, `1712`)
  - break-rule behavior (full `11:30`-`12:00` coverage only)
  - modal overlap hint visibility rule

## Notes
- `docs/requests.md` remains the historical request log.
- `docs/todo.md` is the actionable backlog.
- `timestamp.db` must never be committed.
