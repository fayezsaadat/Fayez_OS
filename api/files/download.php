<?php
declare(strict_types=1);
require __DIR__.'/../../includes/bootstrap.php';require __DIR__.'/../../includes/account.php';require __DIR__.'/../../includes/private-files.php';require_method('GET');$u=owned_user();
if(array_diff(array_keys($_GET),['id'])||!is_string($_GET['id']??null))json_response(false,'Invalid file identifier.',null,400,'VALIDATION');
$r=file_row(account_db(),(int)$u['id'],$_GET['id']);$path=private_dir().'/'.$r['storage_key'];$f=@fopen($path,'rb');if(!$f)json_response(false,'Stored file content is unavailable.',null,404,'FILE_UNAVAILABLE');
session_write_close();header('Content-Type: application/octet-stream');header('Content-Length: '.filesize($path));header("Content-Disposition: attachment; filename=\"download.".strtolower(pathinfo($r['display_name'],PATHINFO_EXTENSION))."\"; filename*=UTF-8''".rawurlencode($r['display_name']));header("Content-Security-Policy: sandbox; default-src 'none'");header('Cross-Origin-Resource-Policy: same-origin');fpassthru($f);fclose($f);
