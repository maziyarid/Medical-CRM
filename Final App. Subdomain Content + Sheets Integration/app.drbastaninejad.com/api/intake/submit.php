<?php
declare(strict_types=1);
require dirname(__DIR__) . '/_bootstrap.php';
require_post();
try{json_response((new App\IntakeService())->submit(request_json(2500000),client_ip()),201);}catch(Throwable $e){app_log('intake_submit_error',['error'=>$e->getMessage()]);json_response(['success'=>false,'message'=>$e->getMessage()],422);}
