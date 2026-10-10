# Test report — Fayez OS 0.8.0 (Phase 7 + Phase 8 pack)

The Phase 8 section is the newest work; every earlier section is a real run from its
own date and is kept as history, not re-dated.

Phase 7 executed **28 September 2026**; the large-media fix and the Phase 8 pack on
**2 October 2026**. Chromium **153.0.8010.12**, PHP **8.4.26**,
MariaDB **11.8.6**; optional Python Playwright/requests QA. Android build: Node 22,
JDK 21, Capacitor 8.5.2, Gradle 8.14.3 / AGP 8.13.0, Android SDK 36.
Historical Phase 6/5/4 reports are preserved separately, not presented as new runs.

## Music, Videos, Phone, performance and offline — PASS

`tests/phase7_browser.py` uses isolated real browser storage and **synthetic** WAV/
MP4 fixtures (not release seed data):

- Import/save/play actual 20-second WAV; currentTime advances, correct duration;
  playback rate, loop and next selection; shared Files rows, search and src removal/
  pause on destroy. No duplicate media database.
- Actual H.264 MP4 decode (160 px video width); valid-looking but undecodable media
  produces a codec error; wrong extension rejected rather than pretending to play.
- Persian contact/name/digit normalization, safe text, clipboard bytes, keypad,
  deletion/clear and service-code rejection. tel: handoff was explicitly intercepted
  in the test: no real phone call or physical dialer was used.
- Synthetic 2,000-note index: 2,000 matches / 200 capped results; 40 initial DOM rows.
  One measured run: approximately **25.6 ms index / 1.1 ms query**. This is a sandbox
  fixture measurement, not a phone benchmark or an asserted before/after percentage.
- No QR vendor script was needed by the home page before ready; public SW caching
  of vendor files afterward remains intentional for offline use.
- Skip-link keyboard focus preserves the hash router; reduced-motion context.
- **54** combinations of three new apps × two themes × widths 320, 360, 375, 390,
  412, 430, 768, 1024, 1440: no document/app horizontal overflow.
- Explicit update-status action; cold offline navigation into all three apps and
  real offline audio playback. Zero uncaught browser exceptions. Final SW v8.0.0 (Phase 8 adds the new app modules).

Fixtures can be created with Python wave and a test-only ffmpeg binary; neither
ffmpeg nor synthetic media is a normal app dependency or included in source releases.

## Phase 8 application pack — PASS (2 October 2026)

Fourteen new applications, checked in Chromium 153 against the static app copy with
synthetic fixtures, a fake capture device and no server. Logs:
`qa/phase8/phase8_apps.log`, `qa/phase8/phase8_integration.log`.

`tests/phase8_apps.py` drives one real behaviour per app:

- Clock: stopwatch advanced and lapped, timer preset set 01:00, reset cleared it.
- Focus: lengths saved, a skipped session logged, history cleared with a confirm.
- Habits: created, ticked and unticked with the counter following.
- Shopping: two items added, one completed, completed items cleared.
- Convert: 1 m = 3.28084 ft, 100 °C = 212 °F, swap reversed the units.
- Passwords: 20-character secret, a passphrase at 53 bits, `aaaaaaaaaaaa` flagged weak.
- Dates: 1405/1/1 → 21 March 2026 and back again, 30 days between 1 and 31 January.
- Health: exact age 26 y 9 m 1 d from 2000-01-01, BMI 23.5 in the healthy range.
- Writings: autosaved after a pause, reopened with its content, deleted.
- Reading: book added, progress updated to 50 %, removed.
- Word count: 10 words / 50 characters / 2 sentences for a fixed sentence.
- Voice notes: recorded through the fake microphone, saved, played back, removed.
- Photo edit: 640×480 rotated to 480×640, PNG downloaded (46 KB) and saved into Files.
- Sketch: stroked the canvas with real pointer events, saved, reopened, removed.

