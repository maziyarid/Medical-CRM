<?php
declare(strict_types=1);
// Synthetic contract tests only. No database, patient data, network, or audio.
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\Services\\Recording\\';
    if (str_starts_with($class, $prefix)) {
        $file = __DIR__ . '/../app/Services/Recording/' . substr($class, strlen($prefix)) . '.php';
        if (is_file($file)) { require $file; }
    }
});
if (!class_exists(App\Services\Recording\RecordingService::class)) {
    echo "FAIL: source-only recording service contract is not implemented\n";
    exit(1);
}

use App\Services\Recording\{RecordingService, RecordingRepository, RecordingAuthority, RecordingStorage, RecordingRuntime, RecordingError};

final class MemoryRepository implements RecordingRepository {
    public array $sessions = [];
    public array $consents = [];
    public bool $failCommit = false;
    public function transaction(callable $operation): mixed {
        $before = [$this->sessions, $this->consents];
        try { $result = $operation(); if ($this->failCommit) { throw new RuntimeException('synthetic persistence failure'); } return $result; }
        catch (Throwable $e) { [$this->sessions, $this->consents] = $before; throw $e; }
    }
    private function key(array $scope, string $id): string { return $scope['clinicId'].':'.$scope['patientId'].':'.$id; }
    public function findByRequest(array $scope, string $requestKey): ?array {
        foreach ($this->sessions as $s) { if ($s['clinicId'] === $scope['clinicId'] && $s['patientId'] === $scope['patientId'] && $s['requestKey'] === $requestKey) { return $s; } }
        return null;
    }
    public function load(array $scope, string $sessionId): ?array { return $this->sessions[$this->key($scope, $sessionId)] ?? null; }
    public function save(array $scope, array $session, ?int $expectedVersion): void {
        $key = $this->key($scope, $session['sessionId']);
        if (($this->sessions[$key]['version'] ?? null) !== $expectedVersion) { throw new RecordingError('VERSION_CONFLICT'); }
        $this->sessions[$key] = $session;
    }
    public function consent(array $scope, string $receiptId): ?array { return $this->consents[$this->key($scope, $receiptId)] ?? null; }
    public function revokeConsent(array $scope, string $receiptId, int $at): void {
        $key = $this->key($scope, $receiptId);
        if (!isset($this->consents[$key])) { throw new RecordingError('CONSENT_REQUIRED'); }
        $this->consents[$key]['status'] = 'revoked'; $this->consents[$key]['revokedAt'] = $at;
    }
}
final class Authority implements RecordingAuthority {
    public bool $allow = true;
    public int $expires = 20_000_000;
    public ?string $denyPermission = null;
    public function grant(array $scope, string $permission, int $at): ?array {
        return $this->allow && $permission !== $this->denyPermission ? $scope + ['expiresAt' => $this->expires] : null;
    }
}
final class Storage implements RecordingStorage {
    public bool $available = true;
    public array $chunks = [];
    public ?array $media = null;
    public mixed $onMedia = null;
    public mixed $onChunk = null;
    public function ready(array $scope): bool { return $this->available; }
    public function verifiedChunk(array $scope, string $sessionId, int $sequence): ?array { if ($this->onChunk) { ($this->onChunk)(); } return $this->chunks[$sequence] ?? null; }
    public function verifiedMedia(array $scope, string $sessionId, string $manifestHash): ?array { if ($this->onMedia) { ($this->onMedia)(); } return $this->media; }
}
final class Runtime implements RecordingRuntime {
    public int $now = 1_000;
    public int $next = 0;
    public mixed $onId = null;
    public ?string $policy = 'clinic_policy_v1';
    public ?array $config = ['name' => 'approved-local', 'version' => '1.2.3', 'model' => 'synthetic-model', 'revision' => 'synthetic-r1'];
    public function nowMs(): int { return $this->now; }
    public function newId(): string { if($this->onId){($this->onId)();} return 'opaque_'.++$this->next; }
    public function consentPolicyVersion(): ?string { return $this->policy; }
    public function provider(): ?array { return $this->config; }
}
function fixture(): array {
    $scope = ['clinicId' => '2', 'patientId' => '9', 'actorId' => '4'];
    $repo = new MemoryRepository(); $auth = new Authority(); $storage = new Storage(); $runtime = new Runtime();
    $repo->consents['2:9:consent_1'] = ['id' => 'consent_1', 'clinicId' => '2', 'patientId' => '9', 'actorId' => '4',
        'scope' => 'recording_transcription', 'status' => 'active', 'policyVersion' => 'clinic_policy_v1',
        'recordedAt' => 500, 'expiresAt' => 20_000_000, 'revokedAt' => null, 'participants' => ['participant_1', 'participant_2']];
    return [new RecordingService($repo, $auth, $storage, $runtime), $scope, $repo, $auth, $storage, $runtime];
}
function same(mixed $actual, mixed $expected): void { if ($actual !== $expected) { throw new RuntimeException('Expected '.json_encode($expected).' got '.json_encode($actual)); } }
function error(string $code, callable $run): void {
    try { $run(); } catch (RecordingError $e) { same($e->getMessage(), $code); return; }
    throw new RuntimeException('Expected '.$code);
}
function newSession(array $f): string { [$svc, $scope] = $f; return $svc->create($scope, 'consent_1', 'request_1')['sessionId']; }
function start(array $f): string { $id = newSession($f); $f[0]->start($f[1], $id); return $id; }
function chunk(array $f, string $id, int $n = 0, array $changes = []): array {
    $c = array_replace(['patientId' => '9', 'sessionId' => $id, 'sequence' => $n, 'startMs' => $n * 30_000, 'endMs' => ($n+1)*30_000,
        'streamId' => 'stream_1', 'initializationSequence' => 0, 'mimeType' => 'audio/webm;codecs=opus', 'byteLength' => 8,
        'sha256' => hash('sha256', 'chunk_'.$n)], $changes);
    $c['idempotencyKey'] = $id.':'.$n.':'.$c['sha256'];
    $f[4]->chunks[$n] = $c + ['clinicId' => '2', 'privateObjectId' => 'private_'.$n, 'durable' => true, 'checksumVerified' => true];
    return $c;
}
function queued(array $f): string {
    $id = start($f); $f[5]->now += 60_000;
    $f[0]->acceptChunk($f[1], $id, chunk($f, $id, 1));
    $f[0]->acceptChunk($f[1], $id, chunk($f, $id, 0));
    $f[0]->stop($f[1], $id, 2, 60_000);
    $f[0]->finalize($f[1], $id);
    return $id;
}
function running(array $f): array {
    $id = queued($f); $job = $f[0]->claim($f[1], $id);
    $f[4]->media = ['manifestHash' => $job['manifestHash'], 'audioDurationMs' => 60_000, 'codecValidated' => true, 'containerValidated' => true];
    return [$id, $job['leaseToken']];
}
function transcript(): array { return ['language' => 'fa', 'segments' => [
    ['id' => 'turn_1', 'startMs' => 0, 'endMs' => 800, 'text' => 'synthetic words', 'speakerId' => null, 'uncertainty' => 0.3,
        'sourceIntervals' => [['startMs' => 0, 'endMs' => 800]]],
    ['id' => 'turn_2', 'startMs' => 400, 'endMs' => 1000, 'text' => 'overlap', 'speakerId' => 'anonymous_1', 'uncertainty' => null,
        'sourceIntervals' => [['startMs' => 400, 'endMs' => 1000]]]
]]; }
$tests = [];
function test(string $name, callable $run): void { global $tests; $tests[$name] = $run; }

