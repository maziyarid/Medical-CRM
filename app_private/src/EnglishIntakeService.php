<?php
declare(strict_types=1);
namespace App;
use RuntimeException;

final class EnglishIntakeService {
    public const COLUMNS=['FirstName','LastName','FatherName','TavalodDay','TavalodMonth','TavalodYear','HomeTel','Mobile','Mobile2','CodeAshnaei','CodeBimeh','CodeMeli','CodeJob','HomeAd','Description','IsTransfer','drugs','difficult','morefmob'];
    private const REQUIRED=['FirstName','LastName','FatherName','Mobile','CodeAshnaei','CodeMeli','CodeJob','HomeAd','IsTransfer'];
    public function submit(array $payload,string $ip): array {
        if(trim((string)($payload['_website']??''))!=='') throw new RuntimeException('Request rejected.');
        $country=InternationalSecurity::country((string)($payload['Country']??''));
        $phone=InternationalSecurity::phone((string)($payload['Mobile']??''),$country);
        $birth=InternationalSecurity::birthDate((string)($payload['BirthDateGregorian']??''));
        $jalali=Jalali::fromGregorian($birth['year'],$birth['month'],$birth['day']);
        $identity=InternationalSecurity::identityDocument((string)($payload['CodeMeli']??''),$country);
        $row=[]; foreach(self::COLUMNS as $c) $row[$c]=Security::text($payload[$c]??'',in_array($c,['HomeAd','Description'],true)?5000:500);
        $row['Mobile']=$phone; $row['CodeMeli']=$identity['value'];
        $row['TavalodDay']=(string)$jalali['day'];$row['TavalodMonth']=(string)$jalali['month'];$row['TavalodYear']=(string)$jalali['year'];
        $row['Mobile2']=InternationalSecurity::optionalPhone((string)($payload['Mobile2']??''),$country);
        if($row['Mobile2']!==''&&$row['Mobile2']===$row['Mobile']) throw new RuntimeException('Emergency phone must be different from the patient phone.');
        foreach(['HomeTel','morefmob'] as $c) $row[$c]=InternationalSecurity::optionalPhone((string)($payload[$c]??''),$country);
        foreach(self::REQUIRED as $c) if(trim((string)$row[$c])==='') throw new RuntimeException('Please complete all required fields.');
        foreach(['FirstName'=>'First name','LastName'=>'Last name','FatherName'=>"Father's name"] as $c=>$label){$n=mb_strlen(trim((string)$row[$c]));if($n<2||$n>60)throw new RuntimeException($label.' must contain 2–60 characters.');}
        $meta=is_array($payload['_meta']??null)?$payload['_meta']:[]; $method=(string)($meta['verificationMethod']??''); $token=Security::text($meta['verificationToken']??'',128);
        if($method==='sms') {
            $local=InternationalSecurity::iranLocalMobile($phone); if($local===null) throw new RuntimeException('SMS verification is currently available only for Iranian mobile numbers.');
            (new OtpService())->assertVerified($local,$token); $verificationNote='SMS OTP: phone ownership verified';
        } elseif($method==='device') {
            (new DeviceVerificationService())->assertVerified($phone,$country,$token); $verificationNote='Device passkey/screen-lock verified; phone ownership not verified by SMS';
        } else throw new RuntimeException('Please complete verification before submitting.');
        $countryName=Security::text($meta['countryName']??'',100); $email=Security::text($meta['email']??'',160);
        $extra=['DOB (Gregorian): '.$birth['iso'],'Identity validation: '.$identity['kind'].'/'.$identity['validation'],'Verification: '.$verificationNote];
        if($countryName!=='')$extra[]='Country: '.$countryName.' ('.$country.')'; if($email!=='')$extra[]='Email: '.$email;
        $row['Description']=trim($row['Description']); $row['Description']=implode(' | ',array_filter([$row['Description'],...$extra]));
        $signature=Security::saveSignature(Security::text($meta['signature']??'',1600000));
        $uuid=bin2hex(random_bytes(16));
        $row['Description']=trim(implode(' | ',array_filter([$row['Description'],'SubmissionRef:'.$uuid])));
        $record=['uuid'=>$uuid,'created_at'=>gmdate('c'),'ip'=>$ip,'locale'=>'en','country'=>$country,'row'=>$row,'signature'=>$signature,'source'=>Security::text($payload['_source']??'',500)];
        $pendingDir=APP_ROOT.'/storage/english_pending'; $submittedDir=APP_ROOT.'/storage/english_submitted';
        foreach([$pendingDir,$submittedDir] as $dir){if(!is_dir($dir)&&!mkdir($dir,0750,true)&&!is_dir($dir))throw new RuntimeException('Could not initialise secure submission storage.');}
        $pending=$pendingDir.'/'.$uuid.'.json';
        $json=json_encode($record,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
        if($json===false||file_put_contents($pending,$json,LOCK_EX)===false) throw new RuntimeException('Your registration could not be saved. Please try again.');
        @chmod($pending,0640);
        if($method==='sms')(new OtpService())->consume($token);else(new DeviceVerificationService())->consume($token);
        app_log('intake_queued_en',['uuid'=>$uuid]);
        return ['success'=>true,'reference'=>strtoupper(substr($uuid,0,10)),'queued'=>true];
    }
}
