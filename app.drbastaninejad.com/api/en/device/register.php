<?php
declare(strict_types=1);require dirname(__DIR__).'/_bootstrap.php';
try{$d=request_json(250000);json_response((new App\DeviceVerificationService())->register((string)($d['token']??''),is_array($d['credential']??null)?$d['credential']:[]));}catch(Throwable $e){app_log('device_register_en_error',['error'=>$e->getMessage()]);json_response(['success'=>false,'message'=>$e->getMessage()],422);}
