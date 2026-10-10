# Private files API — Phase 5

`index.php`: owner-scoped list/upload/rename/delete. `download.php`: authenticated
attachment bytes, never public URLs. Requires session, account header and CSRF on
mutations. See `../../docs/PHASE_5_API.md` and `../../INSTALLATION.md`. Local Files
remains independent and never silently uploads its library.
