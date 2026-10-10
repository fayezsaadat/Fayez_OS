> Historical Phase 2 scope. Current release: 0.3.0 / Phase 3. Use PHASE_3_FA.md, docs/PHASE_3.md and INSTALLATION.md for current installation and storage version.

# Phase 2 implementation guide

## Scope

Four functional local apps, reusing the Phase 1 shell. No account, MySQL CRUD,
cloud synchronization, external provider or native plugin has been added. The
PHP health API now reports version `0.2.0` and local-app capabilities. It does not
pretend to accept application data.

## Source map

| File | Responsibility |
|---|---|
| `app/apps/calculator/engine.js` | Tokenizer + recursive-descent evaluator, no eval |
| `app/apps/calculator/index.js` | Keypad, input, modes, result/error and history UI |
| `app/apps/notes/index.js` | Library/cards, text/checklist editor, pin/search/category |
| `app/apps/tasks/index.js` | Dashboard, filters, task list, editor and completion |
| `app/apps/calendar/index.js` | Month grid, daily agenda, event forms and categories |
| `app/apps/shared/helpers.js` | Escaping, safe formatting, dialogs, drafts, common UI |
| `app/assets/js/local-db.js` | Versioned IndexedDB, records, history, drafts, conflicts |
| `app/assets/js/reminders.js` | Foreground-only reminder scan and notice publication |
| `app/assets/css/apps.css` | Theme-aware app screens and mobile/editor layouts |

The shell has an allowlisted lazy module registry. Each `mount(container)` returns
optional `beforeLeave`, `destroy`, `back`, and `focus` hooks. The shell flushes
pending drafts before releasing a module; errors keep the current screen open.
Module listeners use AbortController and are released on exit. Each module owns a
separate mount node so a slow async open cannot overwrite a later app's screen.

## Calculator

- Supports `+ - * / ^ % !`, parentheses, unary signs and decimals/exponents.
- `sin`, `cos`, `tan`, `sqrt`, `ln`, `log`, `abs`, `pi`/`π`, `e`.
- DEG/RAD affect trig functions. Mode, angle and current expression persist.
- Power is right-associative: `2^3^2 = 512`. Unary minus follows normal precedence:
  `-2^2 = -4`, `(-2)^2 = 4`.
- Implicit multiplication supports `2pi` and `2(3+4)`, not whitespace-separated
  number tokens (`2 3` is rejected).
- `%` is a postfix division by 100, not a contextual pocket-calculator markup key.
- Domain errors are explicit: zero division, invalid logarithm/root, undefined
  tangent, factorial outside whole numbers 0–170 and non-finite overflow.
- Input is bounded at 240 characters; nesting is limited. Unknown identifiers,
  arbitrary code and HTML are rejected. No JavaScript code generation is used.
- Standard JS floating-point calculation, display rounded to 12 significant
  digits. Not an exact-decimal finance/accounting engine.
- Equals stores a successful expression/result/angle/timestamp; max 100 entries.
  Failed expressions do not create history. History can be recalled or cleared
  after confirmation. Math still works if history storage is unavailable, with
  an explicit persistence warning.

## Notes

A note has title, plain-text content, text/checklist type, checklist rows, category,
color, pinned flag, client UUID, timestamps and version. Text and checklist content
are retained when switching types. Blank checklist rows are removed on Save.

- Title: 200 characters; body: 20,000; category: 40; checklist: 200 rows, 1,000
  characters per row. Title is required; a checklist needs a nonblank item.
- Four allowlisted colors: sage, sand, lavender, rose.
- Search includes title/body/checklist/category; category is independently filtered.
  Pinned notes appear first. Empty and no-result states have usable actions.
- Formatting is a deliberately small Markdown subset: `**bold**`, `*italic*`,
  line-prefix `# ` headings and `- ` bullets. The editor inserts plain markers;
  Preview escapes HTML before generating this restricted markup. No scripts,
  embedded images, raw HTML or external links are rendered.
- The UI accepts Unicode and uses `dir="auto"` for user-written text, including
  Persian. UI labels remain the existing English design; this is not an app-wide
  localization or a Persian-calendar implementation.
- Delete requires confirmation. Created/updated dates and category are visible.

## Tasks

Title/description/priority/due-date/category and nullable completion timestamp.
Title is limited to 240 chars, description 4,000, category 40. Priority is an
allowlisted low/medium/high. A due date is optional and date-only in device-local
time (not a UTC midnight conversion).

- **Today:** incomplete and due today.
- **Upcoming:** incomplete and due after today.
- **Overdue:** incomplete with a past due date.
- **Completed:** all completed tasks.
- **All:** includes undated tasks, all dates and completion states.

Summary counts are global across the local task library. Search/category/priority
filters further narrow the selected view; they do not silently redefine global
counts. Complete/reopen preserves the record. Editing does not automatically undo
completion. “Save as new” creates a separate incomplete copy when needed.

