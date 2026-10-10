<?php
declare(strict_types=1);
require __DIR__.'/../../includes/bootstrap.php';require __DIR__.'/../../includes/public-provider.php';require __DIR__.'/../../includes/weather-provider.php';require_method('GET');provider_guard(['lat','lon']);
if($config['weather_provider']!=='openmeteo')json_response(false,'Weather provider is disabled by the administrator.',null,503,'PROVIDER_NOT_CONFIGURED');
$lat=filter_var($_GET['lat']??null,FILTER_VALIDATE_FLOAT);$lon=filter_var($_GET['lon']??null,FILTER_VALIDATE_FLOAT);
if($lat===false||$lon===false||!is_finite($lat)||!is_finite($lon)||abs($lat)>90||abs($lon)>180)json_response(false,'Provide valid latitude and longitude.',null,400,'INVALID_COORDINATES');
$lat=round($lat,2);$lon=round($lon,2);
try {$data=public_cached('weather',"$lat:$lon",900,static function()use($lat,$lon):array{
  $query=http_build_query(['latitude'=>$lat,'longitude'=>$lon,'timezone'=>'auto','timeformat'=>'unixtime','forecast_days'=>7,
    'current'=>'temperature_2m,relative_humidity_2m,apparent_temperature,is_day,weather_code,wind_speed_10m',
    'hourly'=>'temperature_2m,precipitation_probability,weather_code','daily'=>'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max,sunrise,sunset']);
  return normalize_weather(public_json('https://api.open-meteo.com/v1/forecast?'.$query));});json_response(true,'Weather forecast received.',$data);
}catch(Throwable $e){provider_failure($e);}