test('absent dependencies fail closed', function () { error('RECORDING_UNCONFIGURED', fn() => (new RecordingService())->create(['clinicId'=>'2','patientId'=>'9','actorId'=>'4'], 'c', 'r')); });
test('missing clinic and zero actor are denied', function () { $f=fixture(); foreach ([[], ['clinicId'=>'2','patientId'=>'9','actorId'=>'0']] as $s) { error('ACCESS_DENIED', fn()=>$f[0]->create($s,'consent_1','r')); } });
test('permission and lease required', function () { $f=fixture(); $f[3]->allow=false; error('ACCESS_DENIED',fn()=>newSession($f)); $f[3]->allow=true; $f[3]->expires=$f[5]->now; error('ACCESS_DENIED',fn()=>newSession($f)); });
test('storage and provider and policy must be configured', function () {
    $f=fixture(); $f[4]->available=false; error('STORAGE_UNCONFIGURED',fn()=>newSession($f));
    $f=fixture(); $f[5]->config=null; error('TRANSCRIPTION_UNCONFIGURED',fn()=>newSession($f));
    $f=fixture(); $f[5]->policy=null; error('CONSENT_REQUIRED',fn()=>newSession($f));
});
test('consent must be persisted and clinic actor patient scoped', function () {
    foreach (['clinicId'=>'3','patientId'=>'10','actorId'=>'5','status'=>'revoked','policyVersion'=>'old','scope'=>'recording','recordedAt'=>1001,'expiresAt'=>1000,'revokedAt'=>999,'participants'=>[]] as $key=>$bad) {
        $f=fixture(); $f[2]->consents['2:9:consent_1'][$key]=$bad; error('CONSENT_REQUIRED',fn()=>newSession($f));
    }
    $f=fixture(); unset($f[2]->consents['2:9:consent_1']); error('CONSENT_REQUIRED',fn()=>newSession($f));
});
test('create retry same request has stable identity and conflict is rejected', function () {
    $f=fixture(); $id=newSession($f); same(newSession($f),$id); same(count($f[2]->sessions),1);
    $f[2]->consents['2:9:consent_2']=$f[2]->consents['2:9:consent_1']; $f[2]->consents['2:9:consent_2']['id']='consent_2';
    error('IDEMPOTENCY_CONFLICT',fn()=>$f[0]->create($f[1],'consent_2','request_1'));
});
test('ready session does not start until explicit start', function () { $f=fixture(); $id=newSession($f); same($f[2]->load($f[1],$id)['status'],'ready'); $f[0]->start($f[1],$id); same($f[2]->load($f[1],$id)['startedAt'],1000); error('INVALID_STATE',fn()=>$f[0]->start($f[1],$id)); });
test('cross clinic patient or staff cannot access session', function () { $f=fixture(); $id=start($f); foreach (['clinicId'=>'3','patientId'=>'8','actorId'=>'5'] as $key=>$value) { $s=$f[1];$s[$key]=$value; error('ACCESS_DENIED',fn()=>$f[0]->stop($s,$id,1,1)); } });
test('chunk metadata cannot fake durable storage', function () { $f=fixture();$id=start($f);$f[5]->now+=30_000;$c=chunk($f,$id);$f[4]->chunks=[];$c['stored']=true;error('CHUNK_NOT_VERIFIED',fn()=>$f[0]->acceptChunk($f[1],$id,$c)); });
test('chunk acknowledgement matches frontend contract and strips object references', function () { $f=fixture();$id=start($f);$f[5]->now+=30_000;$c=chunk($f,$id);$ack=$f[0]->acceptChunk($f[1],$id,$c);$expected=$c+['stored'=>true];ksort($ack);ksort($expected);same($ack,$expected);same(isset($ack['privateObjectId']),false); });
test('same chunk retry is idempotent and changed metadata conflicts', function () { $f=fixture();$id=start($f);$f[5]->now+=30_000;$c=chunk($f,$id);$a=$f[0]->acceptChunk($f[1],$id,$c);same($f[0]->acceptChunk($f[1],$id,$c),$a);$c['sha256']=str_repeat('a',64);$c['idempotencyKey']=$id.':0:'.$c['sha256'];error('CHUNK_CONFLICT',fn()=>$f[0]->acceptChunk($f[1],$id,$c)); });
test('checksum size and tenant attestation mismatch rejected', function () { foreach (['sha256'=>str_repeat('b',64),'byteLength'=>9,'clinicId'=>'3','durable'=>false,'checksumVerified'=>false] as $key=>$bad) { $f=fixture();$id=start($f);$f[5]->now+=30_000;$c=chunk($f,$id);$f[4]->chunks[0][$key]=$bad;error('CHUNK_NOT_VERIFIED',fn()=>$f[0]->acceptChunk($f[1],$id,$c)); } });
test('chunk size duration sequence MIME digest identity bounds enforced', function () { foreach (['byteLength'=>2097153,'endMs'=>60001,'sequence'=>720,'mimeType'=>'text/html','sha256'=>'not-a-hash','patientId'=>'8','initializationSequence'=>1] as $key=>$bad) { $f=fixture();$id=start($f);$f[5]->now+=60_000;$c=chunk($f,$id,0,[$key=>$bad]);error('INVALID_CHUNK',fn()=>$f[0]->acceptChunk($f[1],$id,$c)); } });
test('live chunk cannot claim duration beyond server elapsed time', function () { $f=fixture();$id=start($f);$c=chunk($f,$id);error('INVALID_CHUNK',fn()=>$f[0]->acceptChunk($f[1],$id,$c)); });
test('out of order upload finalizes in order exactly once', function () { $f=fixture();$id=queued($f);$s=$f[2]->load($f[1],$id);same(array_column($s['manifest']['chunks'],'sequence'),[0,1]);same($s['manifest']['totalBytes'],16);$j=$f[0]->finalize($f[1],$id);same($j,$s['job']['descriptor']);same($j['autoWriteEmr'],false);same(isset($j['manifest']),false); });
test('missing chunk blocks queue creation', function () { $f=fixture();$id=start($f);$f[5]->now+=60_000;$f[0]->acceptChunk($f[1],$id,chunk($f,$id));$f[0]->stop($f[1],$id,2,60_000);error('INVALID_MANIFEST',fn()=>$f[0]->finalize($f[1],$id));same($f[2]->load($f[1],$id)['job'],null); });
test('time gaps and invalid stream boundaries block finalization', function () { foreach ([['startMs'=>30001],['streamId'=>'stream_2','initializationSequence'=>0],['mimeType'=>'audio/mp4']] as $change) { $f=fixture();$id=start($f);$f[5]->now+=60_000;$f[0]->acceptChunk($f[1],$id,chunk($f,$id));$f[0]->acceptChunk($f[1],$id,chunk($f,$id,1,$change));$f[0]->stop($f[1],$id,2,60_000);error('INVALID_MANIFEST',fn()=>$f[0]->finalize($f[1],$id)); } });
test('a new stream preserves its own initialization chunk', function () { $f=fixture();$id=start($f);$f[5]->now+=60_000;$f[0]->acceptChunk($f[1],$id,chunk($f,$id));$f[0]->acceptChunk($f[1],$id,chunk($f,$id,1,['streamId'=>'stream_2','initializationSequence'=>1]));$f[0]->stop($f[1],$id,2,60_000);$f[0]->finalize($f[1],$id);same($f[2]->load($f[1],$id)['manifest']['containerHandling'],'ordered-remux-required'); });
test('three hour wall clock cap includes pauses and allows final draining after stop', function () { $f=fixture();$id=start($f);$f[5]->now+=10_800_001;error('DURATION_LIMIT',fn()=>$f[0]->acceptChunk($f[1],$id,chunk($f,$id)));$f[0]->stop($f[1],$id,1,30_000);same($f[2]->load($f[1],$id)['stoppedAt'],10_801_000);$f[0]->acceptChunk($f[1],$id,chunk($f,$id));$f[0]->finalize($f[1],$id); });
test('stop refuses audio duration longer than wall duration', function () { $f=fixture();$id=start($f);$f[5]->now+=1000;error('INVALID_MANIFEST',fn()=>$f[0]->stop($f[1],$id,1,1001)); });
test('consent revocation after upload blocks finalization', function () { $f=fixture();$id=start($f);$f[5]->now+=30000;$f[0]->acceptChunk($f[1],$id,chunk($f,$id));$f[0]->stop($f[1],$id,1,30000);$f[2]->consents['2:9:consent_1']['revokedAt']=$f[5]->now;error('CONSENT_REQUIRED',fn()=>$f[0]->finalize($f[1],$id)); });
test('claim creates bounded lease and rejects parallel unexpired claim', function () { $f=fixture();$id=queued($f);$j=$f[0]->claim($f[1],$id);same($j['attempt'],1);same($j['leaseExpiresAt'],$f[5]->now+60_000);error('JOB_BUSY',fn()=>$f[0]->claim($f[1],$id)); });
test('ASR stage requires independently verified actual media', function () { $f=fixture();$id=queued($f);$j=$f[0]->claim($f[1],$id);error('MEDIA_NOT_VERIFIED',fn()=>$f[0]->authorizeProcessing($f[1],$id,$j['leaseToken'])); });
test('late worker lease rejected after reclaim', function () { $f=fixture();[$id,$old]=running($f);$f[5]->now+=60_000;$j=$f[0]->claim($f[1],$id);error('JOB_LEASE_INVALID',fn()=>$f[0]->completeDraft($f[1],$id,$old,transcript()));same($j['attempt'],2); });
test('retry is bounded delayed sanitized and terminal at max attempts', function () { $f=fixture();[$id,$token]=running($f);$f[0]->failJob($f[1],$id,$token,'transient');error('RETRY_NOT_DUE',fn()=>$f[0]->claim($f[1],$id));$f[5]->now+=30000;$j=$f[0]->claim($f[1],$id);$f[0]->failJob($f[1],$id,$j['leaseToken'],'transient');$f[5]->now+=30000;$j=$f[0]->claim($f[1],$id);$f[0]->failJob($f[1],$id,$j['leaseToken'],'transient');$f[5]->now+=30000;error('JOB_EXHAUSTED',fn()=>$f[0]->claim($f[1],$id)); });
test('unknown error strings cannot become queue log content', function () { $f=fixture();[$id,$token]=running($f);error('INVALID_ERROR_CODE',fn()=>$f[0]->failJob($f[1],$id,$token,'private patient text')); });
test('withdraw revokes persisted receipt and cancels queued work', function () { $f=fixture();$id=queued($f);$f[0]->withdraw($f[1],$id);$s=$f[2]->load($f[1],$id);same($s['status'],'cancelled');same($s['job']['status'],'cancelled');same($f[2]->consents['2:9:consent_1']['status'],'revoked');error('SESSION_CANCELLED',fn()=>$f[0]->claim($f[1],$id)); });
test('withdraw still works when provider storage or consent expires', function () { $f=fixture();$id=queued($f);$f[4]->available=false;$f[5]->config=null;$f[2]->consents['2:9:consent_1']['expiresAt']=1;$f[0]->withdraw($f[1],$id);same($f[2]->load($f[1],$id)['status'],'cancelled'); });
test('withdraw wins over a late successful worker response', function () { $f=fixture();[$id,$token]=running($f);$f[0]->withdraw($f[1],$id);error('SESSION_CANCELLED',fn()=>$f[0]->completeDraft($f[1],$id,$token,transcript()));same($f[2]->load($f[1],$id)['drafts'],[]); });
test('ASR identity stays unknown and overlap uncertainty raw sources are preserved', function () { $f=fixture();[$id,$token]=running($f);$d=$f[0]->completeDraft($f[1],$id,$token,transcript());same($d['segments'][0]['speaker']['role'],'unknown');same($d['segments'][1]['speaker']['role'],'unknown');same($d['segments'][1]['startMs'],400);same($d['segments'][0]['rawText'],'synthetic words');same($d['segments'][0]['uncertainty'],0.3);same($d['reviewStatus'],'unreviewed');same($d['classification'],null);same($d['provider']['version'],'1.2.3'); });
test('draft completion is idempotent but conflicting replay rejected', function () { $f=fixture();[$id,$token]=running($f);$d=$f[0]->completeDraft($f[1],$id,$token,transcript());same($f[0]->completeDraft($f[1],$id,$token,transcript()),$d);$t=transcript();$t['segments'][0]['text']='changed';error('DRAFT_CONFLICT',fn()=>$f[0]->completeDraft($f[1],$id,$token,$t));same(count($f[2]->load($f[1],$id)['drafts']),1); });
test('invalid transcript timestamps or source intervals rejected', function () { foreach (['endMs'=>60_001,'startMs'=>-1,'speakerId'=>'bad id','uncertainty'=>1.1,'sourceIntervals'=>[['startMs'=>0,'endMs'=>60_001]]] as $k=>$v) { $f=fixture();[$id,$token]=running($f);$t=transcript();$t['segments'][0][$k]=$v;error('INVALID_TRANSCRIPT',fn()=>$f[0]->completeDraft($f[1],$id,$token,$t)); } });
test('manual edit appends immutable draft version with actor time and raw text', function () { $f=fixture();[$id,$token]=running($f);$d=$f[0]->completeDraft($f[1],$id,$token,transcript());$f[5]->now++;$e=$f[0]->editDraft($f[1],$id,1,'turn_1',['text'=>'corrected']);same($e['version'],2);same($e['segments'][0]['rawText'],'synthetic words');same($e['segments'][0]['text'],'corrected');same($e['history'][0]['actorId'],'4');same($e['history'][0]['at'],$f[5]->now);same($f[2]->load($f[1],$id)['drafts'][0],$d);error('VERSION_CONFLICT',fn()=>$f[0]->editDraft($f[1],$id,1,'turn_1',['text'=>'stale'])); });
test('manual speaker assignment never approves clinical publication', function () { $f=fixture();[$id,$token]=running($f);$f[0]->completeDraft($f[1],$id,$token,transcript());$d=$f[0]->editDraft($f[1],$id,1,'turn_1',['speaker'=>['role'=>'doctor','name'=>'Synthetic clinician']]);same($d['segments'][0]['speaker']['source'],'manual');same($d['reviewStatus'],'unreviewed');same($d['status'],'draft');same($d['classification'],null); });
test('edit requires separate explicit permission and rejects arbitrary changes', function () { $f=fixture();[$id,$token]=running($f);$f[0]->completeDraft($f[1],$id,$token,transcript());$f[3]->denyPermission='recording.edit';error('ACCESS_DENIED',fn()=>$f[0]->editDraft($f[1],$id,1,'turn_1',['text'=>'x']));$f[3]->denyPermission=null;error('INVALID_EDIT',fn()=>$f[0]->editDraft($f[1],$id,1,'turn_1',['reviewStatus'=>'approved'])); });
test('persistence failure cannot acknowledge a created session', function () { $f=fixture();$f[2]->failCommit=true;try { newSession($f);throw new LogicException('expected persistence error'); } catch (RuntimeException $e) { same($e->getMessage(),'synthetic persistence failure'); }same(count($f[2]->sessions),0); });
test('queue transaction failure leaves no partially finalized session', function () { $f=fixture();$id=start($f);$f[5]->now+=30000;$f[0]->acceptChunk($f[1],$id,chunk($f,$id));$f[0]->stop($f[1],$id,1,30000);$f[2]->failCommit=true;try { $f[0]->finalize($f[1],$id);throw new LogicException('expected persistence error'); }catch(RuntimeException $e) {same($e->getMessage(),'synthetic persistence failure');}same($f[2]->load($f[1],$id)['manifest'],null);same($f[2]->load($f[1],$id)['job'],null); });

