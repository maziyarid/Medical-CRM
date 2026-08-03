<?php
declare(strict_types=1);
require dirname(__DIR__) . '/_bootstrap.php';
require_post();
try{$data=request_json(50000);json_response((new App\OtpService())->send((string)($data['phone']??''),client_ip()));}catch(Throwable $e){app_log('otp_send_error',['error'=>$e->getMessage()]);json_response(['success'=>false,'message'=>$e->getMessage()],422);}
