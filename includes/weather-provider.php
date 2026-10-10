<?php
declare(strict_types=1);
function normalize_weather(array $p):array {
    $c=$p['current']??null;$h=$p['hourly']??null;$d=$p['daily']??null;
    if(!is_array($c)||!is_array($h)||!is_array($d)||count($d['time']??[])!==7||!is_string($p['timezone']??null))throw new RuntimeException('Incomplete weather data.');
    try {new DateTimeZone($p['timezone']);}catch(Throwable $e){throw new RuntimeException('Invalid weather timezone.');}
    $instant=static fn($v)=>finite_value($v,946684800,time()+86400*17,false);
    $current=['time'=>$instant($c['time']??null),'temperature'=>finite_value($c['temperature_2m']??null,-100,70,false),
      'feelsLike'=>finite_value($c['apparent_temperature']??null,-120,100),'humidity'=>finite_value($c['relative_humidity_2m']??null,0,100),
      'wind'=>finite_value($c['wind_speed_10m']??null,0,500),'code'=>finite_value($c['weather_code']??null,0,99),'isDay'=>finite_value($c['is_day']??null,0,1)];
    $hours=[];foreach(($h['time']??[]) as $i=>$t){if($t<$current['time']-1800)continue;if(count($hours)>=24)break;$hours[]=['time'=>$instant($t),'temperature'=>finite_value($h['temperature_2m'][$i]??null,-100,70),'rain'=>finite_value($h['precipitation_probability'][$i]??null,0,100),'code'=>finite_value($h['weather_code'][$i]??null,0,99)];}
    if(count($hours)<12)throw new RuntimeException('Incomplete hourly weather.');
    $days=[];foreach($d['time'] as $i=>$t)$days[]=['time'=>$instant($t),'max'=>finite_value($d['temperature_2m_max'][$i]??null,-100,70),'min'=>finite_value($d['temperature_2m_min'][$i]??null,-100,70),'rain'=>finite_value($d['precipitation_probability_max'][$i]??null,0,100),'code'=>finite_value($d['weather_code'][$i]??null,0,99),'sunrise'=>$instant($d['sunrise'][$i]??null),'sunset'=>$instant($d['sunset'][$i]??null)];
    return ['source'=>'Open-Meteo','timezone'=>$p['timezone'],'current'=>$current,'hours'=>$hours,'days'=>$days];
}