test('pre-ASR validation can obtain scoped media references without a media attestation', function () { $f=fixture();$id=queued($f);$job=$f[0]->claim($f[1],$id);$stage=$f[0]->authorizeMediaValidation($f[1],$id,$job['leaseToken']);same(count($stage['manifest']['chunks']),2);same($stage['stage'],'media_validation'); });
test('renewing lease rechecks consent and maintains the current attempt', function () { $f=fixture();[$id,$token]=running($f);$f[5]->now+=1000;$job=$f[0]->renewLease($f[1],$id,$token);same($job['leaseExpiresAt'],$f[5]->now+60_000);same($job['attempt'],1);$f[2]->consents['2:9:consent_1']['status']='revoked';error('CONSENT_REQUIRED',fn()=>$f[0]->renewLease($f[1],$id,$token)); });
test('lease expiring during media inspection cannot publish a draft', function () { $f=fixture();[$id,$token]=running($f);$f[4]->onMedia=function()use($f){$f[5]->now+=60_000;};error('JOB_LEASE_INVALID',fn()=>$f[0]->completeDraft($f[1],$id,$token,transcript()));same($f[2]->load($f[1],$id)['drafts'],[]); });
test('revocation and expiry during storage verification cannot acknowledge bytes', function () { foreach (['revoke','expire'] as $what) { $f=fixture();$id=start($f);$f[5]->now+=30000;$c=chunk($f,$id);$f[4]->onChunk=function()use($f,$what){if($what==='revoke'){$f[2]->consents['2:9:consent_1']['revokedAt']=$f[5]->now;}else{$f[5]->now=20_000_000;}};error($what==='revoke'?'CONSENT_REQUIRED':'ACCESS_DENIED',fn()=>$f[0]->acceptChunk($f[1],$id,$c));same($f[2]->load($f[1],$id)['chunks'],[]); } });
test('session byte quota admits exactly 128 MiB then rejects excess', function () { $f=fixture();$id=start($f);$f[5]->now+=65*30000;for($i=0;$i<64;$i++){$f[0]->acceptChunk($f[1],$id,chunk($f,$id,$i,['byteLength'=>2097152]));}error('SESSION_SIZE_LIMIT',fn()=>$f[0]->acceptChunk($f[1],$id,chunk($f,$id,64,['byteLength'=>1]))); });
test('360 synthetic thirty second chunks finalize a three hour manifest', function () { $f=fixture();$id=start($f);$f[5]->now+=10_800_000;$f[0]->stop($f[1],$id,360,10_800_000);for($i=359;$i>=0;$i--){$f[0]->acceptChunk($f[1],$id,chunk($f,$id,$i));}$f[0]->finalize($f[1],$id);same($f[2]->load($f[1],$id)['manifest']['audioDurationMs'],10_800_000); });
test('provider configuration cannot change under a queued job', function () { $f=fixture();$id=queued($f);$f[5]->config['version']='2.0.0';error('TRANSCRIPTION_UNCONFIGURED',fn()=>$f[0]->claim($f[1],$id)); });
test('expanded draft has a total encoded size bound', function () { $f=fixture();[$id,$token]=running($f);$t=['language'=>'fa','segments'=>[]];for($i=0;$i<45;$i++){$t['segments'][]=['id'=>'turn_'.$i,'startMs'=>$i,'endMs'=>$i+1,'text'=>str_repeat('a',15000),'speakerId'=>null,'uncertainty'=>null,'sourceIntervals'=>[['startMs'=>$i,'endMs'=>$i+1]]];}error('INVALID_TRANSCRIPT',fn()=>$f[0]->completeDraft($f[1],$id,$token,$t)); });

