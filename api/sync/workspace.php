<?php
declare(strict_types=1);
require __DIR__.'/../../includes/bootstrap.php';require __DIR__.'/../../includes/account.php';require __DIR__.'/../../includes/workspace.php';
$method=$_SERVER['REQUEST_METHOD'];if(!in_array($method,['GET','PUT'],true)){header('Allow: GET, PUT');json_response(false,'Method not allowed.',null,405,'METHOD_NOT_ALLOWED');}
$u=owned_user();$db=account_db();if($_GET)json_response(false,'Unexpected query.',null,400,'VALIDATION');
if($method==='GET')json_response(true,'Private workspace.',workspace_row($db,(int)$u['id'])??['revision'=>0,'updatedAt'=>null,'data'=>null]);
require_csrf();$d=json_body(4*1024*1024);fields($d,['revision','data']);if(!is_int($d['revision']??null)||$d['revision']<0)json_response(false,'Invalid revision.',null,422,'VALIDATION');$value=validate_workspace($d['data']??null);
$db->beginTransaction();$q=$db->prepare('SELECT id FROM users WHERE id=? FOR UPDATE');$q->execute([$u['id']]);$old=workspace_row($db,(int)$u['id']);
if(($old['revision']??0)!==$d['revision']){$db->rollBack();json_response(false,'Cloud copy changed on another device. Refresh status, then choose which copy to keep. Nothing was overwritten.',['revision'=>$old['revision']],409,'SYNC_CONFLICT');}
$row=['revision'=>$d['revision']+1,'updatedAt'=>gmdate('Y-m-d\TH:i:s\Z'),'data'=>$value];
$q=$db->prepare("INSERT INTO settings (user_id,setting_key,setting_value) VALUES (?,'workspace_v1',?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");$q->execute([$u['id'],json_encode($row,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);$db->commit();json_response(true,'Workspace uploaded.',['revision'=>$row['revision'],'updatedAt'=>$row['updatedAt']]);
