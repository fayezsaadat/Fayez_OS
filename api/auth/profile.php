<?php
declare(strict_types=1);
require __DIR__.'/../../includes/bootstrap.php';require __DIR__.'/../../includes/account.php';require_method('PATCH');require_csrf();$u=owned_user();$d=json_body();fields($d,['name','timezone']);
$name=trim(text_field($d,'name',1,100));$tz=text_field($d,'timezone',1,64);
if($name===''||!in_array($tz,DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC),true))json_response(false,'Choose a valid name and timezone.',null,422,'VALIDATION');
$q=account_db()->prepare('UPDATE users SET display_name=?, timezone=? WHERE id=?');$q->execute([$name,$tz,$u['id']]);auth_reply(account_user());
