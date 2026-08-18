<?php
declare(strict_types=1);
require dirname(__DIR__) . '/_bootstrap.php';
require_post();
try{$d=request_json(50000);json_response((new App\OtpService())->verify((string)($d['phone']??''),(string)($d['token']??''),(string)($d['code']??'')));}catch(Throwable $e){app_log('otp_verify_error',['error'=>$e->getMessage()]);json_response(['success'=>false,'message'=>$e->getMessage()],422);}
