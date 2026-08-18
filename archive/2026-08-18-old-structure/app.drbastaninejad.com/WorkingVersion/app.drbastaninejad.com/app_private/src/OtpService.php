<?php
declare(strict_types=1);
namespace App;
use RuntimeException;
final class OtpService {
    private const VERIFIED_TTL_SECONDS=1800;

    public function send(string $phone,string $ip): array {
        $phone=Security::mobile($phone); $this->cleanupOldRecords();
        $length=max(4,min(6,(int)env('SMS_OTP_LENGTH','5'))); $demo=strtolower((string)env('SMS_PROVIDER','tsms'))==='demo';
        $code=$demo?str_pad((string)env('SMS_DEMO_CODE','12345'),$length,'0',STR_PAD_LEFT):str_pad((string)random_int(0,(10**$length)-1),$length,'0',STR_PAD_LEFT);
        $token=bin2hex(random_bytes(24)); $ttl=max(60,min(600,(int)env('SMS_OTP_TTL_SECONDS','180')));
        $template=(string)env('SMS_MESSAGE_TEMPLATE',"کد تشکیل پرونده: {{code}}\n@app.drbastaninejad.com #{{code}}");
        $message=strtr($template,['{{code}}'=>$code,'{{phone}}'=>$phone]);
        $record=['token'=>$token,'phone'=>$phone,'code_hash'=>password_hash($code,PASSWORD_DEFAULT),'ip'=>$ip,'status'=>'pending','attempts'=>0,'expires_at'=>time()+$ttl,'created_at'=>time()];
        $this->save($record);
        try { $sent=(new TsmsClient())->send($phone,$message); $record['status']='sent'; $record['message_id']=$sent['messageId']??''; $this->save($record); }
        catch(\Throwable $e){ $record['status']='failed';$record['error']=$e->getMessage();$this->save($record);throw $e; }
        return ['success'=>true,'token'=>$token,'expiresIn'=>$ttl,'message'=>'کد تأیید ارسال شد.'];
    }
    public function verify(string $phone,string $token,string $code): array {
        $phone=Security::mobile($phone); $token=preg_replace('/[^a-f0-9]/i','',$token)??''; $code=Security::digits($code); $r=$this->load($token);
        if(!$r||($r['phone']??'')!==$phone)throw new RuntimeException('درخواست کد معتبر نیست.');
        if(($r['status']??'')==='verified'){
            $remaining=$this->verifiedRemaining($r);
            if($remaining<=0)throw new RuntimeException('مهلت تکمیل فرم منقضی شده است؛ دوباره کد بگیرید.');
            return ['success'=>true,'token'=>$token,'expiresIn'=>$remaining,'expiresAt'=>$this->verifiedExpiry($r),'message'=>'شماره با موفقیت تأیید شد.'];
        }
        if(($r['status']??'')!=='sent')throw new RuntimeException('درخواست کد معتبر نیست.');
        if((int)$r['expires_at']<time())throw new RuntimeException('کد منقضی شده است.');
        if((int)($r['attempts']??0)>=(int)env('OTP_MAX_ATTEMPTS','5'))throw new RuntimeException('تعداد تلاش بیش از حد مجاز است.');
        if(!password_verify($code,(string)$r['code_hash'])){$r['attempts']=(int)($r['attempts']??0)+1;$this->save($r);throw new RuntimeException('کد تأیید نادرست است.');}
        $now=time();$r['status']='verified';$r['verified_at']=$now;$r['verified_expires_at']=$now+self::VERIFIED_TTL_SECONDS;unset($r['code_hash']);$this->save($r);
        return ['success'=>true,'token'=>$token,'expiresIn'=>self::VERIFIED_TTL_SECONDS,'expiresAt'=>$r['verified_expires_at'],'message'=>'شماره با موفقیت تأیید شد.'];
    }
    public function assertVerified(string $phone,string $token): array { $phone=Security::mobile($phone);$r=$this->load($token);if(!$r||($r['phone']??'')!==$phone||($r['status']??'')!=='verified')throw new RuntimeException('تأیید شماره معتبر نیست؛ دوباره کد بگیرید.');if($this->verifiedRemaining($r)<=0)throw new RuntimeException('مهلت تکمیل فرم منقضی شده است؛ دوباره کد بگیرید.');return $r; }
    public function consume(string $token): void { $r=$this->load($token);if($r){$r['status']='consumed';$r['consumed_at']=time();$this->save($r);} }
    private function path(string $token): string { return APP_ROOT.'/storage/otp/'.$token.'.json'; }
    private function load(string $token): ?array { if(!preg_match('/^[a-f0-9]{48}$/i',$token))return null;$d=json_decode((string)@file_get_contents($this->path($token)),true);return is_array($d)?$d:null; }
    private function save(array $record): void { $json=json_encode($record,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);if($json===false||file_put_contents($this->path((string)$record['token']),$json,LOCK_EX)===false)throw new RuntimeException('فضای ذخیره OTP قابل نوشتن نیست.');@chmod($this->path((string)$record['token']),0640); }
    private function verifiedExpiry(array $record): int { $expiry=(int)($record['verified_expires_at']??0);if($expiry>0)return $expiry;$verifiedAt=(int)($record['verified_at']??0);return $verifiedAt>0?$verifiedAt+self::VERIFIED_TTL_SECONDS:0; }
    private function verifiedRemaining(array $record): int { return max(0,$this->verifiedExpiry($record)-time()); }
    private function cleanupOldRecords(): void { $now=time();foreach(glob(APP_ROOT.'/storage/otp/*.json')?:[] as $file){$r=json_decode((string)@file_get_contents($file),true);if(!is_array($r))continue;$created=(int)($r['created_at']??0);if($created&&$now-$created>86400)@unlink($file);} }
}