## Calendar

Gregorian monthly grid with 42 correctly aligned days, adjacent-month dates,
today/selected states and event dots. The daily agenda sorts starts chronologically.
Switch month/day, previous/next, Today, a direct date jump and category filtering.

Events have required title/date/time, optional description, duration, category and
reminder offset. Title max 200, description 4,000, category 40. Duration is 5–1440
minutes, in steps of 5. Start/end are stored as UTC ISO instants with the device
IANA timezone captured as metadata. Editing/displays use the current device zone.
DST gap times that do not round-trip are rejected. An ambiguous repeated hour uses
the browser's default mapping; an advanced timezone editor is not included.

The current agenda groups an event by its **start day**. It does not split an
overnight event into multiple day segments, or support recurrence/all-day events.
Those are not claimed as Phase 2 features.

### Reminder behavior

Choose none, at time, 5, 15, 30 or 60 minutes before. `reminders.js` checks every
30 seconds while visible, after event writes, and on returning to the foreground.
A due notice is posted only if the event has not ended. A saved fingerprint prevents
repeat notices for the same event/start/offset. Changing the time/offset permits a
new reminder; deleting an event prevents future reminders, but does not erase an
already published notice. At most five new notices are emitted per scan.

The OS notification indicator preference controls the unread badge/toast; the
notice center still retains the local event notice. The welcome notice is real
system onboarding, not fabricated personal activity. No browser Notification
permission, push subscription, service-worker alarm or Android scheduler is used.
Closing/suspending the app stops reliable reminder delivery. Missed events that
have already ended are not retrospectively alarmed.

## Local storage and consistency

Database `fayez-os`, IndexedDB version **2**. Upgrade adds missing stores/indexes
without dropping existing stores. The original appearance data is retained.

| Store | Data |
|---|---|
| `preferences` | Theme, shell settings, recents/notices, calculator state, fired reminder fingerprints |
| `notes` | Versioned note records, soft deletion tombstones |
| `tasks` | Versioned task records, soft deletion tombstones |
| `events` | Versioned event records, soft deletion tombstones |
| `calculations` | At most 100 expression/result records |
| `drafts` | One recoverable editor draft per app/origin, nullable tombstone on clear |
| `outbox` | Still reserved and empty; no unowned data is being uploaded |

Record writes are read/compare/write operations in a readwrite transaction.
Existing IDs must exist and their supplied version must match. Stale/deleted writes
are rejected instead of silently overwriting a newer record. A successful edit
increments version and preserves creation time. Deletion sets `deletedAt`; list
queries exclude deleted rows. These tombstones are for future sync design; there
is no restore/bin UI yet.

Saving an editor record and clearing its durable draft happen in one transaction.
The caller only announces success after a transaction completes. Drafts additionally
use a small synchronous localStorage cache, revision timestamps and a serialized,
debounced IndexedDB writer, covering immediate reload/close. An older draft write
cannot replace a newer draft tombstone. Drafts are not cloud backups.

Cross-tab data changes use BroadcastChannel when available. Saved-record version
checks still apply without it. One draft per app is shared by the browser origin;
editing drafts concurrently in multiple tabs is not a multi-author merge system.
“Save as new” provides an explicit escape path for a stale saved-record draft.

Data stores do not silently fall back to disposable in-memory CRUD. If IndexedDB
is blocked/unavailable, those apps show a storage error and Retry. The OS shell can
still use its existing preference fallbacks. If storage fills, forms stay open
with an error rather than claiming a save. No local data is encrypted; no account
boundary has been claimed. Production multi-user partitioning is a Phase 5 gate.

## API / later MySQL mapping

The entire local repository is behind `local-db.js`. Future authenticated adapters
can map IDs to `client_id`, `updatedAt` to `updated_at`, `version` to `version`, and
`deletedAt` to `deleted_at` where the server schema provides them. JavaScript field
names need explicit mapping (not blind object-to-SQL). Events will require a server
migration adding client UUID/version/tombstone columns before offline event sync.
The present SQL schema is unchanged and is not a synchronization endpoint.

Do not automatically upload these unauthenticated local records when a user signs
in later. Ask which account should own/import them, partition local stores by user,
and implement authorization, CSRF, idempotency and conflict handling first.

## Offline and updates

Service-worker `v2.0.1` precaches all four app modules, the calculator engine, shared
helpers, repository, reminder worker code and app stylesheet. It caches only the
public shell asset allowlist, never API responses or private data. Installed apps
start under `/app/`; the root PHP redirect still needs a server.

The new worker's application assets use a coherent versioned cache. The central
config file is network-first with a cached fallback. Updates wait until older
clients close. Keep the same origin/path when upgrading so browser data is retained.
No local server/network is needed for application CRUD after the shell has been
cached, but IndexedDB must remain available.
