<?php
declare(strict_types=1);
require __DIR__.'/../../includes/bootstrap.php';require __DIR__.'/../../includes/account.php';require_method('POST');require_csrf();
$db=account_db();if(account_user(false))json_response(false,'Sign out before creating another account.',null,409,'ALREADY_SIGNED_IN');
$d=json_body();fields($d,['name','email','password']);$name=trim(text_field($d,'name',1,100));if($name==='')json_response(false,'Enter your name.',null,422,'VALIDATION');$email=email_field($d);$password=password_field($d);
auth_limit('register',$_SERVER['REMOTE_ADDR']??'unknown',5,3600);
$hash=password_hash($password,PASSWORD_DEFAULT);$db->beginTransaction();
try {
 $q=$db->prepare('INSERT INTO users (public_id,display_name,email,password_hash) VALUES (?,?,?,?)');$q->execute([bin2hex(random_bytes(16)),$name,$email,$hash]);$id=(int)$db->lastInsertId();
 $q=$db->prepare("INSERT INTO categories (user_id,scope,name) VALUES (?,'expense',?)");foreach(['Food','Transport','Shopping','Bills','Education','Health','Entertainment','Other'] as $category)$q->execute([$id,$category]);
 $q=$db->prepare('SELECT * FROM users WHERE id=?');$q->execute([$id]);$u=$q->fetch();$db->commit();
} catch(PDOException $e){if($db->inTransaction())$db->rollBack();if($e->getCode()==='23000')json_response(false,'Unable to create this account. Use a different email or sign in.',null,409,'ACCOUNT_EXISTS');throw $e;}
sign_in($u);auth_reply($u);
