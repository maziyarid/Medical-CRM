<?php
declare(strict_types=1);require dirname(__DIR__).'/_bootstrap.php';
try{$d=request_json(50000);json_response((new App\DeviceVerificationService())->assertionOptions((string)($d['token']??'')));}catch(Throwable $e){app_log('device_assert_options_en_error',['error'=>$e->getMessage()]);json_response(['success'=>false,'message'=>$e->getMessage()],422);}
