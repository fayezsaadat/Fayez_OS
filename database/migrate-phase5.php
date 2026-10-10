<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require __DIR__.'/../config/database.php';$db=database();
function has_column(PDO $db,string $table,string $column): bool {$q=$db->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?');$q->execute([$table,$column]);return (int)$q->fetchColumn()>0;}
if(!has_column($db,'users','public_id')) {
 $db->exec('ALTER TABLE users ADD public_id CHAR(32) NULL');
}
$q=$db->query('SELECT id FROM users WHERE public_id IS NULL');$update=$db->prepare('UPDATE users SET public_id=? WHERE id=?');foreach($q->fetchAll() as $row)$update->execute([bin2hex(random_bytes(16)),$row['id']]);
$db->exec('ALTER TABLE users MODIFY public_id CHAR(32) NOT NULL');
$q=$db->query("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='users' AND index_name='uq_users_public'");if(!(int)$q->fetchColumn())$db->exec('ALTER TABLE users ADD UNIQUE KEY uq_users_public (public_id)');
if(!has_column($db,'users','session_version'))$db->exec('ALTER TABLE users ADD session_version INT UNSIGNED NOT NULL DEFAULT 1');
if(!has_column($db,'files','version'))$db->exec('ALTER TABLE files ADD version BIGINT UNSIGNED NOT NULL DEFAULT 1');
echo "Phase 5 migration complete. Existing rows retained.\n";
