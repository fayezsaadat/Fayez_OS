# Phase 5 — accounts and explicit cloud transfer

Version **0.5.0**, worker **v5.0.1**. The eleven prior tools and Settings remain;
Account adds identity, manual workspace transfer and private server files.
No UI framework, Node runtime, build step or hosted identity SDK is required.

## Delivered / intentional boundaries

- Email/password registration and login; password_hash/password_verify; regenerated
  PHP session IDs and CSRF tokens; logout; profile name/timezone; password change.
- PHP native sessions: HttpOnly, SameSite=Lax, deployment cookie path, Secure under
  HTTPS or explicit `APP_HTTPS=1`. Production account operations require HTTPS.
- 30-minute idle / 24-hour absolute expiry. Password changes bump a database epoch;
  all other sessions fail on their next authenticated request. Sessions bind to
  the installation/database as well as user ID, preventing cross-install reuse.
- File-locked registration/login/password attempt limits. Local PHP sessions and
  limiter files target a single server, not a distributed cluster.
- Owner-scoped SQL, prepared statements, CSRF on mutations and an account UUID
  header on every private operation. The UUID is a consistency guard, not a secret
  or replacement for session authorization. Server owner IDs never come from body.
- Actual MySQL-compatible storage: users, seeded expense categories, settings
  workspace snapshots and private file metadata. No sample identities or content.

Not delivered: verification email, forgotten-password recovery, email changes,
MFA/PIN/app lock, account erasure UI, session/device listings, automatic merge,
continuous/background sync, full backup/version history, native Android signing,
malware scanning or commercial hosting/security certification. Registration
explicitly explains the recovery/verification limitation.

## Browser partitioning and guest safety

`scope.js` reads one validated 32-hex public account ID at module initialization.
The selected database/cache prefix is frozen for the lifetime of that document:

- Guest: original **`fayez-os`**, schema v4, 19 stores.
- Account: **`fayez-os-account-<public-id>`**, same schema v4, separate 19 stores.
- Preference caches, draft caches and BroadcastChannels use the corresponding
  prefix. Delayed old callbacks cannot write into the new account's database.

Bootstrap checks the current PHP session before opening the shell. A changed
identity switches the marker and reloads; other tabs reload through storage
notifications. There is no automatic migration or upload of guest information.
If offline or the server cannot be reached, the last selected device copy remains
usable; this is **offline local access, not a verified online session**.

Logout requires connectivity and CSRF, revokes the current server session and
returns tabs to Guest. Local account copies are deliberately retained, so unsynced
work isn't silently destroyed. They are **not encrypted** or securely erased.
A person controlling the same browser profile/developer tools can access local
storage. This partitioning is not an offline app lock. Guest originals remain
accessible as guest data even after explicit copying into an account.

The active workspace is identified on Home. Appearance stays per workspace on the
device. There is no assumption that two hosts, ports or browser profiles share IDB.

## Workspace protocol: a deliberate single-copy transfer

Account → Workspace transfers exactly these six stores:
`notes`, `tasks`, `events`, `transactions`, `budgets`, `contacts`.

Contacts include their locally resized JPEG photo. Exact money remains decimal
**minor-unit strings**, not binary floats. Client record IDs, versions, timestamps
and deletion tombstones are included. Limit: **1,000 rows/store**, including
hidden deleted rows, and **4 MiB per HTTP JSON request**.

Excluded: preferences, drafts, calculator/currency/QR/news history, saved news,
provider caches, outbox, local file metadata and Blobs. Account cloud files use a
separate explicit upload endpoint and quota. This is not a full backup feature.

1. **Refresh status** reads the current private server revision; no device data is
   replaced. Status is deliberately not automatically trusted as a sync baseline.
2. **Upload this device's copy** takes a consistent readonly transaction across
   all six stores. After confirmation, the request supplies the displayed cloud
   revision. The server validates shape/limits, locks the user row, compares the
   revision and atomically replaces one `settings.workspace_v1` JSON snapshot.
   A mismatch returns 409, not last-write-wins. The uploaded snapshot is the copy
   taken at click time; edits made during transfer stay local for the next upload.
3. **Replace device with cloud** reads the server snapshot and captures a local
   fingerprint before confirmation. In one six-store IDB readwrite transaction,
   it rechecks the local fingerprint; changed local data aborts replacement.
   Each incoming row's version is raised above both local and incoming versions,
   preventing a pre-existing editor from silently overwriting restored records.
   Other tabs reload; saved drafts remain and may legitimately report conflicts.
4. **Copy guest records** is an explicit initial local import into an empty account
   copy. Guest originals are unchanged and nothing is uploaded. Any existing
   target row, even a deletion tombstone, blocks this initial-copy operation.

No record-level merge, multi-version rollback or automatic outbox replay is
claimed. Refreshing status and explicitly choosing Upload can intentionally replace
a newer cloud copy: the confirmation says so. Never describe this as automatic
bidirectional sync. When copies diverge, choose carefully which whole copy to keep.

The existing normalized entity SQL tables are **still forward schema**, not mirrors
of every IDB save. Inspect `settings.setting_key='workspace_v1'` for transferred
collections. This avoids pretending to have per-entity conflict resolution.
A later incremental protocol needs its own reviewed migration.

## Private files

Account → Cloud files is separate from the local Files app. Every action requires
an authenticated owner; uploads/rename/delete also require CSRF. Download fetches
require the account header; there are no public share URLs or redirects to blobs.

- 10 MiB/file, 100 files/account, 50 MiB/account. Quota calculation and insertion
  serialize under a user-row lock. PHP upload/post limits also apply.
- Extension, finfo MIME and appropriate signatures; valid UTF-8 plain text; image
  dimensions at most 4096 each side. No HTML/SVG/executable extensions. Office/ZIP
  receive container-level checks only, not full document/malware inspection.
- Opaque random 256-bit storage keys with `.blob`, not user-controlled paths;
  content SHA-256, actual size, owner and version in MySQL. Files chmod 0600.
- Protected `storage/private/files/` by default; production should put
  `PRIVATE_FILES_DIR` outside document root. `.htaccess` isn't protection on Nginx.
- Download-only, application/octet-stream, attachment filename, nosniff, no-store,
  sandbox/default-src none and same-origin resource policy. No inline execution,
  archive extraction, executable preview or automatic open.
- Same-extension rename and delete compare versions; stale changes return 409.
- On ordinary upload errors, rollback/unlink cleanup avoids orphaned content.
  Database/filesystem writes are **not one crash-atomic transaction**. Power loss,
  disk faults or failed commit can leave orphan/missing bytes; operators must
  reconcile opaque files with metadata. Back up both SQL and the private folder.
- Interrupted uploads may already have committed; refresh before retrying. Duplicate
  user uploads are allowed and consume quota. Transfers have no resume protocol.

## Modules

- `assets/js/scope.js`, `account-session.js`: frozen local namespaces, session boot,
  JSON and private binary clients; no persistent password or CSRF-token storage.
- `apps/account/index.js`, `workspace.js`: accessible forms, confirmations, manual
  transfer, local fingerprint checks, upload/download/rename/delete UI.
- `includes/account.php`, `workspace.php`, `private-files.php`: shared small backend
  services/validation; individual routes remain in `api/auth`, `api/sync`, `api/files`.
- `database/migrate-phase5.php`: CLI-only, repeatable upgrade of existing 18-table
  schema; adds users.public_id/session_version and files.version without resets.

See `PHASE_5_API.md`, `SECURITY.md`, `TEST_REPORT.md` and root installation guides.
