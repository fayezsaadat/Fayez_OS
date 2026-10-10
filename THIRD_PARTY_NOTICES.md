# Third-party notices — Phases 4–5

No UI framework, CDN, runtime npm install or application bundler is required.
Two standalone QR algorithms are vendored locally and lazy-imported by QR Code.
Their code is also precached for offline use. They never receive data over a
network and are not third-party hosted services.

## qrcode-generator 1.4.4

- Author: Kazuhiko Arase.
- License: MIT; copyright notice is retained in `qrcode.js` and full text in
  `app/assets/vendor/qrcode-LICENSE.txt`.
- Source: https://github.com/kazuhikoarase/qrcode-generator
- Acquired from the pinned npm distribution:
  https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.js
- Original file SHA-256:
  `18ae399f81182bc9de916e9c77b195df20cc58d6f2d55a62b085a299f1bf1780`
- Modification: append ES-module default export; upstream generation algorithm
  unchanged. App selects UTF-8 byte encoding and error-correction level M.

## jsQR 1.4.0

- Project: cozmo/jsQR.
- License: Apache License 2.0, included in `app/assets/vendor/jsQR-LICENSE.txt`.
- Source: https://github.com/cozmo/jsQR
- Acquired from https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js
- Original file SHA-256:
  `bc40c8a15196236b2314db0856f72ca0b49980cd5413b8c852a7349f5fee0859`
- Modification: replace the UMD global target with an isolated local object,
  then add an ES-module default export. Decoder algorithm unchanged. Wrapper
  changes marked in the source; modified files have different checksums.

These versions were integration-tested in Chromium; this is not a dependency
security certification. Review updates/licenses before production distribution.
The phrase QR Code is a registered trademark of DENSO WAVE INCORPORATED, as noted
by the upstream generator.

## External data, not bundled content

Open-Meteo/GeoNames and BBC feeds retain their own attribution, data rights and
service terms. No provider forecasts, headlines, photos or rate caches are shipped
as app content. See `docs/PHASE_4_PROVIDERS.md` and `docs/CURRENCY_PROVIDER.md`.

## Optional Phase 7 Android toolchain

See [native/THIRD_PARTY_NOTICES.md](native/THIRD_PARTY_NOTICES.md) and its full license
texts. This toolchain is not required by the ordinary browser/XAMPP app.
