# Installation & file placement — Phase 7 / 0.7.0

## 1. Requirements

- XAMPP with Apache 2.4 and **PHP 8.0 or newer**.
- MySQL 8+ or XAMPP's MariaDB 10.4+ for accounts, workspace sync and private cloud files (optional for guest-only tools).
- A current browser with ES modules. PWA caching requires HTTPS or `localhost`.
- PHP **`pdo_mysql` and `fileinfo`** are required for account/cloud functionality. The local apps need only core PHP for health; the optional currency
  adapter additionally requires **cURL**, working CA certificates and a writable
  `storage/cache/`. Phase 4 weather/news require **cURL**, and RSS parsing also
  needs **DOM/XML**. See `docs/CURRENCY_PROVIDER.md` and `docs/PHASE_4_PROVIDERS.md`.

## 2. Put the project in XAMPP

Extract the ZIP. Keep **all relative paths intact**:

```text
C:\xampp\htdocs\fayez-os\index.php
C:\xampp\htdocs\fayez-os\app\index.html
C:\xampp\htdocs\fayez-os\app\assets\css\...
C:\xampp\htdocs\fayez-os\app\assets\js\...
C:\xampp\htdocs\fayez-os\api\health.php
C:\xampp\htdocs\fayez-os\config\...
C:\xampp\htdocs\fayez-os\database\database.sql
```

On Linux XAMPP, use `/opt/lampp/htdocs/fayez-os/`; on macOS XAMPP use the
installation's `htdocs` directory. Do not copy only `index.php`. Do not move CSS
or JavaScript beside the PHP entry point. Hidden `.htaccess` files must be kept.
The complete tree and per-file explanation are in `docs/FOLDER_STRUCTURE.md`.

## 3. Run

1. Open the XAMPP control panel and start **Apache**.
2. Visit `http://localhost/fayez-os/` (include your Apache port if not 80).
3. The entry redirects to `/fayez-os/app/`; frontend routes appear after `#`.
4. Check `http://localhost/fayez-os/api/health.php`. Expected JSON:

```json
{
  "success": true,
  "message": "Fayez OS API is available",
  "data": {
    "version": "0.7.0",
    "phase": 5,
    "capabilities": ["os_shell", "local_preferences", "offline_shell", "local_calculator", "local_notes", "local_tasks", "local_calendar", "local_expenses", "local_currency", "local_contacts", "weather_forecasts", "publisher_news", "local_qr", "local_files", "accounts", "manual_workspace_sync", "private_files"],
    "currency_provider_configured": false,
    "weather_provider_configured": true,
    "news_provider_configured": true,
    "file_storage": "local_and_authenticated_server",
    "database_required": false,
    "database_required_for": ["accounts", "workspace_sync", "private_files"],
    "database_status": "not_probed",
    "authentication_available": false,
    "cloud_sync_available": false
  }
}
```

5. Open Settings → PHP API connection → **Check**. It displays loading, success,
   timeout, offline and server error states rather than silently failing.
6. Change a theme, wallpaper and accent, reload, and confirm the choices remain.

## 4. Database setup — required for Phase 5 accounts

Guest tools still work without MySQL. Accounts, manual workspace transfer and
private server files are now implemented and require this setup. `health.php`
reports configuration, not database reachability. The application server needs
only SELECT/INSERT/UPDATE/DELETE; installation/migration requires a separate
privileged database user.

1. Start **MySQL** in XAMPP (typically MariaDB under the MySQL label).
2. Visit `http://localhost/phpmyadmin/`.
3. Choose **Import**, select `fayez-os/database/database.sql`, and execute.
   It creates the `fayez_os` database and 18 empty tables.
4. Create a dedicated least-privilege application user. Do not use
   the XAMPP root account for production. Example, replacing the password:

```sql
CREATE USER 'fayez_app'@'localhost' IDENTIFIED BY 'REPLACE_WITH_A_LONG_RANDOM_PASSWORD';
GRANT SELECT, INSERT, UPDATE, DELETE ON fayez_os.* TO 'fayez_app'@'localhost';
```

5. Configure server environment variables. For local Apache, use your local
   VirtualHost configuration (or a local-only `.htaccess` with `mod_env`). Never
   put actual secrets into JavaScript or commit them:

```apache
SetEnv APP_ENV development
SetEnv DB_HOST localhost
SetEnv DB_PORT 3306
SetEnv DB_NAME fayez_os
SetEnv DB_USER fayez_app
SetEnv DB_PASSWORD "REPLACE_WITH_YOUR_LOCAL_PASSWORD"
```

6. For an **existing Phase 1–4 SQL installation**, first make an independent SQL
   backup and run `php database/migrate-phase5.php` with privileged **CLI environment**
   credentials for that database. Apache SetEnv values do not automatically reach
   your terminal. Reimporting CREATE TABLE alone does not add existing columns.
   The repeatable migration preserves rows and adds the public ID, session epoch
   and file version columns. Restore the least-privilege runtime credentials.
7. Restart Apache after changing server configuration. The PDO factory lives in
   `config/database.php`. It uses real prepared statements and UTF-8. It fails
   safely when credentials are missing; the health endpoint intentionally
   **does not report database health**.

## 5. Environment switching

The **one frontend config** is `app/assets/js/config.js`, not scattered URLs.

### XAMPP development

```js
MODE: 'development',
PLATFORM: 'web',
API_BASE_URL: new URL('api/', deploymentRoot).href,
```

The module automatically resolves `/fayez-os/api/` whether Apache uses port 80 or
another port. Debug logs show failed request messages, never database details.

### Production web

Upload the same project at the domain root or a subdirectory, retaining `/app`
and `/api`. Use HTTPS, set frontend `MODE: 'production'`, and server
`APP_ENV=production`. Behind a trusted TLS proxy explicitly set `APP_HTTPS=1`
so PHP emits Secure cookies; on direct Apache TLS PHP detects HTTPS itself. Never
trust arbitrary X-Forwarded-Proto headers. Redirect HTTP to HTTPS at the webserver.
The default computed API base still works. No HTML rewrite
rules are needed. The root PHP file redirects to the separate static app.

Ensure Apache permits `AllowOverride All` for this directory and has `mod_headers`
enabled. The included `.htaccess` files deny private directories and send security
headers. **Verify** `/config/app.php`, `/database/database.sql`, `/storage/` and `/uploads/`
return 403 in your deployed server. Nginx/IIS require equivalent rules; these files
are Apache-specific. Prefer deploying configuration and uploads outside the public
root during production hardening.

For a distinct remote API, change `API_BASE_URL` to its HTTPS URL with a trailing
slash and add that exact origin to `app/.htaccess` `connect-src`. The backend uses
an exact CORS allowlist via `CORS_ALLOWED_ORIGINS`, comma-separated, no wildcard.
Account cookies use SameSite=Lax. A truly cross-site frontend/native app needs a
reviewed cookie/session transport, not just CORS; only same-origin browser/PWA
authentication has been tested in this release.

### Future Android package

Use `PLATFORM: 'android'`, `MODE: 'production'`, and an **absolute production HTTPS
API URL**. No `localhost` URL is allowed in final builds. Do not package PHP or
secrets into the Android project. See `ANDROID_BUILD.md`.

## 6. PWA/offline test

1. Open the app on `http://localhost/.../app/` or HTTPS; wait for the first load.
2. In DevTools → Application, check the manifest and active service worker.
3. Reload once, then use Network → Offline and reload `/app/` or `/app/index.html`.
4. Home, Settings and local tools work offline. Account cloud actions require
   internet; a previously selected account can use its cached local copy offline.
   Create a note, transaction or contact offline, then reload to verify persistence.
5. Settings API check, new forecasts/news and new provider rates require internet. Manual
   conversions and explicitly dated saved quotes work offline; no new market
   rate is claimed while disconnected.
6. Use the supported browser's Install option. If no install prompt is available,
   the app's Install button explains the browser-menu route. Browser/device
   policies may prevent installation; an automatic prompt is not guaranteed.

The worker's scope is `/app/`. The root PHP redirect is server-dependent when
navigating directly to `/fayez-os/` offline. Installed apps start at cached
`/app/index.html#home`. IndexedDB stores and cache storage are origin-scoped;
clearing browser data removes preferences and all personal records, drafts,
photos and histories. A source-folder backup does not back up browser records.

