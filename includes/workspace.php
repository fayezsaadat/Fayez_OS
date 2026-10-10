<?php
declare(strict_types=1);
function workspace_date(string $s): bool {$d=DateTimeImmutable::createFromFormat('!Y-m-d',$s);return $d!==false && $d->format('Y-m-d')===$s;}
const WORKSPACE_STORES=['notes','tasks','events','transactions','budgets','contacts'];
function validate_workspace(mixed $value): array {
 if(!is_array($value)||array_diff(array_keys($value),WORKSPACE_STORES)||array_diff(WORKSPACE_STORES,array_keys($value)))json_response(false,'Invalid workspace stores.',null,422,'WORKSPACE_INVALID');
 $strings=['notes'=>['title'=>800,'content'=>80000,'category'=>160,'type'=>20,'color'=>30], 'tasks'=>['title'=>960,'description'=>16000,'priority'=>10,'dueDate'=>10,'category'=>160], 'events'=>['title'=>800,'description'=>16000,'date'=>10,'time'=>5,'duration'=>4,'category'=>160,'startsAt'=>40,'endsAt'=>40,'timezone'=>64], 'transactions'=>['type'=>10,'currency'=>3,'category'=>80,'paymentMethod'=>20,'date'=>10,'amountMinor'=>18,'description'=>4000], 'budgets'=>['month'=>7,'currency'=>3], 'contacts'=>['name'=>600,'phone'=>160,'email'=>1016,'address'=>4000,'category'=>160,'photo'=>150024]];
 foreach(WORKSPACE_STORES as $store){
  $rows=$value[$store];if(!is_array($rows)||array_keys($rows)!==array_keys(array_values($rows))||count($rows)>1000)json_response(false,'Each workspace store allows up to 1,000 rows.',null,422,'WORKSPACE_LIMIT');$ids=[];
  foreach($rows as $r){
   if(!is_array($r)||!is_string($r['id']??null)||!preg_match('/^[A-Za-z0-9_.:-]{1,80}$/D',$r['id'])||isset($ids[$r['id']])||!is_int($r['version']??null)||$r['version']<1||$r['version']>1000000000)json_response(false,'Invalid or duplicate record identity/version.',null,422,'WORKSPACE_INVALID');$ids[$r['id']]=true;
   $extra=match($store){'notes'=>['checklist','pinned'],'tasks'=>['completedAt'],'events'=>['reminderMinutes'],'contacts'=>['favorite'],'budgets'=>['amountMinor'],default=>[]};
   fields($r,array_merge(['id','version','createdAt','updatedAt','deletedAt'],array_keys($strings[$store]),$extra));
   foreach(['createdAt','updatedAt'] as $key)if(!is_string($r[$key]??null)||!preg_match('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d(?:\.\d{3})?Z$/D',$r[$key]))json_response(false,'Invalid record timestamp.',null,422,'WORKSPACE_INVALID');
   foreach(['deletedAt','completedAt'] as $key)if(isset($r[$key])&&(!is_string($r[$key])||strlen($r[$key])>40||strtotime($r[$key])===false))json_response(false,'Invalid completion/deletion date.',null,422,'WORKSPACE_INVALID');
   foreach($strings[$store] as $key=>$max)if(!is_string($r[$key]??null)||strlen($r[$key])>$max||str_contains($r[$key],"\0"))json_response(false,'Invalid '.$store.' '.$key.'.',null,422,'WORKSPACE_INVALID');
   foreach(['pinned','favorite'] as $key)if(isset($r[$key])&&!is_bool($r[$key]))json_response(false,'Invalid boolean field.',null,422,'WORKSPACE_INVALID');
   if($store==='notes'){
    if(!in_array($r['type'],['text','checklist'],true)||!in_array($r['color'],['sage','sand','rose','lavender'],true)||!is_array($r['checklist']??null)||array_keys($r['checklist'])!==array_keys(array_values($r['checklist']))||count($r['checklist'])>200)json_response(false,'Invalid note.',null,422,'WORKSPACE_INVALID');
    foreach($r['checklist'] as $c)if(!is_array($c)||!is_string($c['id']??null)||!preg_match('/^[A-Za-z0-9_.:-]{1,80}$/D',$c['id'])||!is_string($c['text']??null)||strlen($c['text'])>4000||!is_bool($c['done']??null))json_response(false,'Invalid checklist.',null,422,'WORKSPACE_INVALID');
   }
   if($store==='tasks'&&!in_array($r['priority'],['low','medium','high'],true))json_response(false,'Invalid priority.',null,422,'WORKSPACE_INVALID');
   if($store==='events'&&(!is_int($r['reminderMinutes']??null)||!in_array($r['reminderMinutes'],[-1,0,5,15,30,60],true)))json_response(false,'Invalid reminder.',null,422,'WORKSPACE_INVALID');
   if($store==='tasks'&&$r['dueDate']!==''&&!workspace_date($r['dueDate']))json_response(false,'Invalid due date.',null,422,'WORKSPACE_INVALID');
   if($store==='events'&&(!workspace_date($r['date'])||!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D',$r['time'])||!ctype_digit($r['duration'])||(int)$r['duration']<5||(int)$r['duration']>1440||(int)$r['duration']%5||strtotime($r['startsAt'])===false||strtotime($r['endsAt'])===false||strtotime($r['endsAt'])-strtotime($r['startsAt'])!==(int)$r['duration']*60||!in_array($r['timezone'],DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC),true)))json_response(false,'Invalid event dates/duration/timezone.',null,422,'WORKSPACE_INVALID');
   if($store==='transactions'&&(!workspace_date($r['date'])||!in_array($r['paymentMethod'],['Cash','Bank','Card','Mobile','Other'],true)||!in_array($r['category'],$r['type']==='income'?['Salary','Freelance','Business','Gift','Other']:['Food','Transport','Shopping','Bills','Education','Health','Entertainment','Other'],true)))json_response(false,'Invalid ledger fields.',null,422,'WORKSPACE_INVALID');
   if(in_array($store,['transactions','budgets'],true)){
    if(!in_array($r['currency'],['AFN','USD','EUR','PKR','IRR','TRY','AED','GBP','SAR'],true))json_response(false,'Invalid currency.',null,422,'WORKSPACE_INVALID');
    if(!array_key_exists('amountMinor',$r)||($r['amountMinor']!==null&&(!is_string($r['amountMinor'])||!preg_match('/^\d{1,18}$/D',$r['amountMinor']))))json_response(false,'Invalid exact minor amount.',null,422,'WORKSPACE_INVALID');
   }
   if($store==='transactions'&&($r['amountMinor']===null||!in_array($r['type'],['expense','income'],true)||!preg_match('/^\d{4}-\d\d-\d\d$/D',$r['date'])))json_response(false,'Invalid transaction.',null,422,'WORKSPACE_INVALID');
   if($store==='budgets'&&($r['id']!==$r['month'].':'.$r['currency']||!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D',$r['month'])))json_response(false,'Invalid budget.',null,422,'WORKSPACE_INVALID');
   if($store==='contacts'&&$r['photo']!==''&&!preg_match('~^data:image/jpeg;base64,[A-Za-z0-9+/=]{1,150000}$~D',$r['photo']))json_response(false,'Invalid contact photo.',null,422,'WORKSPACE_INVALID');
  }
 }
 return $value;
}
function workspace_row(PDO $db,int $uid): ?array {$q=$db->prepare("SELECT setting_value FROM settings WHERE user_id=? AND setting_key='workspace_v1'");$q->execute([$uid]);$s=$q->fetchColumn();return $s===false?null:json_decode($s,true,32,JSON_THROW_ON_ERROR);}
