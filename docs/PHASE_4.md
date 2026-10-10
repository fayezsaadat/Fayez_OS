> Historical Phase 4 scope. Current release: Phase 5 / 0.5.0. See PHASE_5_FA.md, docs/PHASE_5.md and INSTALLATION.md for current setup and privacy boundaries.

# Phase 4 — a wider perspective

**Version 0.4.0.** Weather, News, QR and Files now mount inside the same vanilla
JavaScript shell. Previous apps are preserved. No account/authentication, private
server upload or cloud synchronization is implied.

## Weather

- Kabul, Herat, Mazar-i-Sharif and Kandahar presets; explicit worldwide city
  search via Open-Meteo/GeoNames. Selecting a place alone does not fetch weather.
  The home screen updates the selected city on load/foreground return when no saved
  forecast exists or the saved response is at least 15 minutes old. Opening Weather
  does the same; a manual Refresh is always available.
- Optional device location requests permission only on **Use my location**.
  Coordinates are rounded to two decimal places (roughly kilometre scale, varying
  with latitude). **Refresh forecast** sends those coordinates to this PHP server
  and Open-Meteo. City names are sent when Search is pressed.
- Modelled current temperature/feels-like/humidity/wind, 24 hourly entries and
  seven daily forecasts. Celsius/Fahrenheit changes locally. Times use the
  forecast location's timezone, not an assumed device timezone.
- Attribution, fetch time and the model condition timestamp are visible. Conditions
  older than two hours are marked. No sample weather or observation is invented.
- Up to ten location caches support offline reference. Changing location cannot
  display another location's quote after a late response. A refresh failure keeps
  the existing dated cache and shows an error; it does not claim new data.
- Home names the selected city and fetches that fixed city forecast when empty or
  stale, displaying °C, date and an older-forecast indicator. It refreshes only
  while the OS is foregrounded, not periodically in the background. Device
  location is never requested automatically.
- This is forecast/model data, not a physical station reading or emergency alert.

## News

Two fixed publisher feeds: **BBC World** and **BBC Persian**. Refresh explicitly
requests RSS via PHP; the app never invents headlines. This is a personal reader,
not a claim of independent verification or comprehensive Afghanistan coverage.

- Publisher title, description/summary, date when supplied, attribution and source
  URL. Missing publication dates say so; old feed fetches are labeled.
- Search downloaded stories, 12-item incremental display, Saved and History tabs.
- Opening a summary records local history (latest 100 unique stories). That does
  not prove the full article was read. Bookmarks hold up to 100 summaries.
- The detail view clearly says **publisher summary only**. **Read at BBC** is a
  normal explicit external link with noopener/noreferrer. Full articles need the
  publisher site and internet; paywalls/access restrictions are not bypassed.
- RSS images, scripts, embedded media and remote tracking resources are not
  rendered. XML DTD/entities are rejected; text is escaped; source links are
  restricted to HTTPS BBC hosts. Query tracking parameters are removed.
- Cached feed snapshots, saved summaries and reading history work offline. A new
  fetch or opening a full publisher article does not.

## QR Code

Two small, locally bundled algorithm libraries support standards-based generation
and decoding without a CDN, external service or runtime build step. See
`THIRD_PARTY_NOTICES.md` for versions, licenses and wrapper changes.

### Create

Text, complete HTTP(S) web link, or Wi-Fi network. Content limit: **1,000 UTF-8
bytes**, not 1,000 Persian characters. Wi-Fi fields escape delimiters; no password
is required for an open network. Input is not automatically persisted.

Generation uses UTF-8 byte mode, medium error correction, black modules, white
background and a four-module quiet border. Download PNG or a self-generated SVG.
Changing inputs invalidates the old generated result to prevent mismatched
content/downloads. General QR text can contain arbitrary strings, but only
validated HTTP(S) results receive an optional web link.

### Scan

- PNG/JPEG/WebP image up to **5 MB**; MIME/signature check, browser decode, maximum
  decoded dimensions 4096 × 4096, bounded working canvas. No uploaded image.
