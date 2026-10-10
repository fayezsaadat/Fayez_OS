# Phase 6 — implementation contract

Version 0.6.0. Vanilla ES modules/PHP; unchanged IndexedDB v4 (19 stores) at the time —
Phase 8 later moved it to v5 / 28 stores, see docs/PHASE_8.md — and SQL
schema. No frontend/server build framework or runtime CDN.

## New modules

- `apps/search/{index,model}.js`: ephemeral workspace index, 180 ms debounce,
  NFKC + Persian/Arabic letter/digit folding, conjunctive tokens, type filter,
  40-row incremental display / 200 maximum results. Seven saved-data stores plus
  app metadata, at most 10,000 indexed rows per data store. Refresh is explicit;
  data-change events mark the view stale. Queries are not persisted or uploaded.
  Excludes drafts, QR payloads, binary content and server-only data. Result preview
  is escaped; its action explicitly opens the owning **app**, not an exact record.
- `assets/js/notifications.js`: transactional IDB notices and bounded dedup receipts,
  category/quiet-hour preferences, permission-aware service-worker notifications.
  Latest 50 notices, 512 receipts; older evicted fingerprints can recur. Deleting
  a retained notice does not delete its receipt. Local-only, no server delivery.
- `reminders.js`: checks visible, unlocked pages every 30 seconds and relevant
  data/visibility events; max five newly emitted items per check. Calendar enabled
  by default; due-today tasks opt-in. No closed-app alarm or push guarantee.
- `privacy.js`: scoped screen curtain, salted PBKDF2-SHA256 verifier (310,000 rounds,
  16-byte salt, 32-byte verifier), normalized 6–12 digit PIN, 1/5/15-minute idle
  periods and optional hide lock. Five failures start 30 seconds, increasing up
  to five minutes. Cooldown is persisted. Where supported, Web Locks serialize
  verification/configuration changes; a verifier-change check also prevents an
  old verification from restoring obsolete settings. No claim of protection
  from a browser-profile owner, extension or developer tools. No IDB encryption.
- `apps/controls/index.js`: notification choices, explicit permission/test, PIN
  operations, persistent-storage request and password-authenticated revocation.
- `apps/backup/{index,engine,validate}.js`: encrypted local archive, bounded schema
  validation, file content/hash checks, preview and explicit destructive restore.

## Binary archive v1

All integer lengths below are unsigned big-endian. No ZIP decompression or base64
binary-file payload. Text uses fatal UTF-8 decoding on import.

```
8 bytes    FAYEZBK1
4 bytes    header JSON byte length (50..2048)
N bytes    header JSON
remaining  AES-GCM ciphertext including 128-bit authentication tag
```

Header fixes `version:1`, `kdf:PBKDF2-SHA256`, `iterations:310000`,
`cipher:AES-256-GCM`; salt (16 random bytes) and IV (12 random bytes) are base64.
Magic + length + header bytes are authenticated as AAD. A fresh salt/IV is generated
for every export; keys are nonextractable Web Crypto objects. Passphrases are
12–256 JS string code units and are not normalized, uploaded or persistently saved.
JavaScript cannot promise secure erasure of every temporary string/buffer.

Decrypted payload: four-byte metadata length, UTF-8 manifest (max 8 MiB), raw file
bytes (max 50 MiB). Manifest identifies format/version/schema, creation time,
source scope and **all stores** (19 at the time of Phase 6, 28 since Phase 8).
Blob descriptors carry ID, MIME, size, offset
and SHA-256. Stored binary bytes must match metadata, boundaries, hash, allowed
extension/signature and current file limits. No unknown store/preference/schema
is silently ignored. Nonempty reserved outbox is rejected, not dropped.

Maximum input archive 64 MiB; max 20,000 total rows, 10,000/store, 100 files,
10 MiB/file. These are supported limits, not a claim of unbounded export. A missing
blob, unsupported/damaged local record or oversized metadata stops export.

## Consistency, local scope and limitations

Export uses one readonly 19-store transaction and a checked localStorage snapshot;
newer effective draft/preference caches are included. PIN/session/API secrets are
outside the allowlisted archive. File bytes are immutable Blob snapshots. Server
snapshots/files are separate and excluded. Same public account ID or Guest scope
is required on import; a filename is not trusted as identity.

Preview takes a baseline. Restore refuses changed records/settings/drafts, closes
no other account and performs no server write. A lifetime shared Web Lock is held
by each integrated page; restore releases its own lease, requests an immediately
available exclusive lease and refuses another open page. New integrated clients
wait during restore. Browsers without Web Locks may export/check, not restore.
Legacy clients or direct IDB writers do not participate: close them before upgrade
and restore. The transaction's fingerprint includes full metadata and blob ID/size/
type; it is not protection against an uncoordinated tool rewriting same-sized blob
bytes without metadata. This is an application concurrency protocol, not a security
boundary against arbitrary same-origin code.

All stores are checked/replaced in one readwrite transaction. Versioned entities
advance to `max(restored,current)+1`; old editors/drafts may need explicit conflict
resolution. An internal `_cache-epoch` preference commits in that same transaction.
Before a new connection resolves, local DB initialization compares its durable
epoch with the cache epoch and clears stale scoped preference/draft caches. It
preserves `privacy-*` settings and leaves other workspaces untouched. This closes
the crash window between IDB commit and localStorage cleanup. When cache access is
blocked, IDB is authoritative. UI reloads after successful restore. Archive restores
force desktop notification delivery off; permission must not silently be re-enabled.

A browser shutdown can still lose unflushed edits or interrupt a download. A prepared
download is not proof of a saved file. Restore needs temporary disk/memory headroom
and has no automatic undo: export a rescue copy first. Persistent-storage permission
is optional and cannot stop deliberate site-data deletion. Local backups cannot
recover a server whose database/private files were not backed up.

## Notifications and PIN are limited by the platform

System text is generic unless preview is explicitly selected. Quiet hours use the
device timezone (account profile timezone remains metadata). A test action ignores
quiet hours. Display can be suppressed by OS/browser/site policies even after a
successful call. Existing Fayez notifications are closed best-effort at startup,
on privacy lock and by service-worker message on scope switches; OS delivery and
closure are not an instantaneous security guarantee. Explicit previews may reveal
private titles outside the app. Click messages are scope/route checked; a cold
click opens the notification center, not an unauthenticated record URL.

PIN hides route content before normal startup and stops mounted camera streams on
lock. It is deliberately recoverable by removing only that workspace's verifier
key: see PHASE_6_FA.md. Damaged config fails closed. Unavailable localStorage cannot
provide a durable PIN feature; Controls reports storage errors, not saved protection.

## New server route

`POST /api/auth/revoke.php`, JSON `{ "current_password": "..." }`. Requires session,
CSRF and `X-Fayez-Account`. Exact fields only; ten attempts per 15 minutes/account.
Verifies current password under a user-row lock, increments `session_version`,
commits, rotates current SID/CSRF and preserves the current session. Other sessions
are rejected at their next protected request. Response matches auth user/CSRF shape.
Typical failures: 401 auth, 403 CSRF, 409 workspace mismatch, 422 password/input,
429 throttling. Does not erase other devices' offline copies or provide email reset.
