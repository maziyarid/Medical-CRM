<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit(1); }
define('BASE_PATH', dirname(__DIR__));
$envFile=BASE_PATH.'/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line=trim($line); if ($line===''||str_starts_with($line,'#')||!str_contains($line,'=')) continue;
        [$k,$v]=array_map('trim',explode('=',$line,2));
        if (strlen($v)>=2 && (($v[0]==='"'&&str_ends_with($v,'"'))||($v[0]==="'"&&str_ends_with($v,"'")))) $v=substr($v,1,-1);
        if (!isset($_ENV[$k])) { $_ENV[$k]=$v; putenv($k.'='.$v); }
    }
}
spl_autoload_register(static function(string $class): void {
    if (!str_starts_with($class,'App\\')) return;
    $file=BASE_PATH.'/app/'.str_replace('\\',DIRECTORY_SEPARATOR,substr($class,4)).'.php';
    if (is_file($file)) require_once $file;
});
$options=getopt('', ['name:','mobile:','email::','clinic::']);
$name=trim((string)($options['name']??''));
$mobile=trim((string)($options['mobile']??''));
$email=trim((string)($options['email']??''));
$clinic=max(1,(int)($options['clinic']??($_ENV['DEFAULT_CLINIC_ID']??1)));
if ($name===''||$mobile==='') {
    fwrite(STDERR,"Usage: php bootstrap-superadmin.php --name='Full Name' --mobile=09xxxxxxxxx [--email=x@example.com] [--clinic=1]\n");
    exit(2);
}
try {
    $result=(new App\Services\StaffAccountService())->bootstrapFirstSuperAdmin($clinic,$name,$mobile,$email);
    fwrite(STDOUT,json_encode(['ok'=>true,'id'=>$result['id'],'role'=>'super_admin','notification'=>$result['notification']],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL);
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR,'Bootstrap failed: '.$e->getMessage().PHP_EOL);
    exit(1);
}