## 7. Test on a physical phone over LAN

- Put computer and phone on the same trusted Wi-Fi.
- Allow Apache through the local firewall for the private network only.
- On the phone, open `http://YOUR_COMPUTER_LAN_IP/fayez-os/`.
- The shell works, but **LAN HTTP is not a secure context**. PWA installation,
  service workers and camera APIs may be unavailable. Account requests over
  non-loopback HTTP are refused by the client. Use a trusted HTTPS
  development host/certificate or an HTTPS deployment for those tests.
- Never expose phpMyAdmin or the XAMPP control panel to the public internet.

## 8. Troubleshooting

| Symptom | Fix |
|---|---|
| PHP downloads instead of running | Use Apache URL, not a file viewer; verify PHP handler |
| Source files fail under `file://` | Serve the app; ES modules need HTTP(S) |
| API response is HTML | Check PHP and path; API client reports invalid response |
| Old CSS/JS after editing | Bump service-worker cache version, close tabs, update/unregister |
| DB access denied | Verify dedicated credentials and hostname grants; shell needs no DB |
| API check fails in Android | Check HTTPS URL, CORS, reachability and platform config |
| Battery shows an unknown symbol | Browser/WebView does not expose the Battery API |
| Wi-Fi icon but API fails | `navigator.onLine` indicates a network, not internet/API health |
| Browser storage blocked | Session still works; preferences may be memory-only; personal-data apps require working IndexedDB |
| Home time differs from Kabul | Time uses device timezone by design; no location is assumed |
| Apache 500 with `.htaccess` | Check Apache error log, AllowOverride, headers module |

No XAMPP desktop installation, physical device test, Google Play submission, or
signed APK is implied by this Phase 6 delivery. See the actual test report.

## 9. Upgrade from Phase 1, 2, 3 or 4 without clearing local data

1. Back up your project source folder. Do not clear the browser database.
2. Replace project files with this full Phase 5 delivery, retaining any deliberately
   configured server environment values and the same frontend origin/URL.
3. Close all old Fayez OS browser/PWA windows. Reopen online so the new worker can
   activate; an old open client keeps its old cached asset version until closed.
4. IndexedDB automatically upgrades v1/v2/v3 to **v4**, preserving prior stores.
   Seven new stores hold weather/news caches, bookmarks/history, QR history and
   local file metadata/content. There are 28 stores total (Phase 8 added nine).
5. Weather, News, QR Code and Files now show **Available**, alongside prior apps.
   Historical Phase 5 cache: v5.0.1; current Phase 6 cache: v6.0.1. Account adds separate IDB v4 namespaces; guest
   data is neither reassigned nor uploaded automatically. Preserve runtime SQL and
   private-file directories when replacing code; never delete them as stale assets.
6. If an old version remains, use DevTools → Application → Service Workers →
   Update or Unregister, then reload online. Do **not** use “Clear site data”;
   that deletes personal records and is not needed to update the app.

Close old tabs if an upgrade-blocked storage message appears. If running on a
different host or port, remember browser data is origin-scoped, not automatically
shared. This phase has filtered expense CSV export, explicit initial guest copying and
manual six-collection cloud transfer. The Phase 5 cloud snapshot does not have full backup/version history
or automatic merge; drafts/settings/histories/local files are excluded from sync.

## 10. Optional provider

Default-disabled is intentional. To fetch actual daily exchange rates, enable
PHP cURL, set `CURRENCY_PROVIDER=erapi`, grant the PHP account write access to
`storage/cache/`, restart Apache and use Currency → Provider rate → Fetch.
Keep the attribution and review provider terms. Follow `docs/CURRENCY_PROVIDER.md`
for TLS, permissions, errors and production limits. Never disable TLS validation.

## 11. Phase 4 provider and browser setup

Weather (`openmeteo`) and News (`bbc`) default enabled for this personal-use app;
requests occur only on Search/Refresh. Enable PHP cURL and DOM/XML, configure CA
certificates and cache permissions, then restart Apache. Set `WEATHER_PROVIDER`
or `NEWS_PROVIDER` to `disabled` to opt out. Check terms before public/commercial
use; a paid/licensed provider arrangement may be required. See
`docs/PHASE_4_PROVIDERS.md` for fixed routes, cache limits and attribution.

