# Backup and recovery operations

## Choose the right copy

| Copy | Included | Excluded |
|---|---|---|
| Phase 6 `.fayezbak` | All supported local stores, newer draft/preference cache, actual local file bytes | Server-only snapshots/files, credentials/session/PIN |
| Account cloud workspace | One manual six-collection snapshot | Histories, drafts/settings, local file bytes, multiple versions |
| Project ZIP | Application source, icons, licenses, docs, tests | User browser databases, live SQL, private uploads, host secrets |
| Operator server backup | SQL + private files + separately secured configuration | Browser-only changes on devices |

No source ZIP alone is a user-data backup. No browser archive alone is a full server
backup. Local copies remain unencrypted in the browser; only `.fayezbak` is encrypted.

## Local routine

1. Finish/save edits, open Backup, create an encrypted archive with a long unique
   passphrase. Save the passphrase separately; it has no recovery service.
2. Check the real downloaded file exists, copy it to another trusted medium and
   retain dated copies. Never email an archive and its passphrase together.
3. Use **Check backup** to authenticate/validate without replacing data. For a
   restore drill use a disposable browser profile, same account (or Guest), and
   no live edits. Compare representative notes, exact money and file bytes.
4. To restore live local data, first export a rescue archive, close every other
   workspace/legacy tab, preview the incoming copy and confirm replacement.
5. Check the restored content, especially drafts and files. If cloud replacement
   is desired, do that separately and explicitly through Account; local restore
   does not touch cloud state. Re-enable desired system notification delivery.

Failed validation or an aborted transaction must not be treated as a successful
restore. If the page crashes just after commit, reopen it: the durable cache epoch
prevents stale cached drafts/preferences from overriding the restored IDB state.
Do not delete the whole origin to recover a forgotten screen PIN; follow the
key-only procedure in PHASE_6_FA.md. That cannot decrypt a forgotten-passphrase file.

## Server routine (operator-only; not a public web endpoint)

These are procedures to adapt and test for your host, not a one-command backup
service. A staging recovery rehearsal is required. Keep dumps, archives and DB
credential files **outside the web root**, owned by the operator, permission 0600,
and encrypted at rest/in transit. Do not commit them or place them in downloads.

1. Record application release, SQL schema/migration state, PHP/MySQL versions,
   `PRIVATE_FILES_DIR` and configuration-variable names. Keep necessary deployment
   secrets separately in a restricted secret manager; exclude native session files.
2. Enter maintenance mode at the web-server/reverse-proxy layer so **all writes**,
   uploads, renames and deletes are stopped. Stop any separate writers. A SQL
   `--single-transaction` snapshot alone cannot make the filesystem consistent.
3. Dump the correct database while writes are paused. Example (replace paths/name;
   use an operator-owned defaults file rather than a password in the command line):

   ```sh
   mariadb-dump --defaults-extra-file=/secure/fayez-backup.cnf \
     --single-transaction --routines --triggers --hex-blob fayez_os \
     > /secure/backup/fayez-os.sql
   ```

   `mysqldump` is the corresponding MySQL tool. Verify exit status and that the dump
   is complete; check required privileges/version compatibility on your host.
4. Copy/archive the **entire configured private files directory**, preserving names
   and ownership information, before resuming writes. Database file rows point to
   random stored blob names: SQL-only or files-only copies are incomplete.
5. Store a release/configuration manifest and SHA-256 checksums, encrypt the backup
   set using the operator's approved backup/encryption tool, then remove exposed
   temporary plaintext according to the host's secure-storage policy. Filesystem
   deletion is not a guarantee of physical secure erase. Retain multiple dated
   copies off the same server, and monitor backup success separately from cron start.
6. Exit maintenance only after a consistent copy exists. Do not copy disposable
   QA accounts/data into production.

## Server restore drill

Use an isolated host or empty staging database/private directory first; **never
import into the live database just to test**. Verify archive authenticity/checksums,
decrypt securely, restore SQL into the explicitly selected destination, and restore
matching private bytes. Apply the documented migration only if required for the
chosen application version. Configure least-privilege runtime SQL access, private
file ownership/permissions and HTTPS. Ensure document-root rules still deny config,
SQL, uploads/private storage and test scripts. Do not expose a SQL-import web route.

After restoring, invalidate old Fayez account sessions by advancing the
`users.session_version` epoch in the restored database before accepting traffic;
for this schema an operator can execute `UPDATE users SET session_version =
session_version + 1` in the **verified destination database**. This intentionally
signs out all server sessions on their next request. Restored public account IDs
must remain unchanged if associated local archives are to match the accounts.

Verify login with a test account, six-store snapshots, authenticated byte-exact file
downloads, ownership separation and quotas before promoting the recovered host.
Record achieved recovery point/time rather than assuming zero loss. No remote
procedure can erase or recover unsynced browser-only edits on an unavailable device.
