<?php
declare(strict_types=1);
function start_secure_session(): void {
    if(session_status()===PHP_SESSION_ACTIVE)return;
    $config=require __DIR__.'/../config/app.php';
    ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');ini_set('session.gc_maxlifetime','86400');
    session_cache_limiter(''); // Preserve bootstrap's explicit no-store/private headers.
    session_name($config['session_name']);
    $https=$config['https'] || (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off');
    // Path limits cookies to this deployment. Explicit configuration for TLS proxies.
    $path=rtrim(str_replace('\\','/',dirname(dirname($_SERVER['SCRIPT_NAME']??'/api/auth/session.php'))),'/');
    $pos=strrpos($path,'/api');$path=$pos===false?'/':substr($path,0,$pos).'/';
    session_set_cookie_params(['lifetime'=>0,'path'=>$path,'secure'=>$https,'httponly'=>true,'samesite'=>'Lax']);session_start();
    if(isset($_SESSION['user_id']) && (time()-($_SESSION['last_seen']??0)>1800 || time()-($_SESSION['issued_at']??0)>86400)) {$_SESSION=[];session_regenerate_id(true);}
    if(isset($_SESSION['user_id']))$_SESSION['last_seen']=time();
}
function csrf_token(): string {start_secure_session();if(!isset($_SESSION['csrf_token']))$_SESSION['csrf_token']=bin2hex(random_bytes(32));return $_SESSION['csrf_token'];}
function require_csrf(): void {
    start_secure_session();$received=$_SERVER['HTTP_X_CSRF_TOKEN']??'';$expected=$_SESSION['csrf_token']??'';
    if($expected===''||!hash_equals($expected,$received))json_response(false,'Security check failed. Reload and try again.',null,403,'CSRF_FAILED');
}
function json_body(int $limit=65536): array {
    $type=strtolower(trim(explode(';',$_SERVER['CONTENT_TYPE']??'')[0]));
    if($type!=='application/json')json_response(false,'A JSON request body is required.',null,415,'CONTENT_TYPE');
    $raw=file_get_contents('php://input',false,null,0,$limit+1);
    if($raw===false||strlen($raw)>$limit)json_response(false,'Request body is too large.',null,413,'BODY_TOO_LARGE');
    try{$data=json_decode($raw,true,32,JSON_THROW_ON_ERROR);}catch(JsonException $e){json_response(false,'Invalid JSON request.',null,400,'INVALID_JSON');}
    if(!is_array($data)||substr(ltrim($raw),0,1)!=='{')json_response(false,'A JSON object is required.',null,400,'INVALID_JSON');return $data;
}
