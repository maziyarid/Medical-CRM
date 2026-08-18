<?php
declare(strict_types=1);
namespace App;
use RuntimeException;
final class Security {
    public static function digits(string $value): string {
        return preg_replace('/\D/u','',strtr($value,['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9'])) ?? '';
    }
    public static function mobile(string $value): string { $v=self::digits($value); if(!preg_match('/^09\d{9}$/',$v)) throw new RuntimeException('شماره همراه معتبر نیست.'); return $v; }
    public static function nationalId(string $value): string { $v=self::digits($value); if(!preg_match('/^\d{10}$/',$v)) throw new RuntimeException('کد ملی باید ۱۰ رقم باشد.'); return $v; }
    public static function text(mixed $value, int $max=500): string { $v=trim((string)$value); $v=preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u','',$v)??''; return mb_substr($v,0,$max); }
    public static function saveSignature(string $dataUrl): string {
        if(!preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#',$dataUrl,$m)) throw new RuntimeException('امضا معتبر نیست.');
        $binary=base64_decode($m[1],true); if($binary===false || strlen($binary)<100 || strlen($binary)>1500000) throw new RuntimeException('حجم یا فرمت امضا معتبر نیست.');
        if(substr($binary,0,8)!=="\x89PNG\r\n\x1a\n") throw new RuntimeException('فرمت امضا PNG نیست.');
        $name=gmdate('Ymd_His').'_'.bin2hex(random_bytes(8)).'.png'; $path=APP_ROOT.'/storage/signatures/'.$name;
        if(file_put_contents($path,$binary,LOCK_EX)===false) throw new RuntimeException('ذخیره امضا ناموفق بود.'); @chmod($path,0640); return $name;
    }
}
