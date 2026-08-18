<?php
declare(strict_types=1);
if(!function_exists('mb_strlen')){function mb_strlen(string $value,?string $encoding=null):int{$chars=preg_split('//u',$value,-1,PREG_SPLIT_NO_EMPTY);return is_array($chars)?count($chars):strlen($value);}}
if(!function_exists('mb_substr')){function mb_substr(string $value,int $start,?int $length=null,?string $encoding=null):string{$chars=preg_split('//u',$value,-1,PREG_SPLIT_NO_EMPTY);if(!is_array($chars))return substr($value,$start,$length);return implode('',array_slice($chars,$start,$length));}}
function env(string $key,?string $default=null):?string{$v=$_ENV[$key]??getenv($key);return($v===false||$v===null||$v==='')?$default:(string)$v;}
function env_bool(string $key,bool $default=false):bool{$v=env($key);return $v===null?$default:(filter_var($v,FILTER_VALIDATE_BOOL,FILTER_NULL_ON_FAILURE)??$default);}
function request_json(int $max=2500000):array{$length=(int)($_SERVER['CONTENT_LENGTH']??0);if($length>$max)throw new RuntimeException('درخواست بیش از حد بزرگ است.');$raw=file_get_contents('php://input');if($raw===false||trim($raw)==='')return[];$data=json_decode($raw,true,64,JSON_THROW_ON_ERROR);if(!is_array($data))throw new RuntimeException('فرمت درخواست نامعتبر است.');return$data;}
function json_response(array $data,int $status=200):never{http_response_code($status);header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
function require_post():void{if(strtoupper($_SERVER['REQUEST_METHOD']??'')!=='POST')json_response(['success'=>false,'message'=>'Method not allowed'],405);}
function client_ip():string{return (string)($_SERVER['REMOTE_ADDR']??'0.0.0.0');}
function app_log(string $event,array $context=[]):void{$line=json_encode(['time'=>gmdate('c'),'event'=>$event,'context'=>$context],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);@file_put_contents(APP_ROOT.'/storage/logs/app.log',$line.PHP_EOL,FILE_APPEND|LOCK_EX);}