test('malformed publication flags in a persisted job fail closed', function () { $f=fixture();$id=queued($f);$key='2:9:'.$id;$f[2]->sessions[$key]['job']['descriptor']['autoWriteEmr']=true;$f[2]->sessions[$key]['job']['descriptor']['outputStatus']='published';error('INVALID_STORED_STATE',fn()=>$f[0]->finalize($f[1],$id)); });
test('immutable manifest must still match its stored digest', function () { $f=fixture();[$id,$token]=running($f);$f[2]->sessions['2:9:'.$id]['manifest']['chunks'][0]['privateObjectId']='foreign_tenant_object';error('INVALID_STORED_STATE',fn()=>$f[0]->authorizeProcessing($f[1],$id,$token)); });
test('unknown persisted job states cannot be claimed', function () { $f=fixture();$id=queued($f);$f[2]->sessions['2:9:'.$id]['job']['status']='published';error('INVALID_STORED_STATE',fn()=>$f[0]->claim($f[1],$id)); });
test('malformed persisted draft cannot bypass unreviewed status', function () { $f=fixture();[$id,$token]=running($f);$f[0]->completeDraft($f[1],$id,$token,transcript());$f[2]->sessions['2:9:'.$id]['drafts'][0]['reviewStatus']='approved';error('INVALID_STORED_STATE',fn()=>$f[0]->completeDraft($f[1],$id,$token,transcript())); });
test('create rechecks expiry after server identifier generation', function () { $f=fixture();$f[2]->consents['2:9:consent_1']['expiresAt']=1001;$f[5]->onId=function()use($f){$f[5]->now=1001;};error('CONSENT_REQUIRED',fn()=>newSession($f));same($f[2]->sessions,[]); });

