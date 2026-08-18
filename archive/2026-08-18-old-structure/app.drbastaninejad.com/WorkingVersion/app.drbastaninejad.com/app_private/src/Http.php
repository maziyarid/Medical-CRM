<?php
declare(strict_types=1);
namespace App;
use RuntimeException;
final class Http {
    /** @return array{status:int,body:string,headers:array,error:string,url:string} */
    public static function request(string $method, string $url, array $options = []): array {
        $method = strtoupper($method);
        $headers = $options['headers'] ?? [];
        $body = (string)($options['body'] ?? '');
        $timeout = (int)($options['timeout'] ?? 25);
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) throw new RuntimeException('cURL initialization failed.');
            $responseHeaders = [];
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_MAXREDIRS => 8,
                CURLOPT_CONNECTTIMEOUT => min(10,$timeout),
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_USERAGENT => 'DrBastaninejadIntake/2.0',
                CURLOPT_ENCODING => '',
                CURLOPT_HEADERFUNCTION => static function($ch, string $line) use (&$responseHeaders): int {
                    $trim=trim($line); if($trim!=='' && str_contains($trim,':')) { [$k,$v]=explode(':',$trim,2); $responseHeaders[strtolower(trim($k))]=trim($v); } return strlen($line);
                },
            ]);
            if ($method !== 'GET' && $body !== '') curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            $result = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $effective = (string)curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $error = $result === false ? curl_error($ch) : '';
            curl_close($ch);
            if (in_array($status, [301,302,303,307,308], true) && isset($responseHeaders['location']) && $responseHeaders['location'] !== '') {
                $loc = $responseHeaders['location'];
                $ch2 = curl_init($loc);
                $responseHeaders2 = [];
                curl_setopt_array($ch2, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_CONNECTTIMEOUT => min(10,$timeout),
                    CURLOPT_TIMEOUT => $timeout,
                    CURLOPT_USERAGENT => 'DrBastaninejadIntake/2.0',
                    CURLOPT_ENCODING => '',
                    CURLOPT_HEADERFUNCTION => static function($ch2, string $line) use (&$responseHeaders2): int {
                        $trim=trim($line); if($trim!=='' && str_contains($trim,':')) { [$k,$v]=explode(':',$trim,2); $responseHeaders2[strtolower(trim($k))]=trim($v); } return strlen($line);
                    },
                ]);
                $result2 = curl_exec($ch2);
                $status2 = (int)curl_getinfo($ch2, CURLINFO_RESPONSE_CODE);
                $error2 = $result2 === false ? curl_error($ch2) : '';
                curl_close($ch2);
                return ['status'=>$status2,'body'=>$result2===false?'':(string)$result2,'headers'=>$responseHeaders2,'error'=>$error2,'url'=>$loc];
            }
            return ['status'=>$status,'body'=>$result===false?'':(string)$result,'headers'=>$responseHeaders,'error'=>$error,'url'=>$effective ?: $url];
        }
        if (!filter_var((string)ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOL)) {
            return ['status'=>0,'body'=>'','headers'=>[],'error'=>'cURL is unavailable and allow_url_fopen is disabled.','url'=>$url];
        }
        $context = stream_context_create(['http'=>[
            'method'=>$method,'timeout'=>$timeout,'ignore_errors'=>true,'follow_location'=>1,'max_redirects'=>8,
            'header'=>implode("\r\n",$headers) . "\r\nUser-Agent: DrBastaninejadIntake/2.0\r\n",
            'content'=>$method==='GET'?'':$body,
        ]]);
        $result = @file_get_contents($url,false,$context);
        $status=0; $responseHeaders=[];
        foreach($http_response_header??[] as $line){ if(preg_match('#^HTTP/\S+\s+(\d{3})#',$line,$m))$status=(int)$m[1]; elseif(str_contains($line,':')){[$k,$v]=explode(':',$line,2);$responseHeaders[strtolower(trim($k))]=trim($v);} }
        return ['status'=>$status,'body'=>$result===false?'':(string)$result,'headers'=>$responseHeaders,'error'=>$result===false?'HTTP request failed.':'','url'=>$url];
    }
}
