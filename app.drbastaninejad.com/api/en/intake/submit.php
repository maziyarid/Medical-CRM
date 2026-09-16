<?php
declare(strict_types=1);require dirname(__DIR__).'/_bootstrap.php';
try{$d=request_json(2200000);json_response((new App\EnglishIntakeService())->submit($d,client_ip()));}catch(Throwable $e){app_log('intake_submit_en_error',['error'=>$e->getMessage()]);json_response(['success'=>false,'message'=>$e->getMessage()],422);}
