<?php
declare(strict_types=1);
function private_dir(): string {global $config;$dir=$config['private_files_dir'];if(!is_dir($dir)&&!@mkdir($dir,0700,true)&&!is_dir($dir))json_response(false,'Private file storage unavailable.',null,503,'FILE_STORAGE');return rtrim($dir,'/\\');}
function file_name(mixed $v): string {
 if(!is_string($v)||strlen($v)>180||!preg_match('//u',$v)||trim($v)!==$v||$v===''||$v[0]==='.'||preg_match('~[\\\\/<>:"|?*\x00-\x1f\x7f]|\.\.~u',$v))json_response(false,'Use a simple filename of up to 180 UTF-8 bytes, with its extension.',null,422,'INVALID_FILE');return $v;
}
function validate_upload(array $f): array {
 if(!isset($f['error'])||!is_int($f['error'])||$f['error']!==UPLOAD_ERR_OK)json_response(false,'Upload failed or exceeds the PHP upload limit.',null,413,'UPLOAD_FAILED');
 if(!is_uploaded_file($f['tmp_name']??''))json_response(false,'Invalid uploaded file.',null,422,'INVALID_FILE');
 if(isset($f['full_path'])&&$f['full_path']!==$f['name'])json_response(false,'Directory uploads are not supported.',null,422,'INVALID_FILE');
 $name=file_name($f['name']);$ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));$size=filesize($f['tmp_name']);
 if(!$size||$size>10*1024*1024)json_response(false,'Files must be between 1 byte and 10 MiB.',null,413,'FILE_LIMIT');
 $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);$head=file_get_contents($f['tmp_name'],false,null,0,32);
 $valid=match($ext){
  'png'=>$mime==='image/png'&&str_starts_with($head,"\x89PNG\r\n\x1a\n"),
  'jpg','jpeg'=>$mime==='image/jpeg'&&str_starts_with($head,"\xff\xd8\xff"),
  'webp'=>$mime==='image/webp'&&substr($head,0,4)==='RIFF'&&substr($head,8,4)==='WEBP',
  'pdf'=>$mime==='application/pdf'&&str_starts_with($head,'%PDF-'),
  'txt'=>in_array($mime,['text/plain','application/octet-stream'],true)&&!str_contains(file_get_contents($f['tmp_name']),"\0")&&preg_match('//u',file_get_contents($f['tmp_name'])),
  'zip','docx','xlsx','pptx'=>in_array($mime,['application/zip','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/vnd.openxmlformats-officedocument.presentationml.presentation'],true)&&str_starts_with($head,"PK\x03\x04"),
  'mp3'=>in_array($mime,['audio/mpeg','audio/mp3'],true),
  'wav'=>in_array($mime,['audio/x-wav','audio/wav'],true)&&substr($head,0,4)==='RIFF'&&substr($head,8,4)==='WAVE',
  'mp4'=>$mime==='video/mp4'&&substr($head,4,4)==='ftyp',default=>false};
 if(!$valid)json_response(false,'Unsupported file or content does not match its extension. HTML, SVG and executables are not accepted.',null,422,'INVALID_FILE');
 if(str_starts_with($mime,'image/')){$dim=@getimagesize($f['tmp_name']);if(!$dim||$dim[0]>4096||$dim[1]>4096)json_response(false,'Use images no larger than 4096 pixels per side.',null,422,'INVALID_FILE');}
 $category=str_starts_with($mime,'image/')?'image':(str_starts_with($mime,'audio/')?'audio':(str_starts_with($mime,'video/')?'video':'document'));
 return [$name,$mime,$size,$category];
}
function file_row(PDO $db,int $owner,string $id,bool $lock=false): array {
 if(!ctype_digit($id)||strlen($id)>18)json_response(false,'File not found.',null,404,'NOT_FOUND');
 $q=$db->prepare('SELECT * FROM files WHERE user_id=? AND id=?'.($lock?' FOR UPDATE':''));$q->execute([$owner,$id]);$row=$q->fetch();if(!$row)json_response(false,'File not found.',null,404,'NOT_FOUND');if(!preg_match('/^[a-f0-9]{64}\.blob$/D',$row['storage_key']))json_response(false,'Stored file metadata is invalid.',null,503,'FILE_STORAGE');return $row;
}
function public_file(array $r): array {return ['id'=>(string)$r['id'],'name'=>$r['display_name'],'size'=>(int)$r['size_bytes'],'mime'=>$r['mime_type'],'version'=>(int)$r['version'],'updatedAt'=>str_replace(' ','T',$r['updated_at']).'Z'];}