test('provider requires pinned model provenance and records it on drafts', function () { $f=fixture();unset($f[5]->config['model']);error('TRANSCRIPTION_UNCONFIGURED',fn()=>newSession($f));$f=fixture();[$id,$token]=running($f);$d=$f[0]->completeDraft($f[1],$id,$token,transcript());same($d['provider']['model'],'synthetic-model');same($d['provider']['revision'],'synthetic-r1'); });
test('configuration withdrawn during storage inspection cannot acknowledge bytes', function () { $f=fixture();$id=start($f);$f[5]->now+=30000;$c=chunk($f,$id);$f[4]->onChunk=function()use($f){$f[5]->config=null;};error('TRANSCRIPTION_UNCONFIGURED',fn()=>$f[0]->acceptChunk($f[1],$id,$c));same($f[2]->load($f[1],$id)['chunks'],[]); });

test('saved draft and segment envelopes cannot add publication metadata', function () { foreach(['draft','segment'] as $where){$f=fixture();[$id,$token]=running($f);$f[0]->completeDraft($f[1],$id,$token,transcript());if($where==='draft'){$f[2]->sessions['2:9:'.$id]['drafts'][0]['autoWriteEmr']=true;}else{$f[2]->sessions['2:9:'.$id]['drafts'][0]['segments'][0]['clinicalRecordId']='foreign';}error('INVALID_STORED_STATE',fn()=>$f[0]->completeDraft($f[1],$id,$token,transcript()));} });
test('saved raw transcript remains bound to the completed result digest', function () { $f=fixture();[$id,$token]=running($f);$f[0]->completeDraft($f[1],$id,$token,transcript());$f[2]->sessions['2:9:'.$id]['drafts'][0]['segments'][0]['rawText']='tampered original';error('INVALID_STORED_STATE',fn()=>$f[0]->completeDraft($f[1],$id,$token,transcript())); });
test('later draft versions preserve immutable source fields and earlier history', function () { $f=fixture();[$id,$token]=running($f);$f[0]->completeDraft($f[1],$id,$token,transcript());$f[0]->editDraft($f[1],$id,1,'turn_1',['text'=>'corrected']);$f[2]->sessions['2:9:'.$id]['drafts'][1]['segments'][0]['rawText']='changed raw';error('INVALID_STORED_STATE',fn()=>$f[0]->editDraft($f[1],$id,2,'turn_1',['text'=>'next'])); });

// Synthetic interchange export, invoked only by the optional offline test runner.
if (defined('RECORDING_EMIT_INTERCHANGE')) {
    $f = fixture(); [$sessionId, $leaseToken] = running($f);
    $draft = $f[0]->completeDraft($f[1], $sessionId, $leaseToken, transcript());
    $session = $f[2]->load($f[1], $sessionId);
    echo json_encode(['scope' => $f[1], 'consent' => $f[2]->consents['2:9:consent_1'],
        'startedAt' => $session['startedAt'], 'stoppedAt' => $session['stoppedAt'],
        'receipts' => array_values(array_map(static fn(array $row): array => $row['receipt'], $session['chunks'])),
        'manifest' => $session['manifest'], 'job' => $session['job']['descriptor'], 'draft' => $draft], JSON_THROW_ON_ERROR);
    return;
}

$failed = 0;
foreach ($tests as $name => $run) { try { $run(); echo 'PASS '.$name."\n"; } catch (Throwable $e) { $failed++; echo 'FAIL '.$name.': '.$e->getMessage()."\n"; } }
echo count($tests).' tests, '.$failed." failed\n";
exit($failed ? 1 : 0);
