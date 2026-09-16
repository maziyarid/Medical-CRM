<?php
declare(strict_types=1);
namespace App;
use RuntimeException;

final class DeviceVerificationService {
    private const RP_ID='app.drbastaninejad.com';
    private const ORIGIN='https://app.drbastaninejad.com';
    private const TTL=1800;

    public function registrationOptions(string $phone, string $country, string $displayName): array {
        $phone=InternationalSecurity::phone($phone,$country); $this->ensureDir();
        $token=bin2hex(random_bytes(24)); $challenge=random_bytes(32); $userId=random_bytes(24);
        $record=['token'=>$token,'phone'=>$phone,'country'=>$country,'display_name'=>mb_substr(trim($displayName),0,100),'status'=>'registration_pending','challenge'=>$this->b64u($challenge),'user_id'=>$this->b64u($userId),'created_at'=>time(),'expires_at'=>time()+600];
        $this->save($record);
        return ['success'=>true,'token'=>$token,'publicKey'=>[
            'challenge'=>$record['challenge'],'rp'=>['name'=>'Dr Shahin Bastaninejad','id'=>self::RP_ID],
            'user'=>['id'=>$record['user_id'],'name'=>$phone,'displayName'=>$record['display_name'] ?: 'Patient'],
            'pubKeyCredParams'=>[['type'=>'public-key','alg'=>-7]],'timeout'=>60000,'attestation'=>'none',
            'authenticatorSelection'=>['authenticatorAttachment'=>'platform','residentKey'=>'discouraged','requireResidentKey'=>false,'userVerification'=>'required'],
            'excludeCredentials'=>[]
        ]];
    }
    public function register(string $token, array $credential): array {
        $r=$this->load($token); $this->assertState($r,'registration_pending');
        $this->decodeClient((string)($credential['response']['clientDataJSON']??''),'webauthn.create',(string)$r['challenge']);
        $att=$this->decode((string)($credential['response']['attestationObject']??''));
        if(!is_array($att)||!isset($att['authData'])||!is_string($att['authData'])) throw new RuntimeException('Invalid authenticator response.');
        [$credId,$pem,$signCount]=$this->parseRegistrationAuthData($att['authData']);
        $provided=$this->decodeB64u((string)($credential['rawId']??$credential['id']??''));
        if($provided===''||!hash_equals($credId,$provided)) throw new RuntimeException('Credential identifier mismatch.');
        $r['credential_id']=$this->b64u($credId); $r['public_key_pem']=$pem; $r['sign_count']=$signCount; $r['status']='registered'; unset($r['challenge']); $this->save($r);
        return ['success'=>true,'token'=>$token,'message'=>'Secure verification started.'];
    }
    public function assertionOptions(string $token): array {
        $r=$this->load($token); $this->assertState($r,'registered',false);
        $challenge=$this->b64u(random_bytes(32)); $r['challenge']=$challenge; $r['status']='assertion_pending'; $r['expires_at']=time()+600; $this->save($r);
        return ['success'=>true,'token'=>$token,'publicKey'=>['challenge'=>$challenge,'rpId'=>self::RP_ID,'timeout'=>60000,'userVerification'=>'required','allowCredentials'=>[['type'=>'public-key','id'=>$r['credential_id']]]]];
    }
    public function assertCredential(string $token, array $credential): array {
        $r=$this->load($token); $this->assertState($r,'assertion_pending');
        $this->decodeClient((string)($credential['response']['clientDataJSON']??''),'webauthn.get',(string)$r['challenge']);
        $auth=$this->decodeB64u((string)($credential['response']['authenticatorData']??''));
        $sig=$this->decodeB64u((string)($credential['response']['signature']??''));
        $clientRaw=$this->decodeB64u((string)($credential['response']['clientDataJSON']??''));
        if(strlen($auth)<37||$sig===''||$clientRaw==='') throw new RuntimeException('Invalid authenticator assertion.');
        $this->verifyRpFlags($auth);
        $credId=$this->decodeB64u((string)($credential['rawId']??$credential['id']??''));
        if(!hash_equals($this->decodeB64u((string)$r['credential_id']),$credId)) throw new RuntimeException('Credential identifier mismatch.');
        $signed=$auth.hash('sha256',$clientRaw,true);
        $ok=openssl_verify($signed,$sig,(string)$r['public_key_pem'],OPENSSL_ALGO_SHA256);
        if($ok!==1) throw new RuntimeException('Verification could not be completed. Please try again.');
        $counter=unpack('N',substr($auth,33,4))[1] ?? 0; $old=(int)($r['sign_count']??0);
        if($counter>0 && $old>0 && $counter <= $old) throw new RuntimeException('Authenticator counter validation failed.');
        $r['sign_count']=$counter; $r['status']='verified'; $r['verified_at']=time(); $r['verified_expires_at']=time()+self::TTL; unset($r['challenge']); $this->save($r);
        return ['success'=>true,'token'=>$token,'expiresIn'=>self::TTL,'message'=>'Verification complete.'];
    }
    public function assertVerified(string $phone,string $country,string $token): array {
        $phone=InternationalSecurity::phone($phone,$country); $r=$this->load($token);
        if(!$r||($r['status']??'')!=='verified'||!hash_equals((string)($r['phone']??''),$phone)||(int)($r['verified_expires_at']??0)<time()) throw new RuntimeException('Verification is missing or has expired.');
        return $r;
    }
    public function consume(string $token): void { $r=$this->load($token); if($r){$r['status']='consumed';$r['consumed_at']=time();$this->save($r);} }

