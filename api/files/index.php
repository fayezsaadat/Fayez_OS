<?php
declare(strict_types=1);
require __DIR__.'/../../includes/bootstrap.php';require __DIR__.'/../../includes/account.php';require __DIR__.'/../../includes/private-files.php';
$m=$_SERVER['REQUEST_METHOD'];if(!in_array($m,['GET','POST','PATCH','DELETE'],true)){header('Allow: GET, POST, PATCH, DELETE');json_response(false,'Method not allowed.',null,405,'METHOD_NOT_ALLOWED');}
$u=owned_user();$db=account_db();if($_GET)json_response(false,'Unexpected query.',null,400,'VALIDATION');
if($m==='GET'){$q=$db->prepare('SELECT * FROM files WHERE user_id=? ORDER BY id DESC');$q->execute([$u['id']]);$rows=$q->fetchAll();json_response(true,'Private files.',['files'=>array_map('public_file',$rows),'used'=>array_sum(array_column($rows,'size_bytes')),'limit'=>50*1024*1024]);}
require_csrf();
if($m==='POST'){
 if(count($_FILES)!==1||!isset($_FILES['file'])||$_POST)json_response(false,'Choose one file.',null,422,'INVALID_FILE');
 [$name,$mime,$size,$category]=validate_upload($_FILES['file']);$dir=private_dir();$key=bin2hex(random_bytes(32)).'.blob';$path=$dir.'/'.$key;
 $db->beginTransaction();$q=$db->prepare('SELECT id FROM users WHERE id=? FOR UPDATE');$q->execute([$u['id']]);
 $q=$db->prepare('SELECT COUNT(*) AS n,COALESCE(SUM(size_bytes),0) AS size FROM files WHERE user_id=?');$q->execute([$u['id']]);$used=$q->fetch();
 if((int)$used['n']>=100||(int)$used['size']+$size>50*1024*1024){$db->rollBack();json_response(false,'Cloud files are limited to 100 files and 50 MiB per account.',null,413,'FILE_QUOTA');}
 try{
  if(!move_uploaded_file($_FILES['file']['tmp_name'],$path))throw new RuntimeException('Private upload move failed');chmod($path,0600);
  $q=$db->prepare('INSERT INTO files (user_id,original_name,display_name,storage_key,mime_type,file_category,size_bytes,sha256) VALUES (?,?,?,?,?,?,?,?)');$q->execute([$u['id'],$name,$name,$key,$mime,$category,$size,hash_file('sha256',$path)]);$id=$db->lastInsertId();$db->commit();
 }catch(Throwable $e){if($db->inTransaction())$db->rollBack();@unlink($path);throw $e;}
 json_response(true,'File uploaded.',public_file(file_row($db,(int)$u['id'],$id)),201);
}
$d=json_body();fields($d,$m==='PATCH'?['id','version','name']:['id','version']);
if(!is_string($d['id']??null)||!is_int($d['version']??null))json_response(false,'Invalid file identity/version.',null,422,'VALIDATION');
$db->beginTransaction();$r=file_row($db,(int)$u['id'],$d['id'],true);
if((int)$r['version']!==$d['version']){$db->rollBack();json_response(false,'File changed elsewhere. Refresh the list.',null,409,'FILE_CONFLICT');}
if($m==='PATCH'){$name=file_name($d['name']??null);if(strtolower(pathinfo($name,PATHINFO_EXTENSION))!==strtolower(pathinfo($r['original_name'],PATHINFO_EXTENSION)))json_response(false,'Keep the original file extension.',null,422,'INVALID_FILE');$q=$db->prepare('UPDATE files SET display_name=?,version=version+1 WHERE id=? AND user_id=?');$q->execute([$name,$r['id'],$u['id']]);$db->commit();json_response(true,'File renamed.',public_file(file_row($db,(int)$u['id'],$d['id'])));}
// Row locked until physical removal: on failure keep metadata rather than claim deletion.
$path=private_dir().'/'.$r['storage_key'];if(is_file($path)&&!unlink($path)){$db->rollBack();json_response(false,'Unable to remove file content. Try again.',null,503,'FILE_STORAGE');}
$q=$db->prepare('DELETE FROM files WHERE id=? AND user_id=?');$q->execute([$r['id'],$u['id']]);$db->commit();json_response(true,'Cloud file deleted.');
