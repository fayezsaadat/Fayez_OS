# Database plan — fayez_os

`database/database.sql` defines 18 tables. In Phase 5, **users, categories, settings
and files are active**; other normalized tables remain forward schema. Six local
collections transfer explicitly as one owner-scoped `settings.workspace_v1` JSON
snapshot, not per-entity SQL mirrors. Import is optional only for guest mode. No fake accounts or transactions
are seeded. Tables use InnoDB, utf8mb4, primary keys, appropriate foreign keys and
`created_at` / `updated_at`. The schema targets MySQL 8+ and MariaDB 10.4+.

## Relationships

```text
users 1 ── N categories
      1 ── N expenses / income / budgets
      1 ── N notes / tasks / events / contacts
      1 ── N bookmarks / saved_news / reading_history
      1 ── N files / notifications / settings
      1 ── N sessions / password_resets / sync_operations

categories (id, user_id) ── (category_id, user_id) in owned app rows
```

Composite category ownership foreign keys prevent a row from referencing another
user's category. Endpoints must additionally verify the category scope (e.g.
`expense`, not `note`) and every user's authorization. Categories are archived,
not hard deleted while referenced. A future account erasure flow must delete
child app rows before categories in one controlled transaction, because category
foreign keys use RESTRICT. Database metadata alone does not delete uploaded bytes;
file cleanup needs an after-commit job.

## Table plan

| Table | Purpose & notable constraints/indexes |
|---|---|
| `users` | Unique normalized email, password hash, optional PIN hash, timezone/currency |
| `categories` | Per-user scope/name unique; archived state; composite ownership key |
| `expenses` | Positive DECIMAL amount; date/payment method/currency; user/date index |
| `income` | Same monetary rules; independently indexed by user/date |
| `budgets` | One amount per user, month start and currency |
| `notes` | Client UUID, text/structured checklist, pin/color, version, deletion tombstone |
| `tasks` | Client UUID, priority, UTC due date, completion/version/tombstone |
| `events` | UTC start/end with timezone, all-day flag, reminder offset; time check |
| `contacts` | Name/phone/email/address/avatar key; favorite/name indexes |
| `bookmarks` | URL and SHA-256 deduplication key per user |
| `saved_news` | Provider/article unique per user; canonical source URL and metadata |
| `reading_history` | URL deduplication; last read time, title, per-user date index |
| `files` | Unique random storage key, display/original names, MIME/bytes/SHA-256 |
| `notifications` | Typed notice, allowlisted internal route, read state |
| `settings` | Unique user/key, JSON value, version for future sync |
| `sessions` | Hashed session identifier, device metadata, expiry/revocation |
| `password_resets` | Hashed one-use random token, expiry/use time; never plaintext token |
| `sync_operations` | Unique user/operation UUID and committed result version |

The `sessions` table is intended for device management/revocation metadata, **not
an installed PHP session handler**. Phase 5 enforces revocation on each
protected request. Phase 5 uses native PHP session storage and a users.session_version epoch, verified
on every protected request; the SQL sessions table remains reserved.

## Data rules

- Normalize emails deliberately (trim and agreed case policy), validate on server,
  handle duplicates uniformly. Use `password_hash(..., PASSWORD_DEFAULT)` and
  `password_verify()` in Phase 5; never store plaintext passwords or PINs.
- Never store money in floating-point columns. Accept bounded decimal strings,
  use fixed-decimal server arithmetic, enforce positive transaction amounts.
- Compute balances and statistics from transactions, grouping **by currency**.
  Do not add AFN and USD together; conversion needs a dated, explicitly sourced
  exchange rate. Currency allowlist: AFN, USD, EUR, GBP, IRR, PKR, TRY, AED, SAR.
- `month_start` must be validated to the first of the month in the API.
- Keep date-only accounting dates as DATE; UTC for instants. Use account timezone
  for display, agenda boundaries and reminders. Preserve event timezone.
- Contacts' phone numbers are strings (leading + and zeros matter).
- Note formatting must use an allowlisted structured format. The stored content
  is not trusted HTML; render with safe text/formatting components.
- `files.storage_key` is a generated relative key, never a client-supplied path.
  Store outside public root where possible. Files remain user-owned and private.
- JSON does not excuse missing validation: enforce allowable keys, types,
  checklist length and content-size limits at the API boundary.

## Queries and scale

All mutable queries include the session user's ID, e.g. future note update:

```sql
UPDATE notes
SET title = :title, content = :content, version = version + 1
WHERE id = :id AND user_id = :user_id AND version = :base_version;
```