`app/.htaccess` permits same-origin camera/geolocation, no microphone, and local
Blob media while keeping executable scripts local and embedded objects disabled.
Camera requires HTTPS/localhost and permission; an iframe may still block it.
Choose an image as the QR fallback. File selection and QR images are local, not
PHP uploads in the **local Files app**. Account → Cloud files is a separate
authenticated upload flow; its metadata is in MySQL and bytes in private storage. Do not use unencrypted browser storage as the
only copy of important information.

## 12. Private account file storage and production gates

Set PHP `upload_max_filesize=10M`, `post_max_size=12M`, `display_errors=Off` and an
appropriate memory/request timeout. PHP needs a private writable native session
save directory. It also creates `storage/private/auth-limits/`; avoid world-writable
permissions. `AUTH_LIMIT_DIR` can override the limiter directory for isolated tests
or a private deployment path. Limiters/sessions are single-server, not distributed.

Default private bytes: `storage/private/files/`. For production set, for example,
`PRIVATE_FILES_DIR=/srv/fayez-private/files` **outside document root**; restrict it to
the PHP service account. The API never uses a client filename as a storage path.
Keep `storage/`, `uploads/`, `config/`, `includes/`, `database/`, `tests/` and `docs/`
blocked from HTTP. Direct access must fail on your actual webserver, not just the
CLI router. Never enable PHP/script execution in any upload location.

Back up SQL and private bytes together with an operator-managed secure procedure.
Check free disk space, quotas, stale/orphaned blobs after crashes, log retention,
TLS, deployment-level rate limits, and provider licenses before public use. No
runtime database dump, account credentials, limiter buckets, private uploads or
native keystore is included in the release ZIP.

For PHP CLI development, configure credentials in the terminal and run:

```sh
APP_ENV=development php -d upload_max_filesize=10M -d post_max_size=12M \
  -S 0.0.0.0:8080 -t . dev-router.php
```

Windows PowerShell uses `$env:APP_ENV='development'` and analogous DB variables,
then the PHP command without the Unix prefix. XAMPP web requests use the Apache
environment above. Never run the PHP development server as public production hosting.


## Phase 5 → 6 upgrade (no new SQL migration)

Preserve user data and host secrets; close ALL old app tabs first, including installed
PWA windows. Replace the complete source release, keep private uploads in their
configured protected location, then load once online and reload to activate the new
service worker. Do not clear browser data. IndexedDB remains v4 with 19 stores;
Phase 6 adds an internal cache epoch preference on full restore, not a new store.
Earlier SQL installations still need the existing Phase 5 migration described above.

New Find, Controls and Backup tools are in the drawer; Settings links to Controls
and Backup, and the home search shortcut opens Find. Encrypted archive operations
and PIN hashing require Web Crypto on HTTPS or localhost. Safe restore additionally
requires Web Locks. Files are bounded: 10 MiB/file, 50 MiB/100 local files, metadata
8 MiB, archive 64 MiB, 20,000 total records. Allow temporary memory/disk headroom.

A local archive excludes server-only data, PIN and credentials. Keep full SQL/private
filesystem backups separately: see `docs/BACKUP_OPERATIONS.md`. No automatic backup
scheduler or email password recovery has been introduced. OS notification permission
and persistent storage can be denied by the browser; use a standalone tab rather
than an embedded preview when testing permissions/cookies.


## Phase 6 → 7 upgrade

No SQL migration and no IDB schema upgrade (still 19 stores / v4). Close old tabs,
replace source, load online and close/reopen all PWA windows to activate v7.0.2.
Do not clear site data. Music/Videos share existing local files; no copies are
silently uploaded or migrated. Phone hands off only after a user action.

For normal XAMPP use, ignore `native/` and `scripts/` and install no Node packages.
These paths have deny rules and are not public endpoints. The optional Android
project/debug APK is separate: Android 8+ and current WebView 120+, local-only until
native production authentication/HTTPS is reviewed. See ANDROID_BUILD.md for actual
build/signing and device-test limits, not an assertion of release readiness.
