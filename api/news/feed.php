<?php
declare(strict_types=1);
require __DIR__.'/../../includes/bootstrap.php';require __DIR__.'/../../includes/public-provider.php';require __DIR__.'/../../includes/news-provider.php';require_method('GET');provider_guard(['feed']);
if($config['news_provider']!=='bbc')json_response(false,'News provider is disabled by the administrator.',null,503,'PROVIDER_NOT_CONFIGURED');
$feed=$_GET['feed']??'world';if(!is_string($feed)||!in_array($feed,['world','persian'],true))json_response(false,'Choose World or Persian feed.',null,400,'INVALID_FEED');
try {$d=public_cached('news',$feed,900,static function()use($feed):array{return normalize_news(public_fetch($feed==='persian'?'https://feeds.bbci.co.uk/persian/rss.xml':'https://feeds.bbci.co.uk/news/world/rss.xml'),$feed);});json_response(true,'Publisher feed received.',$d);}catch(Throwable $e){provider_failure($e);}