Prepare and bind via PDO with emulated prepares disabled. A zero-row update
requires a scoped follow-up to distinguish missing/deleted from version conflict,
without leaking other users' records. Allowlist sort columns; identifiers cannot
be parameter-bound. Cap page size (e.g. 50, max 100). Prefer cursor pagination
on `(transaction_date,id)` or `(updated_at,id)`. Use scoped indexes before adding
full-text search in Phase 6. Avoid reading every note for each keystroke.

Example monthly expense range uses an index-friendly half-open interval:

```sql
SELECT category_id, currency, SUM(amount) AS total
FROM expenses
WHERE user_id = :user_id
  AND transaction_date >= :month_start
  AND transaction_date < :next_month
GROUP BY category_id, currency;
```

## Migrations & sync

The SQL file initializes empty databases, not versioned migrations. `IF NOT EXISTS`
does **not** upgrade existing tables. Before changing this schema in future phases,
introduce ordered migration files with a schema version ledger, backup/restore
checks and staged deployment. Use transaction boundaries for multi-row changes.

Notes/tasks have per-user client UUIDs and row versions; sync operations provide
idempotency. Retain deletion tombstones until clients have acknowledged them.
The proposed sync flow is in `UI_ARCHITECTURE.md`; it is not active in Phase 4.
An authenticated user's local data must be partitioned and handled safely on
logout/account switch before cloud features ship.

## First-run categories (Phase 5)

Within the registration transaction, create user-owned expense categories:
Food, Transport, Shopping, Bills, Education, Health, Entertainment and Other.
No global seed rows are used, so ownership is unambiguous. Income/note/task/event/
contact categories follow their own scope. Do not create categories until an
actual user exists.

## Phase 2 status

The server schema is unchanged. Actual Phase 2 records are in IndexedDB, not these
tables. `docs/PHASE_2.md` documents local shapes and future adapter mappings. Event
sync will need a future migration adding client UUID/version/tombstone columns;
no automatic local-to-MySQL synchronization is claimed or active.

## Phase 3 status

The forward MySQL schema remains unchanged; it is not runtime storage yet.
Transactions, budgets, contacts and conversion history now use IndexedDB v3.
Before Phase 5 sync, add ordered migrations for their UUID/version/tombstone
metadata, map local category strings to user-owned IDs, define photo storage, and
reconcile integer minor-unit strings with SQL DECIMAL without float coercion.
The optional currency endpoint only caches public quotes on disk; it writes no
personal or account record to MySQL. See `PHASE_3.md` for local shapes.

## Phase 4 storage decision

The forward SQL schema remains unchanged. IndexedDB v4 adds seven stores for
weather/news snapshots, bookmarks/history, QR history and file metadata/Blobs.
Phase 4 deferred private server file CRUD; Phase 5 now supplies authenticated
private routes. There is still no anonymous upload endpoint. Define consent,
quota, conflict handling and a private blob-storage migration before cloud import.

## Phase 5 migration and active data contract

`users.public_id` is a unique random 32-hex identifier used to partition browser
workspaces; `users.session_version` invalidates older logins. Passwords use the
existing password_hash column. `files.version` supplies optimistic rename/delete
checks. Schema remains 18 tables; no fake users/passwords or private bytes are seeded.

For existing Phase 1–4 SQL, back up first and run `database/migrate-phase5.php`
with a CLI administrator connection. Repeated execution is supported; do not
assume CREATE TABLE IF NOT EXISTS upgrades columns. Use the restricted application
user again after migration.

Workspace CAS uses the revision in settings.setting_value, with the owner row
locked before the revision read/write. Amounts remain minor-unit strings inside
JSON; no float-to-DECIMAL conversion or fictional balance materialization occurs.
Private file metadata is owned SQL; bytes are separately protected files. See
PHASE_5.md for limits, crash recovery and the non-automatic sync model.


## Phase 6 actual implementation

No additional SQL tables/migration. Global search is a bounded, in-memory local
workspace index, not SQL full-text search. PIN verifier/cooldown are scoped local
privacy settings, not the reserved users.pin_hash field. Full local archives read
all stores; since Phase 8 that is **28 v5 stores**. Optional new stores missing from an
older archive are emptied on restore. `_cache-epoch` in preferences is an internal postrestore cache
barrier. Notification preferences/receipts and backup activity also use preferences.
The revoke endpoint uses the existing users.session_version epoch. Server disaster
recovery remains an operator SQL + protected-files procedure, not a browser archive.
