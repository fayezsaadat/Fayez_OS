# Security boundaries — Phase 7

This is a tested development implementation, **not a security certification or
an internet-production approval**. See PHASE_5.md/API and the actual test report.

## Implemented

- Static frontend / modular JSON PHP separation; no embedded API keys/DB secrets.
- Native PDO prepared statements, utf8mb4, owner-scoped queries. Public IDs and
  file IDs are not authorization. Private GETs and mutations require a session;
  account guard prevents stale-tab actions under a different logged-in identity.
- password_hash/password_verify/rehash, validated 12–72-byte passwords, generic
  incorrect-login messages, SID/CSRF rotation, HttpOnly/SameSite=Lax cookies.
  Correct HTTPS/proxy configuration enables Secure. Frontend rejects public HTTP
  account requests; backend production account operations require TLS configuration.
- CSRF token validation on login/register and all mutations. No GET mutations.
  Exact-origin credentialed CORS; no wildcard. CORS is not authorization/CSRF.
- 30-minute idle and 24-hour absolute expiry; database session epoch on each
  protected request. Password change requires the current password and invalidates
  other epochs. Sessions bind to installation and database, not just numeric ID.
- Protected locked limiter buckets for registration/login/password attempts,
  Retry-After on 429, bounded retention/count. Not a distributed rate limiter.
- Private file ownership on list/download/rename/delete; auth+CSRF on upload,
  content/type/size/count limits, random keys, private file permissions, guarded
  attachment output, no inline execution. No arbitrary paths or URL fetches.
- Whole-workspace revision CAS under user-row locking, strict bounded six-store
  schema, exact money strings, local transactional fingerprint recheck on pull.
- Private JSON and attachments are no-store. SW caches only public asset URLs,
  never APIs or account payloads. Local data remains outside the SW cache.
- Apache denies private directories; nosniff/referrer/CSP headers. Scripts stay
  local, no unsafe-eval. Images/media allow bounded local data/Blob use; no objects.
  Production Apache CSP includes frame-ancestors self. The development CLI router
  deliberately permits Arena preview embedding; do not use it for public hosting.
- Existing provider fixed HTTPS hosts, TLS checks/no redirects, body/time/cache
  bounds, XML NONET/DTD rejection and safe publisher links remain in place.
- Camera/location user opt-in; no microphone. QR frames/images processed locally.

## Local privacy is not server authorization

Guest data is unowned data in this browser profile. Account data is partitioned
into separate IndexedDB databases and preference/draft/cache namespaces. Switching
identity reloads tabs; delayed callbacks retain their original namespace.

These databases are **not encrypted**. Offline cached account copies remain usable
without live session verification. Logout invalidates the current server session
and returns to Guest, but deliberately retains local account copies for later
sign-in. An expired session similarly does not destroy unsynced edits. A person
who controls the browser profile/devtools can read local databases. Browser
partitioning is not an offline PIN lock or secure erase. Guest import preserves
original guest records; those originals do not acquire account protection.

QR passwords/history, drafts/preferences and provider caches are excluded from
workspace upload. Contact photos are included only in an explicitly confirmed
six-store upload. Cloud file upload is separate from local Files.

## Required production work / known limits

1. Deploy HTTPS-only behind a configured trusted proxy; set APP_HTTPS correctly,
   private PHP session save path, display_errors=Off and suitable security headers.
   Check actual response headers and directory denial on Apache/Nginx/IIS. Never
   trust arbitrary forwarded headers to enable secure mode or bypass IP limits.
2. Use least-privilege DB credentials, protect backups/logs/private directories,
   keep uploads outside document root, monitor disk/quota and operator alerts.
   Add deployment rate limits, maintenance and abuse controls for public signup.
3. Review private-file crash recovery. SQL and filesystem are not one atomic
   transaction; reconcile orphan/missing bytes. Back up database and bytes together.
   Valid signatures/containers are not malware scanning or safe-opening guarantees.