`tests/phase8_integration.py` covers what crosses app boundaries:

- A habit, a shopping item and a document survived a full page reload.
- Local search returns the new collections (Habits, Shopping, Writings).
- **Encrypted backup round-trip:** a 27,438-byte archive was exported, the new stores
  were wiped, the same archive was validated and restored, and the habit, shopping
  item, document and the sketch blob all came back after the reload.
- 14 apps × 2 themes × widths 320/390/430/1440: no horizontal overflow.
- `tests/phase8_accessibility.py`: axe-core **4.10.3** (WCAG 2 A/AA + 2.1 AA tags) over
  the empty surface of all fourteen apps in dark and light — **28 audits, zero
  violations**. A name containing `<img src=x onerror=…>` renders as text in the habit
  list and in search results (`tests/phase8_accessibility.py` asserts no element was
  created). Axe ran in a test-only context; the shipped CSP was not loosened. This is a
  spot check, not TalkBack work or WCAG certification.

Icon work (3 October 2026) — two new suites, both PASS:

- `tests/phase8_icons.py`: every launcher tile is painted (34/34) and keeps at
  least 3.05:1 contrast with the white glyph (range 3.05–3.51:1, worst endpoint);
  all fourteen glyphs are centred within 1.8 x / 2.0 y units of the 24×24 grid,
  fill at least 12 units, stay inside the box once the 1.65 stroke is counted,
  and differ from each other and from the older set.
- `tests/phase8_icon_usage.py`: the new glyphs render in the home launcher
  (14/14), the all-apps drawer (14/14), every app titlebar (14/14), Recently
  opened, Find result rows, the eight in-app empty states and the Voice notes
  Record button. The staged Android copy of `icons.js`, `registry.js`,
  `phase8.css` and `index.html` is byte-identical to `app/` and reports 0.8.0.

Regressions re-run in the same environment: `phase7_browser.py`, `phase7_media_size.py`
(including a 74 MB/720p WebM played from the device) and `phase4_browser.py` all PASS.
Zero page errors and zero external requests in every run.

Not covered: PHP/MariaDB suites (PHP and MySQL were not installed in this
environment — no PHP or SQL file changed), real Android/iOS devices, emulators,
store builds, real microphone hardware, and accessibility certification of the new
surfaces (only token contrast, focus rings, touch sizes and overflow were checked).

## Large-media playback fix — PASS (2 October 2026)

Reported issue: videos larger than 10 MB could not be played. Root cause was the
saved Files budget (10 MiB per file), which also blocked anything the player could
have decoded. Fix: a second, playback-only path with no size cap, plus wider format
support. Verified with `tests/phase7_media_size.py` in Chromium 153 against the
static app copy, using ffmpeg-generated synthetic fixtures:

- A **15.5 MB WebM**, an **11.5 MB H.264 MP4**, a **74 MB 720p WebM** and a
  **16 MB WAV** all played from the device picker: correct dimensions (640×360,
  480×270, 1280×720), `currentTime` advancing, `error.code 0`, blob source.
- The saved path still refuses files above 10 MiB, and now answers with a pointer to
  “Play from device” instead of a dead end; the saved library rows stay untouched.
- Guards unchanged on the new path: HTML rejected, empty file rejected, a file whose
  bytes do not match its extension rejected.
- Regressions: small saved MP4 still plays, the transient badge clears when a saved
  file is selected, undecodable media still reports a codec error, leaving the app
  revokes the temporary object URL.
- No horizontal overflow at 320/390/430/1440 px with the transient notice visible.
- Zero page errors and zero external requests during the whole run.

`tests/phase7_browser.py` and `tests/phase4_browser.py` were re-run on **2 October
2026** against the same static copy with **no PHP backend** (local/guest mode): both
PASS. The PHP/MariaDB suites were **not** re-run in that environment because PHP and
MariaDB were not installed there; no PHP or SQL file changed in this fix.

