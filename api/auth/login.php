<?php
declare(strict_types=1);
require __DIR__.'/../../includes/bootstrap.php';require __DIR__.'/../../includes/account.php';require_method('POST');require_csrf();$db=account_db();
if(account_user(false))json_response(false,'Already signed in. Reload your account.',null,409,'ALREADY_SIGNED_IN');
$d=json_body();fields($d,['email','password']);$email=email_field($d);$password=text_field($d,'password',1,72);
auth_limit('login-ip',$_SERVER['REMOTE_ADDR']??'unknown',40,900);auth_limit('login-email',$email,10,900);
$q=$db->prepare('SELECT * FROM users WHERE email=?');$q->execute([$email]);$u=$q->fetch();
$dummy='$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
$valid=password_verify($password,$u['password_hash']??$dummy);
if(!$u||!$valid)json_response(false,'Email or password is incorrect.',null,401,'INVALID_CREDENTIALS');
if(password_needs_rehash($u['password_hash'],PASSWORD_DEFAULT)){$q=$db->prepare('UPDATE users SET password_hash=? WHERE id=? AND password_hash=?');$q->execute([password_hash($password,PASSWORD_DEFAULT),$u['id'],$u['password_hash']]);}
sign_in($u);auth_reply($u);
