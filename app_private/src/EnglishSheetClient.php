<?php
declare(strict_types=1);
namespace App;
use RuntimeException;

final class EnglishSheetClient {
    public function append(array $row, string $submissionUuid): array {
        if (strtolower((string)env('SHEET_DRIVER','apps_script'))==='demo') { app_log('demo_sheet_en',$row); return ['ok'=>true,'row'=>0]; }
        $url=trim((string)env('SHEET_WEBHOOK_URL','')); $secret=(string)env('SHEET_SHARED_SECRET','');
        if(!preg_match('#^https://script\.google\.com/macros/s/[^/]+/exec$#',$url)) throw new RuntimeException('The Google Sheet web-app URL is not configured.');
        if($secret===''||str_starts_with($secret,'CHANGE_')) throw new RuntimeException('The Google Sheet shared secret is not configured.');
        $payload=['action'=>'english_booking_append','submission_uuid'=>$submissionUuid,'row'=>$row];
        $json=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); if($json===false) throw new RuntimeException('Could not encode the sheet payload.');
        $body=http_build_query(['secret'=>$secret,'payload'=>$json],'','&',PHP_QUERY_RFC3986);
        $r=Http::request('POST',$url,['headers'=>['Content-Type: application/x-www-form-urlencoded; charset=UTF-8','Accept: application/json,text/plain,*/*'],'body'=>$body,'timeout'=>35]);
        if($r['error']!=='') throw new RuntimeException('Google Sheet connection error: '.$r['error']);
        if($r['status']<200||$r['status']>=400) throw new RuntimeException('Google Sheet returned HTTP '.$r['status'].'.');
        $data=json_decode(trim($r['body']),true);
        if(!is_array($data)) throw new RuntimeException('The Google Sheet bridge did not return JSON.');
        if(!($data['ok']??false)) throw new RuntimeException('The Google Sheet bridge rejected the submission: '.(string)($data['error']??'unknown error'));
        return $data;
    }
}
