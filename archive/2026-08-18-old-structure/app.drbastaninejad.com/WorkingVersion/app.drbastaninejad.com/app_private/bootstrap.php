<?php
declare(strict_types=1);
define('APP_ROOT',__DIR__);
spl_autoload_register(static function(string $class):void{$prefix='App\\';if(!str_starts_with($class,$prefix))return;$file=APP_ROOT.'/src/'.substr($class,strlen($prefix)).'.php';if(is_file($file))require $file;});
require APP_ROOT.'/src/helpers.php';
App\Env::load(APP_ROOT.'/.env');
date_default_timezone_set((string)env('APP_TIMEZONE','Asia/Tehran'));
foreach(['logs','otp','signatures','pending','submitted'] as $dir){$path=APP_ROOT.'/storage/'.$dir;if(!is_dir($path)&&!mkdir($path,0750,true)&&!is_dir($path))throw new RuntimeException('Cannot create storage directory: '.$dir);}
if(env_bool('APP_DEBUG',false)){ini_set('display_errors','1');error_reporting(E_ALL);}else{ini_set('display_errors','0');error_reporting(E_ALL);}
