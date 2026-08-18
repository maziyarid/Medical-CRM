<?php
declare(strict_types=1);
namespace App;
use RuntimeException;
final class IntakeService {
    public const COLUMNS=['FirstName','LastName','FatherName','TavalodDay','TavalodMonth','TavalodYear','HomeTel','Mobile','Mobile2','CodeAshnaei','CodeBimeh','CodeMeli','CodeJob','HomeAd','Description','IsTransfer','drugs','difficult','morefmob'];
    private const REQUIRED_COLUMNS=['FirstName','LastName','FatherName','TavalodDay','TavalodMonth','TavalodYear','Mobile','CodeAshnaei','CodeMeli','CodeJob','HomeAd','IsTransfer'];
    public function submit(array $payload,string $ip): array {
        if(trim((string)($payload['_website']??''))!=='')throw new RuntimeException('درخواست رد شد.');
        $meta=is_array($payload['_meta']??null)?$payload['_meta']:[];$token=Security::text($meta['otpToken']??'',64);
        $row=[];foreach(self::COLUMNS as $column)$row[$column]=Security::text($payload[$column]??'',in_array($column,['HomeAd','Description'],true)?5000:500);
        foreach(self::REQUIRED_COLUMNS as $required)if($row[$required]==='')throw new RuntimeException('یک فیلد ضروری ناقص است: '.$required);
        foreach(['FirstName'=>'نام','LastName'=>'نام خانوادگی'] as $field=>$label){$len=mb_strlen($row[$field]);if($len<2||$len>30)throw new RuntimeException($label.' باید بین ۲ تا ۳۰ حرف باشد.');}
        $row['Mobile']=Security::mobile($row['Mobile']);
        if($row['Mobile2']!==''){
            $row['Mobile2']=Security::mobile($row['Mobile2']);
            if($row['Mobile']===$row['Mobile2'])throw new RuntimeException('شماره همراه اضطراری نباید شماره خود بیمار باشد.');
        }
        $row['CodeMeli']=Security::nationalId($row['CodeMeli']);
        (new OtpService())->assertVerified($row['Mobile'],$token);
        $signature=Security::saveSignature(Security::text($meta['signature']??'',1600000));
        $uuid=bin2hex(random_bytes(16));$record=['uuid'=>$uuid,'created_at'=>gmdate('c'),'ip'=>$ip,'row'=>$row,'signature'=>$signature,'insurance'=>Security::text($meta['insuranceType']??'',100),'source'=>Security::text($payload['_source']??'',500)];
        $pending=APP_ROOT.'/storage/pending/'.$uuid.'.json';$json=json_encode($record,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);if($json===false||file_put_contents($pending,$json,LOCK_EX)===false)throw new RuntimeException('فضای پشتیبان پرونده قابل نوشتن نیست.');@chmod($pending,0640);
        try{$sheet=(new SheetClient())->append($row);}catch(\Throwable $e){app_log('sheet_write_failed',['uuid'=>$uuid,'error'=>$e->getMessage()]);throw new RuntimeException('اطلاعات پشتیبان‌گیری شد اما ثبت در Google Sheet ناموفق بود: '.$e->getMessage());}
        rename($pending,APP_ROOT.'/storage/submitted/'.$uuid.'.json');(new OtpService())->consume($token);
        return ['success'=>true,'reference'=>strtoupper(substr($uuid,0,10)),'sheetRow'=>(int)($sheet['row']??0)];
    }
}