- Camera on HTTPS/localhost if supported and permitted. Start is explicit;
  microphone is never requested. Stop works while permission is pending.
- Camera frames are decoded locally at a bounded working size. Stream tracks stop
  after success, Stop, mode switch, hidden page or leaving the app. A late permission
  result after navigation is immediately stopped.
- Denied/unsupported/iframe-blocked camera access has a clear image-upload fallback.
- Decoded content is displayed as untrusted text. No automatic web navigation,
  Wi-Fi connection, phone call or other action. Review the full text/domain before
  using **Open reviewed link**. Copy falls back to selecting text if the clipboard
  API is blocked.
- History is **opt-in**, latest 50 saved entries. Do not save passwords/sensitive
  codes in this unencrypted browser history. Clear history requires confirmation.

A synthetic camera stream was tested; actual phone-camera focusing/permissions and
native integration remain device-test gates. QR recognition is not guaranteed for
every damaged, cropped, dense or low-contrast image.

## Files — deliberately local

The user delegated the storage choice. Until Phase 5 provides authenticated
ownership, **no private file is uploaded to PHP or MySQL**. Selecting a file creates
a browser-local copy; it does not modify the original device file.

- Multiple-file import, filename search, automatic type categories, newest/name/
  size sorting, details, rename preserving extension, download and confirmed delete.
- Limits: **10 MiB per file, 100 files, 50 MiB total** (UI abbreviates MB). Browser
  quota may be smaller; errors are shown instead of claiming a successful save.
- Metadata and Blob are saved/deleted in the same IndexedDB transaction. Aggregate
  count/size is checked inside that transaction, including concurrent imports.
- Rename/delete checks the saved version to reject stale edits from another tab.
- Allowed: PNG/JPG/WebP, UTF-8 TXT, PDF, MP3/WAV, MP4, DOCX/XLSX/PPTX and ZIP.
  MIME is assigned from the allowed extension; binary signatures are checked.
  HTML, SVG, scripts, executables, empty and oversized files are rejected.
- Image preview is bounded to 4096 × 4096. Text is escaped and previewed up to
  100 KB. Audio/video uses browser controls without autoplay. Unsupported codecs
  show a download fallback. Object URLs are released when previews close.
- PDF/Office/ZIP are **download only**: no inline active document, decompression,
  macro execution or claimed malware scan. A ZIP signature does not certify its
  contents. Only open untrusted downloads in appropriate trusted applications.
- Delete physically removes metadata and Blob from this database. It is not a
  forensic secure-erase guarantee. Important files need independent downloaded
  copies; local browser storage is not a backup.

## Persistence and lifecycle

IndexedDB `fayez-os` upgrades to **v4, 19 stores** without deleting v1/v2/v3 stores.
New stores: `weatherCache`, `newsCache`, `savedArticles`, `newsHistory`, `qrHistory`,
`files`, `fileBlobs`. No SQL migration is required for these local apps.

Personal data remains origin-scoped, unencrypted and not separated by account.
Clearing site data can remove files, photos, QR contents, bookmarks and all earlier
personal records. Full backup/restore and consent-based cloud import are later
phases. Do not clear site data to update the service worker.

The worker caches code/assets (including both QR libraries), never API responses
or personal file contents. Cached forecasts/feeds are separately validated and
stored through the app repositories. All four modules load offline after an
initial successful secure-context shell load. Final release cache: **v4.0.2**.

## Files of interest

- `app/apps/weather/{index,model}.js`
- `app/apps/news/{index,model}.js`
- `app/apps/qr/{index,engine}.js`
- `app/apps/files/{index,repository}.js`
- `app/apps/shared/phase4.js`, `app/assets/css/phase4.css`
- `app/assets/vendor/` — two local algorithms and license notices
- `api/weather/{forecast,locations}.php`, `api/news/feed.php`
- `includes/{public-provider,weather-provider,news-provider}.php`

See `PHASE_4_PROVIDERS.md` for server settings, external data terms and limits.
