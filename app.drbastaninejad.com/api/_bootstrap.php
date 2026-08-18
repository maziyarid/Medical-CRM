<?php
declare(strict_types=1);
$documentRoot=rtrim((string)($_SERVER['DOCUMENT_ROOT']??dirname(__DIR__)),'/\\');
$candidates=[];$configured=trim((string)(getenv('APP_PRIVATE_ROOT')?:''));if($configured!=='')$candidates[]=rtrim($configured,'/\\').'/bootstrap.php';
$candidates[]=dirname($documentRoot).'/app_private/bootstrap.php';$candidates[]=dirname($documentRoot,2).'/app_private/bootstrap.php';$candidates[]=dirname(__DIR__,3).'/app_private/bootstrap.php';$candidates[]=dirname(__DIR__,2).'/app_private/bootstrap.php';
$bootstrap=null;foreach(array_unique($candidates) as $candidate)if(is_file($candidate)){$bootstrap=$candidate;break;}
if($bootstrap===null){http_response_code(500);header('Content-Type: application/json; charset=utf-8');echo json_encode(['success'=>false,'message'=>'app_private/bootstrap.php پیدا نشد. app_private را بیرون از DocumentRoot قرار دهید.'],JSON_UNESCAPED_UNICODE);exit;}
require $bootstrap;header('X-Content-Type-Options: nosniff');header('Referrer-Policy: strict-origin-when-cross-origin');
