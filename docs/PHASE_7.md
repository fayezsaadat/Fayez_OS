# Phase 7 — 0.7.0 implementation boundaries

## Shared frontend, actual local tools

Music/Videos share `apps/media/index.js`, with tiny type-specific mounts. They offer
two paths. **Saved** files are read from the existing Files collection (metadata
first, Blob only on selection) and keep the 10 MiB/file, 100-file / 50 MiB total
library budget. **Play from device** reads the picked `File` straight through an
object URL with no size cap at all, keeps nothing in IndexedDB, uploads nothing and
revokes the URL when the app closes or another source is chosen; oversized picks on
the saved path are answered with a pointer to it. Both paths share the same player,
generation token, transport controls and release rules. Dropping a file on the player
card uses the playback-only path. Generation tokens prevent late reads from reviving a
destroyed player; release pauses, removes src, loads the empty element and revokes
its object URL. No persisted playback preference, new IDB store or duplicate library
was added.

Accepted media formats widened to MP4/M4V/MOV/WebM/OGV/MKV/3GP and
MP3/WAV/M4A/AAC/FLAC/OGG/Opus/WEBA, each still signature-checked against its
extension. A file with an unknown extension is accepted for playback only when the
browser reports a `video/*` or `audio/*` type, and is then labelled unverified.
Playback has native HTML media controls, speed, loop, previous/next and explicit
continue-next. Unsupported codecs are errors, not fabricated success. Hide/leave/
privacy lock pauses playback; there is no background service or streaming catalog.

Phone reads saved local contacts, normalizes Persian/Arabic digits, permits an
optional leading plus and 3–30 digits after punctuation stripping, and rejects
USSD/service codes. Its explicit action uses tel: in browsers and the narrow Android
ACTION_DIAL bridge in the wrapper. It cannot place calls or read SIM/call logs.
Clipboard failures have an honest manual-copy fallback message.

## Performance changes and measurements

Search `getAll(undefined,10001)` bounds each metadata fetch while preserving the
10,000-row indexing contract and detecting truncation. It no longer fetches an
unbounded store then discards most rows. Normalized title keys and query are reused
in ranking; result DOM stays 40 rows per step / maximum 200. QR vendor parsing remains
lazy for the active UI; service-worker installation still caches public vendor assets
for cold offline use. No vendor/CDN/framework was added to the web runtime.

The synthetic 2,000-note run in this environment measured approximately **25.6 ms**
for index construction and **1.1 ms** for a matching query, returning 200 of 2,000
matches (40 initially rendered). These are one sandbox measurement, not a mobile
benchmark, SLA or before/after percentage improvement. Server-session bootstrap
latency, low-memory hardware and first-time PWA installation still need measurement
on deployment devices. No minifier-generated giant bundle replaces modular source.

## Accessibility and update lifecycle

Skip-to-apps keeps the hash router intact and focuses the home landmark. Visible
focus rings, 44 px text-button targets, reduced-motion/backdrop adjustments and
forced-colors styles supplement existing labels, dialogs and focus containment.
Axe 4.10.3 (WCAG 2 A/AA + 2.1 AA tags) found no violations in the tested empty
Music/Videos/Phone and Settings surfaces in both themes. Auditing injected axe into
a CSP-bypassed **test context**; production CSP was not relaxed. This is not WCAG
certification or a TalkBack/full-keyboard audit of every possible state.

Settings checks service-worker updates explicitly. A waiting update asks the user
to close all windows, rather than calling skipWaiting during edits or restore.
Native assets are bundled and do not register a service worker; their update action
explains APK replacement. The web cache is v8.0.0 in the Phase 8 build, with 88
allowlisted public paths (the cache started at v7.0.2 / 71 paths in Phase 7).

## Android is an isolated optional build toolchain

Canonical config/package lock and Android Studio source are under `native/`;
`scripts/prepare-android.mjs` copies only app/, injects native production/local-only
configuration, adds CSP, fallback WebView guidance and licenses, and removes the SW.
Root PHP/SQL/config/uploads are never staged. Native/scripts paths are denied by
Apache and the development router. Node 22+ is for developer packaging only.

MainActivity registers a deliberately narrow Java plugin, handles Back, applies
system-bar/cutout/IME insets, dispatches pause/resume and sets FLAG_SECURE. The latter
is a platform request, not encryption. App backup/device-transfer exclusions and
cleartext prohibition are explicit. Portrait is requested but Android 16/tablet
policies can override it. Minimum Android 8 / WebView 120; keep WebView patched.

Native exports send sequential 49,152-byte chunks with offset/token/size validation
(up to 64 MiB) into a private cache file. A user-selected SAF content URI is the only
output destination. The final copy is off the UI thread; success resolves only after
streams close. Cancellation/interruption cleans staging best effort, with a next-load
cleanup for leftovers. Incomplete destination files remain possible after provider
failure; never call this a crash-proof or securely erased pipeline. No general file
path, shell, HTTP or credential bridge is exposed. Native functions were compiled
and statically analyzed; device permission/picker behavior still requires testing.

API_ENABLED=false blocks both JSON/binary requests before fetch in this delivered
APK, including provider calls. Account scope is forced Guest without deleting any
account databases. The internal app.fayez.invalid asset origin is not an API host.
There is no misleading localhost backend or weakened SameSite/CSRF policy. Native
cross-origin account authentication remains a reviewed deployment gate, not a switch
advertised as tested. Browser accounts remain live with a configured PHP/MySQL host.

Debug APK assembly/signature verification and app lint succeeded. The first combined
build/lint attempt exceeded the small host's memory; the successful final run used
one worker and a 768 MiB heap. Lint leaves eight warnings (pinned tooling update,
portrait/large-screen behavior, version-qualified icon folders and framework-template
resources). No lint error was suppressed to produce the artifact. No production
signing key, signed AAB, Android emulator/physical-device test or Play acceptance is
claimed. ANDROID_BUILD.md gives the reproducible build and explicit remaining gates.