Not covered: real device/emulator playback, iOS Safari codec behaviour, network or
external-storage sources, and files larger than 74 MB (the practical limit is the
browser's codecs and available memory, not any Fayez OS cap).

## Accessibility checks — bounded scope PASS

Axe-core **4.10.3**, WCAG 2 A/AA + 2.1 AA tags: **zero violations** in the tested
empty Music/Videos/Phone and Settings app-window surfaces, each in dark and light
(eight audits). Axe injection used a CSP-bypassed **test browser context**; shipped
CSP was not loosened. This does not cover every populated/permission/native-player
state, TalkBack, OS picker, complete keyboard traversal or WCAG certification.

## Android artifact — built and statically verified

- `assembleDebug` succeeded; actual `com.fayez.os` APK, version **0.7.0**, code **7**,
  minimum API **26**, target/compile **36**. It is **debug-signed**, not a store release.
- `apksigner verify --verbose --print-certs` succeeds (APK v2 signature). Certificate
  identifies Android Debug. No user release/upload key or signed AAB was generated.
- `:app:lintDebug` succeeds with **0 errors / 8 warnings**. Warnings concern a newer
  Gradle version, portrait/Android-16 behavior, unnecessary version-qualified icon
  resource folders at this minSdk, and framework-template resources. They are recorded,
  not suppressed as a means to pass the build.
- A first combined build/lint plus browser run exhausted this small host's available
  memory. It was stopped; the successful final builds used a single worker / 768 MiB
  heap and separate browser runs. An earlier emitted APK was not treated as final
  proof of a successful whole build.
- Release task dry-run without signing variables fails with the intended clear
  signing-configuration gate. A production-signed build was not tested.
- APK inspection checks local-only config, target identity/permissions, included
  license texts, no PHP/SQL/private server data, no SW, and staged/source asset parity
  except the documented config/boot/index/extra-native files and omitted webserver dotfiles.
- App requests INTERNET/CAMERA, not CALL_PHONE/contacts/microphone/broad storage;
  AndroidX contributes its scoped signature receiver permission. Static declarations
  do not substitute for real device permission behavior tests.

## Native bridge contract — PASS, explicitly simulated native side

`tests/phase7_native_bridge.py` serves staged Android web assets in Chromium and
installs **explicit JS doubles for FayezNative**, not a real Android runtime:

- Native local-only account/health UI explains disabled services; **zero API fetches**,
  no SW registration, Android update message.
- 120,123 export bytes split into three sequential chunks; exact reconstructed bytes,
  IDs/offsets/base64 size; write failure triggers cancellation, not saved success.
- Correct dialer argument and Back/modal/Home/locked-screen minimize behavior.
- Native pause event activates configured PIN screen. Zero uncaught exceptions.

The Java bridge was compiled/linted; **SAF provider I/O, file-picker result lifecycle,
Back/insets/IME, runtime camera, FLAG_SECURE and OS intents were NOT executed on a
physical Android device or emulator**. A debug APK is not evidence of those results.

## Prior workflows rerun on Phase 7 — PASS

- `browser_smoke.py`: home 15 / drawer 20 launchers, filters/empty states, working
  launchers including Music/Videos, browser Back, global slash-search, notifications,
  preferences/system theme/offline; nine widths, no uncaught errors. The previous
  “Music/Videos coming soon” expectation was deliberately updated.
- `phase2_browser.py`: calculator parser/UI, Notes/checklists/drafts, Tasks and
  Calendar CRUD, stale-write conflicts/reminders, offline writes, editor layouts.
- `phase3_browser.py`: 55 money/validation assertions; expenses/budgets/CSV,
  currency and synthetic provider cache, contacts/photos/drafts, cross-tab conflicts,
  offline, both themes/nine widths, 100-row history cap; no uncaught errors.
- `phase4_browser.py`: weather/news fixtures/caches, real QR PNG decode/generation,
  synthetic-camera lifecycle, file bytes/rename/delete, offline and both-theme
  layouts; 14 additional validation/quota assertions; no uncaught errors.
- `storage_browser.py`: preserving v1/v2/v3 → v4 migrations, all stores of that version,
  blocked-storage error and calculator fallback.
- `phase6_browser.py`: search/PIN/notifications, encrypted full local schema/file
  bytes/newer drafts, wrong password/13 invalid archive cases, stale/second-tab
  refusal, atomic restore and cache epoch, both-theme layouts and offline.
- `phase6_edges.py`: actual five 10 MiB files (50 MiB) encrypted round-trip,
  injected transaction-abort rollback, synthetic camera track stop, accelerated
  idle/visibility lock and damaged verifier recovery. System notification payload/
  closure tests still use declared stubs; actual permission denial is tested.
- `phase5_api.py` + `phase5_browser.py`: **real isolated PHP/MariaDB**, account
  registration/scopes, Guest import, manual snapshot CAS and races, private-file
  bytes/ownership, offline edits, profile/password/session revocation, cross-tab
  logout, account isolation and both-theme nine-width forms; no uncaught errors.
- `phase6_api.py` + `phase6_account.py`: real other-session revoke and same-account
  archive portability between browser profiles; Guest mismatch and missing-Guest-DB
  behavior. A test signup hit rate limits; only isolated QA throttle files were reset.
  Production rate-limit code was not weakened.
- `api_smoke.py`: 0.7.0/phase 7 health, disabled currency, methods/parameters,
  no-store/nosniff and protected paths. Native/scripts protection checked separately.

## Static and package verification

73 app JavaScript modules plus the optional staging script; 31 PHP source files:
syntax checks. 89 precache paths exist, including the shared weather service needed
for offline startup. Documentation links, debug signature,
ZIP CRC/source parity, generated/private-file exclusion and preservation of the
previous Phase 6 ZIP are checked before delivery. Generated SDK/JDK, node_modules,
Gradle caches/build directories, test media/backups, machine local.properties and
private signing material are excluded from the source ZIP. The APK is separate.

## Weather display correction — 4 October 2026

`tests/phase4_browser.py` now verifies the updated foreground flow with isolated
Open-Meteo-shaped fixtures: the home widget names the selected default city
(Kabul) instead of prompting to select one and now fetches the selected city's
forecast on home load/foreground return if no snapshot exists or it is at least
15 minutes old. A fresh local snapshot is reused; opening Weather and manual
Refresh behave consistently; an older snapshot survives offline without being
presented as newly fetched data. The shared weather service deduplicates a home
and app request for the same city, and was added to the offline shell precache.
The browser suite passed with zero uncaught errors. The preview-only server's
fixed-host adapter was also exercised against the real Open-Meteo and geocoding
services: forecast returned HTTP 200 for Kabul (`Asia/Kabul`, 24 hourly entries,
7 days), and the home widget updated from `—°C` to the fetched temperature. This
is not a PHP/XAMPP integration run: PHP is not installed in this sandbox. If the
target XAMPP API route still reports an error, check PHP cURL, server network
access and `storage/cache/` permissions.

## Still unverified / not promised

No Android installation/emulator/physical-device run, actual SAF/dialer/camera/IME/
TalkBack/OEM test, native cloud authentication, signed release AAB, store eligibility,
real full-disk/power-cut recovery, low-end mobile performance benchmark, independent
security/cryptographic audit or certification. No native background push/alarm/media
service. No encryption of the live browser/WebView database; PIN is a UI curtain.

XAMPP/Apache on Windows and older PHP/MySQL versions were not physically tested.
Provider network availability/rights were not re-certified here. Review current
licenses/policies and complete ANDROID_BUILD.md device/release gates before public
or sensitive production use. Local archive, cloud snapshot, source ZIP and full
server backup remain different artifacts.
