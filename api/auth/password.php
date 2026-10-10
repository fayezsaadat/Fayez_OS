<?php
declare(strict_types=1);
require __DIR__.'/../../includes/bootstrap.php';require __DIR__.'/../../includes/account.php';require_method('POST');require_csrf();$u=owned_user();$d=json_body();fields($d,['current_password','password']);$current=text_field($d,'current_password',1,72);$new=password_field($d);
auth_limit('password',(string)$u['id'],10,900);$db=account_db();$db->beginTransaction();$q=$db->prepare('SELECT * FROM users WHERE id=? FOR UPDATE');$q->execute([$u['id']]);$row=$q->fetch();
if(!password_verify($current,$row['password_hash'])){$db->rollBack();json_response(false,'Current password is incorrect.',null,422,'INVALID_CREDENTIALS');}
$q=$db->prepare('UPDATE users SET password_hash=?, session_version=session_version+1 WHERE id=?');$q->execute([password_hash($new,PASSWORD_DEFAULT),$u['id']]);$row['session_version']++;$db->commit();sign_in($row);auth_reply($row);
