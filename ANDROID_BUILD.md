# Android build — Phase 7 / 0.7.0, updated for Phase 8 / 0.8.0

> **Phase 8 note.** The source now builds versionCode **8**, versionName **0.8.0**,
> and includes the fourteen new applications. The APK published with Phase 7 is
> unchanged and therefore predates this pack: rebuild it with the same commands below
> (the Android SDK has to be downloaded again on a fresh machine).

## What was actually delivered

- A real **debug-signed APK** with application ID `com.fayez.os`, versionCode **7**,
  versionName **0.7.0**, minSdk **26 (Android 8+)**, compile/target SDK **36**.
- Capacitor **8.5.2**, Gradle **8.14.3**, Android Gradle Plugin **8.13.0**, JDK **21**.
- The same `app/` frontend, staged by `scripts/prepare-android.mjs`. No UI rewrite.
- Narrow Java bridge for hardware Back, foreground lifecycle, device **ACTION_DIAL**,
  and user-selected Storage Access Framework exports. Real Fayez icons/splash.
- Android System WebView/Chrome **120+** required. An old-WebView fallback page
  explains the requirement without deleting data. Keep WebView current for security.

**Not** a store-ready release, signed AAB, physical-device validation or independent
security audit. The APK's debug signature was verified; compilation/static lint and
browser bridge doubles are not proof that every device picker/codec works.

## Local-only Android policy

No production API domain was provided. The staging script deliberately sets
`PLATFORM: 'android'`, `API_ENABLED: false`, production mode and the inert placeholder
`https://api-not-configured.invalid/`. Both JSON and binary API clients refuse before
fetch; startup remains Guest and cannot adopt an account marker. Account screens
explain the disabled service instead of showing fake login. Weather/news/provider
refresh also reports disabled; manual currency, local tools and existing caches work.

The internal origin **https://app.fayez.invalid** is a Capacitor asset origin, **not a
production API server**. PHP, SQL, private files, environment credentials and runtime
server caches are never copied into the APK. No service worker runs inside the
native container. Browser/XAMPP behavior is unchanged, including real server accounts.

Do not enable native cloud access simply by changing a flag/URL. The current API's
HttpOnly/Secure/SameSite=Lax sessions were designed for same-origin web/PWA. Native
cross-origin cookies, CSRF, expiry/revocation, uploads, approved CORS origins and
credential storage must be reviewed and tested with the real production HTTPS host.
Do not weaken cookies/CSRF, allow wildcard origins or ship a localhost API to bypass
this gate. Provider redistribution licenses also require review before public use.

## Source layout

```
app/                              authoritative shared frontend
scripts/prepare-android.mjs        controlled staging (Node is build-time only)
native/package.json               exact Capacitor versions
native/package-lock.json          dependency integrity / repeatable npm resolution
native/capacitor.config.json       canonical optional Android build config
native/android/                   Android Studio project and Gradle wrapper
native/android/app/src/main/java/com/fayez/os/
  MainActivity.java               registration, Back, insets, pause/resume, FLAG_SECURE
  FayezNative.java                narrow SAF export and ACTION_DIAL bridge
native/licenses/                  dependency license texts
```

