<?php
declare(strict_types=1);
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/../config/database.php';
function account_db(): PDO {
    global $config;
    if ($config['database']['user'] === '') json_response(false,'Accounts need MySQL setup. Local apps are still available.',null,503,'ACCOUNT_SETUP');
    if ($config['environment'] !== 'development' && !request_https()) json_response(false,'Accounts require HTTPS. Configure the server TLS setting.',null,503,'HTTPS_REQUIRED');
    try {return database();} catch (Throwable $e) {error_log('[Fayez accounts] database connection unavailable');json_response(false,'Account storage is unavailable. Please contact the server administrator.',null,503,'ACCOUNT_STORAGE');}
}
function request_https(): bool {
    global $config;
    return ($config['https'] ?? false) || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
}
function session_binding(): string {global $config;return hash('sha256',__DIR__.'|'.$config['database']['host'].'|'.$config['database']['port'].'|'.$config['database']['name']);}
function account_user(bool $required=true): ?array {
    start_secure_session();
    if (empty($_SESSION['user_id'])) {if($required)json_response(false,'Please sign in again.',null,401,'UNAUTHENTICATED');return null;}
    $q=account_db()->prepare('SELECT id, public_id, display_name, email, timezone, session_version FROM users WHERE id=?');$q->execute([$_SESSION['user_id']]);$u=$q->fetch();
    if (!$u || ($_SESSION['app_binding']??'')!==session_binding() || (int)$u['session_version'] !== ($_SESSION['session_version'] ?? 0)) {$_SESSION=[];session_regenerate_id(true);if($required)json_response(false,'This session has expired. Please sign in again.',null,401,'UNAUTHENTICATED');return null;}
    return $u;
}
function public_user(array $u): array {return ['id'=>$u['public_id'],'name'=>$u['display_name'],'email'=>$u['email'],'timezone'=>$u['timezone']];}
function owned_user(): array {
    $u=account_user();
    if (!hash_equals($u['public_id'], $_SERVER['HTTP_X_FAYEZ_ACCOUNT'] ?? '')) json_response(false,'The active account changed. Reload before continuing.',null,409,'ACCOUNT_CHANGED');
    return $u;
}
function sign_in(array $u): void {session_regenerate_id(true);$_SESSION=['app_binding'=>session_binding(),'user_id'=>(int)$u['id'],'session_version'=>(int)$u['session_version'],'issued_at'=>time(),'last_seen'=>time(),'csrf_token'=>bin2hex(random_bytes(32))];}
function fields(array $data,array $keys): void {if(array_diff(array_keys($data),$keys))json_response(false,'Unexpected request field.',null,400,'VALIDATION');}
function text_field(array $d,string $k,int $min,int $max): string {
    $v=$d[$k]??null;
    if(!is_string($v)||strlen($v)<$min||strlen($v)>$max||!preg_match('//u',$v)||preg_match('/[\x00-\x1f\x7f]/',$v))json_response(false,'Invalid '.$k.'.',null,422,'VALIDATION');return $v;
}
function password_field(array $d,string $key='password'): string {return text_field($d,$key,12,72);}
function email_field(array $d): string {$s=strtolower(trim(text_field($d,'email',3,254)));if(!filter_var($s,FILTER_VALIDATE_EMAIL))json_response(false,'Enter a valid email address.',null,422,'VALIDATION');return $s;}
// Protected, locked local rate buckets. No IP/email plaintext; bounded file count.
function auth_limit(string $scope,string $value,int $limit,int $period): void {
    $dir=getenv('AUTH_LIMIT_DIR') ?: __DIR__.'/../storage/private/auth-limits';if(!is_dir($dir)&&!@mkdir($dir,0700,true)&&!is_dir($dir))json_response(false,'Security storage unavailable.',null,503,'SECURITY_STORAGE');
    $files=glob($dir.'/*.json')?:[];
    foreach($files as $f)if(filemtime($f)<time()-7200)@unlink($f);
    $path=$dir.'/'.hash('sha256',$scope.'|'.$value).'.json';
    if(count($files)>3000&&!is_file($path))json_response(false,'Too many requests. Try later.',null,429,'RATE_LIMITED');
    $f=fopen($path,'c+');if(!$f||!flock($f,LOCK_EX))json_response(false,'Security storage unavailable.',null,503,'SECURITY_STORAGE');chmod($path,0600);
    $v=json_decode(stream_get_contents($f)?:'{}',true);if(!is_array($v)||($v['until']??0)<=time())$v=['until'=>time()+$period,'count'=>0];
    if($v['count'] >= $limit){$wait=max(1,$v['until']-time());flock($f,LOCK_UN);fclose($f);header('Retry-After: '.$wait);json_response(false,'Too many attempts. Try again later.',null,429,'RATE_LIMITED');}
    $v['count']++;rewind($f);ftruncate($f,0);fwrite($f,json_encode($v));fflush($f);flock($f,LOCK_UN);fclose($f);
}
function auth_reply(array $u): void {json_response(true,'Account ready.',['user'=>public_user($u),'csrf'=>csrf_token()]);}
