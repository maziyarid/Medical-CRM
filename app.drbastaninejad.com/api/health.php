<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
$warnings=[];$required=['logs','otp','signatures','pending','submitted'];foreach($required as $dir){$path=APP_ROOT.'/storage/'.$dir;if(!is_writable($path))$warnings[]='پوشه storage/'.$dir.' قابل نوشتن نیست.';}
if(!function_exists('curl_init')&&!filter_var((string)ini_get('allow_url_fopen'),FILTER_VALIDATE_BOOL))$warnings[]='cURL نصب نیست و allow_url_fopen خاموش است.';
if((string)env('TSMS_FROM','')===''||str_starts_with((string)env('TSMS_FROM',''),'CHANGE_'))$warnings[]='TSMS_FROM (شماره خط ارسال‌کننده) هنوز تنظیم نشده است.';
if(!preg_match('#^https://script\.google\.com/macros/s/[^/]+/exec$#',(string)env('SHEET_WEBHOOK_URL','')))$warnings[]='آدرس Web App شیت هنوز تنظیم نشده است.';
json_response(['success'=>true,'php'=>PHP_VERSION,'curl'=>function_exists('curl_init'),'privateRoot'=>true,'warnings'=>$warnings]);
