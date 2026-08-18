<?php
declare(strict_types=1);
namespace App;
use RuntimeException;
final class SheetClient {
    public function append(array $row): array {
        if(strtolower((string)env('SHEET_DRIVER','apps_script'))==='demo'){app_log('demo_sheet',$row);return ['ok'=>true,'row'=>0];}
        $url=trim((string)env('SHEET_WEBHOOK_URL',''));$secret=(string)env('SHEET_SHARED_SECRET','');
        if(!preg_match('#^https://script\.google\.com/macros/s/[^/]+/exec$#',$url))throw new RuntimeException('آدرس Web App شیت تنظیم نشده یا معتبر نیست.');
        if($secret===''||str_starts_with($secret,'CHANGE_'))throw new RuntimeException('رمز مشترک Google Sheet تنظیم نشده است.');
        $payload=json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);if($payload===false)throw new RuntimeException('ساخت داده شیت ناموفق بود.');
        $body=http_build_query(['secret'=>$secret,'payload'=>$payload],'','&',PHP_QUERY_RFC3986);
        $r=Http::request('POST',$url,['headers'=>['Content-Type: application/x-www-form-urlencoded; charset=UTF-8','Accept: application/json,text/plain,*/*'],'body'=>$body,'timeout'=>35]);
        if($r['error']!=='')throw new RuntimeException('خطای اتصال Google Sheet: '.$r['error']);
        if($r['status']<200||$r['status']>=400)throw new RuntimeException('Web App شیت پاسخ HTTP '.$r['status'].' برگرداند.');
        $data=json_decode(trim($r['body']),true);
        if(!is_array($data)){app_log('sheet_non_json',['status'=>$r['status'],'body'=>mb_substr($r['body'],0,500),'url'=>$r['url']]);throw new RuntimeException('پاسخ شیت JSON نیست؛ Web App را با دسترسی Anyone دوباره Deploy کنید.');}
        if(!($data['ok']??false))throw new RuntimeException('شیت ثبت را رد کرد: '.(string)($data['error']??'خطای نامشخص'));
        return $data;
    }
    public function health(): array { $url=trim((string)env('SHEET_WEBHOOK_URL',''));if(!preg_match('#^https://script\.google\.com/macros/s/[^/]+/exec$#',$url))throw new RuntimeException('Sheet URL invalid.');$r=Http::request('GET',$url,['headers'=>['Accept: application/json'],'timeout'=>20]);if($r['error']!=='')throw new RuntimeException($r['error']);$d=json_decode(trim($r['body']),true);if(!is_array($d)||!($d['ok']??false))throw new RuntimeException('Sheet Web App health response invalid.');return $d; }
}
