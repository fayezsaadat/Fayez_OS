<?php
declare(strict_types=1);
require __DIR__.'/../../includes/bootstrap.php';require __DIR__.'/../../includes/account.php';require_method('GET');
$enabled=$config['database']['user']!=='';
$u=$enabled?account_user(false):null;
json_response(true,'Session status.',['enabled'=>$enabled,'user'=>$u?public_user($u):null,'csrf'=>csrf_token()]);
