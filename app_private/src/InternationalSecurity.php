<?php
declare(strict_types=1);
namespace App;
use RuntimeException;

final class InternationalSecurity {
    private const PASSPORT_PATTERNS = [
        'AM'=>'/^[A-Z]{2}\d{7}$/','AR'=>'/^[A-Z]{3}\d{6}$/','AT'=>'/^[A-Z]\d{7}$/','AU'=>'/^[A-Z]\d{7}$/',
        'AZ'=>'/^[A-Z]\d{8}$/','BE'=>'/^[A-Z]{2}\d{6}$/','BG'=>'/^\d{9}$/','BR'=>'/^[A-Z]{2}\d{6}$/',
        'BY'=>'/^[A-Z]{2}\d{7}$/','CA'=>'/^(?:[A-Z]{2}\d{6}|[A-Z]\d{6}[A-Z]{2})$/','CH'=>'/^[A-Z]\d{7}$/',
        'CN'=>'/^(?:G\d{8}|E(?![IO])[A-Z0-9]\d{7})$/','CY'=>'/^[A-Z](?:\d{6}|\d{8})$/','CZ'=>'/^\d{8}$/',
        'DE'=>'/^[CFGHJKLMNPRTVWXYZ0-9]{9}$/','DK'=>'/^\d{9}$/','DZ'=>'/^\d{9}$/','EE'=>'/^(?:[A-Z]\d{7}|[A-Z]{2}\d{7})$/',
        'ES'=>'/^[A-Z0-9]{2}[A-Z0-9]?\d{6}$/','FI'=>'/^[A-Z]{2}\d{7}$/','FR'=>'/^\d{2}[A-Z]{2}\d{5}$/',
        'GB'=>'/^\d{9}$/','GR'=>'/^[A-Z]{2}\d{7}$/','HR'=>'/^\d{9}$/','HU'=>'/^[A-Z]{2}\d{6,7}$/',
        'IE'=>'/^[A-Z0-9]{2}\d{7}$/','IN'=>'/^[A-Z]\d{7}$/','ID'=>'/^[A-C]\d{7}$/','IR'=>'/^[A-Z]\d{8}$/',
        'IS'=>'/^A\d{7}$/','IT'=>'/^[A-Z0-9]{2}\d{7}$/','JP'=>'/^[A-Z]{2}\d{7}$/','KR'=>'/^[MS]\d{8}$/',
        'KZ'=>'/^[A-Z]\d{7}$/','LI'=>'/^[A-Z]\d{5}$/','LT'=>'/^[A-Z0-9]{8}$/','LU'=>'/^[A-Z0-9]{8}$/',
        'LV'=>'/^[A-Z0-9]{2}\d{7}$/','LY'=>'/^[A-Z0-9]{8}$/','MT'=>'/^\d{7}$/','MY'=>'/^[AHK]\d{8}$/',
        'MX'=>'/^[A-Z]\d{8}$/','NL'=>'/^[A-Z]{2}[A-Z0-9]{6}\d$/','PK'=>'/^[A-Z]{2}\d{7}$/','PL'=>'/^[A-Z]{2}\d{7}$/',
        'RO'=>'/^\d{8,9}$/','RU'=>'/^\d{9}$/','SE'=>'/^\d{8}$/','SK'=>'/^[0-9A-Z]\d{7}$/',
        'TR'=>'/^[A-Z]\d{8}$/','UA'=>'/^[A-Z]{2}\d{6}$/','US'=>'/^(?:\d{9}|[A-Z]\d{8})$/','ZA'=>'/^[TAMD]\d{8}$/'
    ];

    public static function country(string $value): string {
        $v = strtoupper(trim($value));
        if (!preg_match('/^[A-Z]{2}$/', $v)) throw new RuntimeException('Please select a valid country.');
        return $v;
    }
    public static function phone(string $value, string $country=''): string {
        $v = trim($value);
        $v = str_replace([' ', '-', '(', ')', '.', "\t", "\r", "\n"], '', $v);
        if (str_starts_with($v, '00')) $v = '+' . substr($v, 2);
        if ($country === 'IR' && preg_match('/^09\d{9}$/', $v)) $v = '+98' . substr($v, 1);
        if (!preg_match('/^\+[1-9]\d{7,14}$/', $v)) throw new RuntimeException('Enter a valid mobile number including the country code, for example +44 7700 900123.');
        return $v;
    }
    public static function iranLocalMobile(string $e164): ?string {
        return preg_match('/^\+989\d{9}$/', $e164) ? '0' . substr($e164, 3) : null;
    }
    public static function optionalPhone(string $value, string $country=''): string {
        return trim($value)==='' ? '' : self::phone($value, $country);
    }
    public static function birthDate(string $iso): array {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', trim($iso), $m)) throw new RuntimeException('Enter a valid date of birth.');
        $y=(int)$m[1]; $mo=(int)$m[2]; $d=(int)$m[3];
        if ($y < 1900 || !checkdate($mo,$d,$y)) throw new RuntimeException('Enter a valid date of birth.');
        $dt = sprintf('%04d-%02d-%02d',$y,$mo,$d);
        if ($dt > gmdate('Y-m-d')) throw new RuntimeException('Date of birth cannot be in the future.');
        $age = (int)gmdate('Y')-$y - (gmdate('md') < sprintf('%02d%02d',$mo,$d) ? 1 : 0);
        if ($age < 0 || $age > 120) throw new RuntimeException('Date of birth is outside the accepted range.');
        return ['iso'=>$dt,'year'=>$y,'month'=>$mo,'day'=>$d,'age'=>$age];
    }
    public static function identityDocument(string $value, string $country): array {
        $country=self::country($country);
        $raw=strtoupper(trim($value));
        $v=preg_replace('/[\s-]+/','',$raw) ?? '';
        if ($v==='' || strlen($v)>20) throw new RuntimeException('Enter a valid passport or national identity number.');
        if ($country==='IR' && preg_match('/^\d{10}$/',$v)) {
            if (!self::validIranNationalId($v)) throw new RuntimeException('The Iranian national identity number is not valid.');
            return ['value'=>$v,'kind'=>'Iran national ID','validation'=>'checksum'];
        }
        $pattern=self::PASSPORT_PATTERNS[$country] ?? null;
        if ($pattern !== null && !preg_match($pattern,$v)) throw new RuntimeException('Please check the passport or identity number and the selected country.');
        if ($pattern === null && !preg_match('/^[A-Z0-9]{5,20}$/',$v)) throw new RuntimeException('Enter a valid passport or national identity number.');
        return ['value'=>$v,'kind'=>'passport/identity document','validation'=>$pattern ? 'country-format' : 'generic-format'];
    }
    public static function validIranNationalId(string $code): bool {
        if (!preg_match('/^\d{10}$/',$code) || preg_match('/^(\d)\1{9}$/',$code)) return false;
        $sum=0; for($i=0;$i<9;$i++) $sum += ((int)$code[$i])*(10-$i);
        $r=$sum%11; $check=(int)$code[9]; return $r<2 ? $check===$r : $check===(11-$r);
    }
}