    private function ensureDir(): void { $p=APP_ROOT.'/storage/device_verify'; if(!is_dir($p)&&!mkdir($p,0750,true)&&!is_dir($p)) throw new RuntimeException('Secure verification is temporarily unavailable.'); }
    private function path(string $token): string { return APP_ROOT.'/storage/device_verify/'.preg_replace('/[^a-f0-9]/i','',$token).'.json'; }
    private function load(string $token): ?array { $this->ensureDir(); if(!preg_match('/^[a-f0-9]{48}$/i',$token)) return null; $d=json_decode((string)@file_get_contents($this->path($token)),true); return is_array($d)?$d:null; }
    private function save(array $r): void { $this->ensureDir(); $j=json_encode($r,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); if($j===false||file_put_contents($this->path((string)$r['token']),$j,LOCK_EX)===false) throw new RuntimeException('Secure verification is temporarily unavailable.'); @chmod($this->path((string)$r['token']),0640); }
    private function assertState(?array $r,string $state,bool $checkExpiry=true): void { if(!$r||($r['status']??'')!==$state) throw new RuntimeException('Verification request is not valid.'); if($checkExpiry&&(int)($r['expires_at']??0)<time()) throw new RuntimeException('Verification request has expired.'); }
    private function decodeClient(string $encoded,string $type,string $challenge): array { $raw=$this->decodeB64u($encoded); $d=json_decode($raw,true); if(!is_array($d)||($d['type']??'')!==$type||($d['origin']??'')!==self::ORIGIN||!hash_equals($challenge,(string)($d['challenge']??''))) throw new RuntimeException('Verification could not be completed. Please try again.'); return $d; }
    private function parseRegistrationAuthData(string $auth): array {
        if(strlen($auth)<55) throw new RuntimeException('Authenticator data is incomplete.'); $this->verifyRpFlags($auth,true);
        $pos=37+16; $len=unpack('n',substr($auth,$pos,2))[1]??0; $pos+=2; if($len<1||strlen($auth)<$pos+$len) throw new RuntimeException('Credential data is incomplete.');
        $credId=substr($auth,$pos,$len); $pos+=$len; $cbor=substr($auth,$pos); $offset=0; $cose=$this->cbor($cbor,$offset);
        if(!is_array($cose)||($cose[1]??null)!==2||($cose[3]??null)!==-7||($cose[-1]??null)!==1||!isset($cose[-2],$cose[-3])) throw new RuntimeException('Only ES256 platform passkeys are supported.');
        $x=$cose[-2];$y=$cose[-3]; if(!is_string($x)||!is_string($y)||strlen($x)!==32||strlen($y)!==32) throw new RuntimeException('Invalid passkey public key.');
        $der=hex2bin('3059301306072A8648CE3D020106082A8648CE3D030107034200')."\x04".$x.$y; $pem="-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($der),64,"\n")."-----END PUBLIC KEY-----\n";
        $counter=unpack('N',substr($auth,33,4))[1]??0; return [$credId,$pem,$counter];
    }
    private function verifyRpFlags(string $auth,bool $requireAttested=false): void { if(!hash_equals(hash('sha256',self::RP_ID,true),substr($auth,0,32))) throw new RuntimeException('Authenticator RP ID validation failed.'); $flags=ord($auth[32]); if(($flags&0x01)===0||($flags&0x04)===0) throw new RuntimeException('User verification was not performed by the authenticator.'); if($requireAttested&&($flags&0x40)===0) throw new RuntimeException('Attested credential data is missing.'); }
    private function decode(string $encoded): mixed { $raw=$this->decodeB64u($encoded); $o=0; return $this->cbor($raw,$o); }
    private function cbor(string $data,int &$o): mixed {
        if($o>=strlen($data)) throw new RuntimeException('Invalid CBOR data.'); $b=ord($data[$o++]); $major=$b>>5; $ai=$b&31; $len=$this->cborLen($data,$o,$ai);
        if($major===0) return $len; if($major===1) return -1-$len;
        if($major===2||$major===3){$v=substr($data,$o,$len);$o+=$len;return $v;}
        if($major===4){$a=[];for($i=0;$i<$len;$i++)$a[]=$this->cbor($data,$o);return $a;}
        if($major===5){$a=[];for($i=0;$i<$len;$i++){$k=$this->cbor($data,$o);$v=$this->cbor($data,$o);$a[$k]=$v;}return $a;}
        if($major===6) return $this->cbor($data,$o);
        if($major===7){if($ai===20)return false;if($ai===21)return true;if($ai===22)return null;}
        throw new RuntimeException('Unsupported CBOR value.');
    }
    private function cborLen(string $d,int &$o,int $ai): int { if($ai<24)return $ai; if($ai===24)return ord($d[$o++]); if($ai===25){$v=unpack('n',substr($d,$o,2))[1];$o+=2;return $v;} if($ai===26){$v=unpack('N',substr($d,$o,4))[1];$o+=4;return $v;} throw new RuntimeException('Unsupported CBOR length.'); }
    private function b64u(string $v): string { return rtrim(strtr(base64_encode($v),'+/','-_'),'='); }
    private function decodeB64u(string $v): string { $v=strtr($v,'-_','+/'); $pad=strlen($v)%4; if($pad)$v.=str_repeat('=',4-$pad); return base64_decode($v,true) ?: ''; }
}