`native/www`, Gradle caches/builds, local.properties, keystores/signing secrets and
`native/android/app/src/main/assets` are generated/private and excluded from the
source release. They are recreated by sync/build. The root capacitor.config.json
is a matching convenience config; use commands from **native/** below as canonical.
Normal PHP/XAMPP users need **none** of this Node/Java/SDK tooling.

## Rebuild a debug APK

Install Node **22+**, JDK **21**, Android Studio/SDK platform **36**, SDK build tools
**35.0.0** (AGP default used by this build) and current platform-tools. Build tools
36.0.0 were additionally used for artifact verification. Review/accept SDK license
terms on your own development machine. Have network access to npm/Google/Maven/
Gradle for the first build; pin upgrades and retest instead of installing arbitrary
latest dependencies into a release build.

From the project root:

```sh
cd native
npm ci
npm run sync
# Open for inspection if desired:
npm run open
```

Set `JAVA_HOME` to JDK 21 and `ANDROID_HOME` to your SDK. Android Studio can create
`native/android/local.properties`; do not commit a machine-specific `sdk.dir`.

```sh
cd android
./gradlew --no-daemon --max-workers=1 assembleDebug
./gradlew --no-daemon --max-workers=1 :app:lintDebug
```

Windows: use `gradlew.bat`. Output:
`native/android/app/build/outputs/apk/debug/app-debug.apk`.
The project limits Gradle to one worker / 768 MiB heap for this constrained build
host. Larger dedicated developer machines can tune heap/workers after measurement.

After **every** frontend edit, rerun `npm run sync` before rebuilding. Never edit
staged `www` or generated asset copies as the source of truth.

## Installing this experimental APK

Transfer the provided `Fayez-OS-Phase-7-debug.apk` to a test device. Permit installation
from the specific trusted file/browser source only when Android asks; don't disable
Play Protect globally. Alternatively use `adb install path/to/the.apk`.

This debug certificate is not your long-term signing identity. A locally rebuilt
APK may use a different debug key. Android will reject an in-place update with a
mismatched signature. **Export a verified encrypted backup before any uninstall**:
uninstalling can destroy the app's local database. Keep the passphrase separately.
An update with a matching signing identity/version normally retains app data, but
back up first. Browser and APK have separate origins/storage; data does not magically
move between them. Guest → Guest archives are the current migration route; an
account-scoped browser archive cannot be restored into the native Guest workspace.

## Native behavior and permissions

- INTERNET, CAMERA and (since Phase 8) **RECORD_AUDIO** + MODIFY_AUDIO_SETTINGS are
  declared. Camera is optional and requested on explicit QR camera use; the microphone
  is requested by the WebView only when you press Record in Voice notes, and nothing is
  streamed, uploaded or transcribed. Still no CALL_PHONE, contacts or broad storage
  permission; AndroidX adds its app-scoped signature receiver permission.
- Phone/Contacts hand off an ordinary validated number through **ACTION_DIAL**; the
  user still decides whether to place a call. Service/USSD codes are rejected.
- Blob downloads use 49,152-byte chunks into a size/order/token-checked app-private
  staging file (64 MiB cap), then **ACTION_CREATE_DOCUMENT**. Only the chosen content
  URI is written. Copy runs off the UI thread; temporary data is removed on finish,
  cancellation/destroy, or next bridge load after interruption. A failed destination
  write can leave a partial document: verify the saved copy. No guaranteed physical
  secure erase, crash-proof export or broad arbitrary-path bridge is claimed.
- Android Back closes the current modal, uses in-app Back, or minimizes from Home/
  the locked screen. Pause/resume events pause media and honor configured hide-lock.
  Existing camera controllers release streams when the privacy lock destroys them.
- Insets include system bars, cutout and IME. Portrait is requested; Android 16/
  large-screen policies may ignore fixed orientation. Responsive web layout remains.
- FLAG_SECURE requests screenshot/recents protection; it is not encryption or a
  guarantee across every OEM. Platform backup/device-transfer exclusions avoid
  silently copying WebView data. Export your own encrypted archives.
- No native push/background alarm/foreground media service was added. Playback pauses
  when hidden or leaving its app. Browser system-notification features may be
  unavailable in WebView; in-app notices still work while foreground/visible.

## Your release APK / AAB (not generated or signed for you)

Create and securely back up **your own** upload/release key outside this project,
using Android Studio/keytool and your signing policy. Never send the key/passwords
to a chat, commit them or reuse this delivered debug identity for production.

The Gradle release gate requires these environment variables, set securely in your
own shell/CI secret store (not command-history plaintext or a committed file):

- `FAYEZ_KEYSTORE_PATH`: absolute path to your keystore
- `FAYEZ_STORE_PASSWORD`
- `FAYEZ_KEY_ALIAS`
- `FAYEZ_KEY_PASSWORD`

Then after sync, from `native/android`:

```sh
./gradlew --no-daemon :app:assembleRelease
./gradlew --no-daemon :app:bundleRelease
```

Without all four values, those release tasks fail clearly. No placeholder release
key is supplied. Verify signatures, package identity/version and actual device
behavior. Configure Play App Signing, upload-key recovery, store listing, privacy
policy/Data Safety, content rating and current target/API/native-library requirements
in the appropriate console. Target SDK 36 alone does not prove store eligibility.

## Physical-device acceptance gate — still open

Before relying on this build, test: install/update/uninstall backup recovery; at
least two supported WebView/Android versions; small screens/large fonts/keyboard/
rotation/gesture Back; TalkBack; camera grant/deny/revoke/late results; real audio/
video codecs and visibility cleanup; Phone without a dialer; SAF save/cancel/full
storage/cloud document providers; 50 MiB encrypted backup and restore; Web Locks
support; PIN startup/hide/idle and recents; offline cold launch and low-memory process
recreation. Add production backend/session tests only after the native auth design
is reviewed. None of these physical-device results are implied by compilation.
