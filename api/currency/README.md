# Currency provider API — optional, Phase 3

`GET rates.php` returns standard JSON from a fixed ExchangeRate-API adapter only
when `CURRENCY_PROVIDER=erapi` is enabled. Default disabled returns HTTP 503;
no query parameters or personal amounts are accepted. PHP cURL and protected
writable `storage/cache/` are required.

Read root `docs/CURRENCY_PROVIDER.md` for installation, response fields, errors,
cache protections, attribution, provider terms and public-deployment limits.
