<?php
declare(strict_types=1);
function news_link(string $link):?string {
    $p=parse_url(trim($link));if(!$p||($p['scheme']??'')!=='https'||isset($p['user'])||isset($p['pass'])||isset($p['port'])||!in_array(strtolower($p['host']??''),['www.bbc.com','www.bbc.co.uk','bbc.com','bbc.co.uk'],true))return null;
    return 'https://'.$p['host'].($p['path']??'/');
}
function news_text(string $text,int $max):string { $s=trim(preg_replace('/[\x{202A}-\x{202E}\x{2066}-\x{2069}]/u','',html_entity_decode(strip_tags($text),ENT_QUOTES|ENT_HTML5,'UTF-8'))??'');return preg_match('/^.{0,'.$max.'}/us',$s,$m)?$m[0]:''; }
function normalize_news(string $xml,string $feed):array {
    if(!class_exists('DOMDocument'))throw new RuntimeException('PHP DOM/XML extension is required.');
    if(stripos($xml,'<!DOCTYPE')!==false||stripos($xml,'<!ENTITY')!==false)throw new RuntimeException('DTD/entities are not accepted.');
    $old=libxml_use_internal_errors(true);$doc=new DOMDocument();
    try {if(!$doc->loadXML($xml,LIBXML_NONET|LIBXML_NOBLANKS)||$doc->documentElement->tagName!=='rss')throw new RuntimeException('Invalid RSS response.');}
    finally {libxml_clear_errors();libxml_use_internal_errors($old);}
    $source=$feed==='persian'?'BBC Persian':'BBC News · World';$items=[];$seen=[];
    foreach($doc->getElementsByTagName('item') as $node){$text=static fn($tag)=>(string)($node->getElementsByTagName($tag)->item(0)->textContent??'');$url=news_link($text('link'));$title=news_text($text('title'),350);if(!$url||!$title||isset($seen[$url]))continue;$seen[$url]=true;$t=strtotime($text('pubDate'));
      $items[]=['id'=>hash('sha256',$url),'title'=>$title,'summary'=>news_text($text('description'),1200),'url'=>$url,'publishedAt'=>$t!==false?gmdate('c',$t):null,'source'=>$source,'feed'=>$feed];if(count($items)>=40)break;}
    if(!$items)throw new RuntimeException('No usable articles in the feed.');
    return ['source'=>$source,'feed'=>$feed,'items'=>$items];
}
