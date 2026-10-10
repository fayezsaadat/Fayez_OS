<?php
declare(strict_types=1);
require __DIR__.'/../../includes/bootstrap.php';require __DIR__.'/../../includes/public-provider.php';require_method('GET');provider_guard(['q']);
if($config['weather_provider']!=='openmeteo')json_response(false,'Weather provider is disabled by the administrator.',null,503,'PROVIDER_NOT_CONFIGURED');
$q=$_GET['q']??'';if(!is_string($q)||strlen(trim($q))<2||strlen($q)>120||preg_match('/[\x00-\x1F]/',$q))json_response(false,'Enter a city name of 2–120 UTF-8 bytes.',null,400,'INVALID_SEARCH');$q=trim($q);
try{$data=public_cached('places',$q,86400,static function()use($q):array{
  $p=public_json('https://geocoding-api.open-meteo.com/v1/search?'.http_build_query(['name'=>$q,'count'=>8,'language'=>'en','format'=>'json']));$places=[];
  foreach(array_slice($p['results']??[],0,8) as $r){$lat=finite_value($r['latitude']??null,-90,90,false);$lon=finite_value($r['longitude']??null,-180,180,false);if(!is_string($r['name']??null))continue;$places[]=['name'=>substr($r['name'],0,150),'region'=>substr((string)($r['admin1']??''),0,150),'country'=>substr((string)($r['country']??''),0,100),'lat'=>round($lat,2),'lon'=>round($lon,2)];}
  return ['places'=>$places,'source'=>'Open-Meteo / GeoNames'];});json_response(true,'Location search complete.',$data);
}catch(Throwable $e){provider_failure($e);}
