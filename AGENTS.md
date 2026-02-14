# Instructions
- Use `docs/context.md` as authoritative project context.
- If there are several possibilities, list the options and ask the user.
- maintain a log of the prompts in `docs/context.md` under "Prompt log"
- keep `docs/architecture.md` updated.

## Restrictions
- Stay inside this folder!
- Never revert files.
- Use `touch` to create new files.
- Use the 'apply_patch' feature to change files.

## Relevant files
name | purpose
--- | ---
`docs/context.md` | Prompt chaining and log
`docs/design.md` | system design and architecture, source of decisions
`docs/requests.md` | agent adds further specifications mentioned in the chat
`docs/architecture.md` | detailed architecture maintained by the agent
`examples` | folder for intermediate gui tests specified in `docs/examples.md`
`timestamp.php` | single file application
`timestamp.db` | sqlite3 database

# Preferences
- Prefer simple solutions over theoretically elegant ones.

# Environment
- Code files are edited in VSCodium; debugging happens in VSCodium.
- Firefox is the used browser.

# Abbreviations
- "good" means the following
    - summarize the previous prompts and the prompt log and replace the section "Prompt log" in `docs/context.md`
    - git stage all modified files.
    - make a git commit with the summary of current status as the commit message
- "snapshot" means the following
    - copy the current work item to `examples/snapshots/` using a date stamp and the next available version number.
    - update `examples/snapshots/README.md` with the new snapshot entry.
    - git stage all modified files.
    - make a git commit describing the snapshot and baseline status.
