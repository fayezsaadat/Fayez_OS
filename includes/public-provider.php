<?php
declare(strict_types=1);
// Internal fixed-provider transport. Never pass a client-supplied URL here.
function public_fetch(string $url, int $limit = 524288): string {
    if (!extension_loaded('curl')) throw new RuntimeException('PHP cURL is required.');
    $ch=curl_init($url); $body='';
    curl_setopt_array($ch,[CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,
      CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>6,
      CURLOPT_USERAGENT=>'FayezOS/0.4.0 PersonalReader',CURLOPT_HTTPHEADER=>['Accept: application/json, application/rss+xml, application/xml'],
      CURLOPT_WRITEFUNCTION=>static function($ch,string $chunk)use(&$body,$limit):int {if(strlen($body)+strlen($chunk)>$limit)return 0;$body.=$chunk;return strlen($chunk);}]);
    $ok=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);
    if($ok===false||$status!==200)throw new RuntimeException('Provider request failed.');
    return $body;
}
function public_json(string $url): array { $d=json_decode(public_fetch($url),true,32,JSON_THROW_ON_ERROR);if(!is_array($d))throw new RuntimeException('Invalid provider response.');return $d; }
function public_cached(string $namespace,string $key,int $ttl,callable $loader): array {
    $dir=__DIR__.'/../storage/cache';if(!is_dir($dir)&&!@mkdir($dir,0700,true))throw new RuntimeException('Cache unavailable.');
    $file=$dir.'/'.$namespace.'-'.hash('sha256',$key).'.json';
    $read=static function()use($file,$ttl):?array {if(!is_file($file)||time()-filemtime($file)>=$ttl||filemtime($file)>time())return null;$d=json_decode((string)@file_get_contents($file),true);if(!is_array($d)||!isset($d['fetchedAt']))return null;$d['serverCached']=true;return $d;};
    if(($d=$read())!==null)return $d;
    $lock=@fopen($dir.'/'.$namespace.'.lock','c');if(!$lock)throw new RuntimeException('Cache unavailable.');
    if(!flock($lock,LOCK_EX|LOCK_NB)){fclose($lock);throw new RuntimeException('A refresh is already running. Please try again shortly.',503);}
    try {
      if(($d=$read())!==null)return $d;
      $attempt=$dir.'/'.$namespace.'.attempt';$last=is_file($attempt)?(int)@file_get_contents($attempt):0;
      if(time()-$last<2)throw new RuntimeException('Please wait two seconds before refreshing another location or feed.',429);
      if(@file_put_contents($attempt,(string)time(),LOCK_EX)===false)throw new RuntimeException('Cache unavailable.');
      // Bound disk growth even when many different place names/coordinates are requested.
      $files=glob($dir.'/'.$namespace.'-*.json')?:[];usort($files,static fn($a,$b)=>filemtime($a)<=>filemtime($b));
      while(count($files)>=64){$old=array_shift($files);if(!@unlink($old))throw new RuntimeException('Cache maintenance unavailable.');}
      $d=$loader();$d['fetchedAt']=gmdate('c');$d['serverCached']=false;
      $temp=tempnam($dir,'public-');if(!$temp)throw new RuntimeException('Cache unavailable.');
      if(file_put_contents($temp,json_encode($d,JSON_THROW_ON_ERROR),LOCK_EX)===false||!rename($temp,$file)){@unlink($temp);throw new RuntimeException('Cache write failed.');}@chmod($file,0600);return $d;
    } finally {flock($lock,LOCK_UN);fclose($lock);}
}
function provider_guard(array $allowed):void {if(array_diff(array_keys($_GET),$allowed))json_response(false,'Unexpected query parameter.',null,400,'INVALID_PARAMETERS');}
function provider_failure(Throwable $e):void {if(in_array($e->getCode(),[429,503],true)){header('Retry-After: 2');json_response(false,$e->getMessage(),null,$e->getCode(),$e->getCode()===429?'RATE_LIMITED':'PROVIDER_BUSY');}error_log('[Fayez OS provider] '.$e->getMessage());json_response(false,'The provider could not be reached or returned invalid data. Check PHP cURL, cache permissions and network access, then retry. Saved data remains available.',null,502,'PROVIDER_ERROR');}
function finite_value($v, float $min, float $max, bool $nullable=true) {if($v===null&&$nullable)return null;if((!is_int($v)&&!is_float($v))||!is_finite((float)$v)||$v<$min||$v>$max)throw new RuntimeException('Invalid numeric provider field.');return $v;}
