# Calendar API folder — reserved per-entity contract

Phase 5 uses authenticated `api/sync/workspace.php` for explicit six-collection
snapshots rather than exposing per-entity CRUD in this folder. Identity/profile
routes are in `api/auth`. Local app saves remain local until an explicit transfer.
See root docs/PHASE_5_API.md. Do not infer unsupported endpoints from this folder.
