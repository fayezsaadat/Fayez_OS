# Phase 5 JSON / private binary API

Base path is configurable in `app/assets/js/config.js`. Default `/api/` relative
to the installation. PHP 8+, PDO MySQL, fileinfo and native sessions. Credentials
are server configuration; the ZIP intentionally has no configured DB password.

All JSON envelopes: `{success,message,data,code?}`. No-store/private, JSON UTF-8,
nosniff. Success examples omit sensitive real identities. Cookie name:
`fayez_os_session`; do not expose it to JavaScript or store it in localStorage.

## Session and CSRF

First GET `auth/session.php` returns `{enabled,user:null|{id,name,email,timezone},csrf}`.
`enabled` means configured, not a MySQL reachability probe. The public ID is a random
32-hex identifier, not the numeric SQL primary key. Anonymous users can obtain their
own CSRF token, not another session's. Login/register rotate both token and SID.

Send `credentials: include`. All mutation routes require `X-CSRF-Token`. Protected
profile/password/workspace/file routes also require `X-Fayez-Account` matching the
session's current public ID, including on GETs. Logout checks the identity if a user
is signed in. A mismatched account guard is 409; absent session is 401. Neither
client IDs nor CORS headers authorize an operation without a valid PHP session.

| Route | Method | Body / response |
|---|---|---|
| `auth/session.php` | GET | Session status + CSRF; no password/hash returned |
| `auth/register.php` | POST | `{name,email,password}` → signed-in user + rotated CSRF |
| `auth/login.php` | POST | `{email,password}` → user + rotated CSRF |
| `auth/logout.php` | POST | `{}` → anonymous rotated session/CSRF |
| `auth/profile.php` | PATCH | `{name,timezone}` → updated public user |
| `auth/password.php` | POST | `{current_password,password}` → rotated current session; other epochs invalidated |
| `sync/workspace.php` | GET | `{revision,updatedAt,data}`; absent copy has revision 0/data null |
| `sync/workspace.php` | PUT | `{revision,data}` → committed revision/time; whole six-store snapshot |
| `files/index.php` | GET | `{files:[{id,name,size,mime,version,updatedAt}],used,limit}` |
| `files/index.php` | POST | multipart, exactly one `file`, no text fields → private file metadata (201) |
| `files/index.php` | PATCH | `{id,version,name}` → metadata with incremented version |
| `files/index.php` | DELETE | `{id,version}` → deletion confirmation |
| `files/download.php?id=...` | GET | Authenticated attachment bytes, not JSON on success |

No endpoint accepts an owner/user ID to select another person's workspace. Private
file IDs are strings in JSON. Unknown/other-owner IDs are indistinguishable 404s.
No arbitrary server path or download URL is accepted. No GET mutation or public
file-listing route exists. Wrong methods receive 405 + Allow.

## Limits and errors

- JSON auth/profile requests: 64 KiB; strings validated, no unknown fields.
- Display name: 1–100 UTF-8 bytes; email: max 254 bytes, normalized trim/lowercase.
  Password: 12–72 UTF-8 bytes for registration/change; never silently truncated.
- Workspace: 4 MiB request, exactly six named arrays, max 1,000 records per array.
  IDs/versions/timestamps, lists, exact money, dates, categories and photo data
  types checked; unexpected fields, duplicate IDs and malformed records rejected.
- Auth limiter: registration 5/IP/hour; login 40/IP/15 minutes and 10/email/15
  minutes; password-change attempts 10/user/15 minutes. Attempts include successful
  operations. REMOTE_ADDR, not untrusted forwarded headers, supplies the IP.
  Behind a proxy this may group users: configure trusted proxy address handling
  at the webserver and deployment rate limits. 429 includes Retry-After.
- Private files: 10 MiB/file, 50 MiB and 100/account, bounded file format checks.
  PHP `upload_max_filesize`/`post_max_size` and proxy limits may reject earlier.
- 400 malformed request/unknown fields; 401 unauthenticated/invalid credentials;
  403 CSRF; 404 missing/not owned; 409 version/account conflict or existing account;
  413 size/quota; 415 JSON content type; 422 validation; 429 throttling;
  503 missing setup/storage/HTTPS; generic 500 unexpected server failure.

A non-2xx response never counts as confirmed success. A lost/aborted response can
occur after a successful commit: inspect current revision/files before retrying.
Workspace writes are CAS, not idempotent retry IDs; file uploads may create duplicates.

## Cookie/CORS/hosting

SameSite=Lax is retained. The tested integration is **same-origin web/PWA**. Cross-
site WebView/native auth is not solved merely by adding a CORS origin. Do not weaken
cookie/CSRF policy to make a prototype work; design and test an appropriate native
session transport for Phase 7. For same-site CORS deployments, allow only the exact
frontend origin and its CSP connect-src; no wildcard credentials.

Production requires HTTPS. `APP_HTTPS=1` is an explicit administrator assertion for
a trusted TLS-terminating proxy; arbitrary forwarded headers are not trusted. It
sets Secure cookies and must never be enabled as a workaround for public HTTP.
Local HTTP testing uses `APP_ENV=development`; frontend account requests allow HTTP
only for localhost/loopback. Enforce HTTP→HTTPS at the production webserver.

PHP native session storage and the limiter directory must be private and writable.
Sessions have installation/database binding, idle and absolute expiry, and verify
the user's session_version on each protected request. This release is single-server;
a multi-worker FPM deployment shares storage, but multiple hosts require a shared
session/limiter design and a separate concurrency/load/security review.
