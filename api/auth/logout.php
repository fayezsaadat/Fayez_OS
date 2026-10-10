<?php
declare(strict_types=1);
require __DIR__.'/../../includes/bootstrap.php';require __DIR__.'/../../includes/account.php';require_method('POST');require_csrf();
if(account_user(false))owned_user();
$_SESSION=[];session_regenerate_id(true);json_response(true,'Signed out.',['csrf'=>csrf_token()]);
