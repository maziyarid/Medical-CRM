<?php
declare(strict_types=1);require dirname(__DIR__).'/bootstrap.php';
$checks=['PHP'=>PHP_VERSION,'cURL'=>function_exists('curl_init')?'yes':'no','allow_url_fopen'=>ini_get('allow_url_fopen'),'env'=>is_file(APP_ROOT.'/.env')?'yes':'NO','storage'=>is_writable(APP_ROOT.'/storage')?'writable':'NOT writable','TSMS_FROM'=>(string)env('TSMS_FROM','')!==''?'set':'MISSING','Sheet URL'=>preg_match('#^https://script\.google\.com/macros/s/[^/]+/exec$#',(string)env('SHEET_WEBHOOK_URL',''))?'valid-looking':'MISSING/invalid'];foreach($checks as $k=>$v)echo str_pad($k,18).": $v\n";