4. Implement/review email verification, forgotten-password recovery, account
   erasure/retention, audit policy, and appropriate at-rest protection before an
   unrestricted account service. None is implied by the current registration UI.
5. Single-server PHP sessions/limiters need a redesigned shared store and load tests
   for multiple hosts. Reverse proxies can aggregate REMOTE_ADDR limits; configure
   trusted address handling at the webserver, never arbitrary client headers.
6. Same-origin browser/PWA auth is tested. Cross-site WebView cookies, native secure
   storage and session transport require a separate design and real-device tests.
   Do not simply remove SameSite/CSRF protections to make an embedded preview work.
7. No automatic merge, multi-version backup, undo-cloud-replace or durable job queue.
   Whole-copy confirmations are consequential; choose which copy to keep carefully.
   A lost HTTP response may follow a commit. Inspect state before retrying uploads.
8. Patch dependencies/runtime, review provider/content licenses, test accessibility,
   and obtain an independent deployment/security review. No penetration/load or
   physical-device certification is claimed.


## Phase 6 additions and remaining boundaries

The scoped PIN is a UI privacy curtain, not live-data encryption, trusted identity,
secure erase or protection from extensions/developer tools. Salted PBKDF2 verification
and persisted cooldown reduce casual UI guessing; users controlling the profile can
remove the verifier. This is documented recovery, not a cryptographic escape hatch.
PIN is excluded from archives. A forgotten backup passphrase cannot be recovered.

Only local backup archives use AES-256-GCM with authenticated bounded headers and
PBKDF2-SHA256 (310,000 rounds, fresh 16-byte salt/12-byte IV). Full-store validation,
file signatures/SHA-256, same-scope restriction, exclusive integrated-client lease,
baseline comparison and one IDB transaction prevent accidental partial replacement.
A durable cache epoch prevents stale preference/draft resurrection after commit.
Web Locks do not police legacy/direct-IDB clients; close them first. There is no
claim of an independent cryptographic audit, guaranteed memory erasure or unlimited
archive size. See `PHASE_6.md` for exact bounds and the file protocol.

Notification titles are generic by default. Explicit title preview can reveal private
content on the OS lock screen; closure is best effort. No closed-app scheduler/push
service exists. Search excludes QR/password histories and drafts and sends no query.
The new password-confirmed revoke endpoint rotates current SID/CSRF and advances the
server session epoch, but cannot remove another device's offline data. Browser cache,
local storage and IndexedDB are never an authorization boundary for server endpoints.
Full server recovery must cover SQL and protected file bytes, not just source/local
archives. Follow `BACKUP_OPERATIONS.md` and keep all backups outside the document root.


## Phase 7 Android/local media boundary

APK API clients are disabled before fetch; native Guest scope, HTTPS asset origin,
cleartext prohibition and restrictive local CSP are injected only in staged assets.
Cross-origin cookie/CSRF/authentication is not weakened to enable a fake cloud demo.
The SAF bridge is size/token/offset bound and writes only the user's chosen content
URI; no arbitrary-path read/write or shell/HTTP bridge exists. Temporary plaintext
exports (except already encrypted archives) can exist in app-private cache until
cleanup and are not a secure-erasure guarantee. Native stream/provider interruption
may leave partial destination files. User confirmation and independent verification
remain essential. ACTION_DIAL cannot place a call itself; no CALL_PHONE/contacts/
microphone/broad storage permission is requested.

FLAG_SECURE and platform-backup exclusions supplement, not replace, device security
or local archive encryption. Browser PIN remains a bypassable screen curtain.
Physical OEM/WebView/permission behavior has not been certified. Debug-signed APK
is not a release identity; changing signing keys can require uninstall and data loss.
Release tasks require private signing environment variables; no release key is
provided. Native/scripts deployment internals are denied over HTTP. Review third-party
licenses and current provider/store requirements before public distribution.
