<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class BookingBlacklistService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conn();
    }

    public function normalize(string $identifier): string
    {
        $identifier = strtoupper(trim($identifier));
        $identifier = strtr($identifier, ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
        return preg_replace('/[^A-Z0-9]/', '', $identifier) ?? '';
    }

    public function normalizeMobile(string $mobile): string
    {
        $m=$this->normalize($mobile);
        if (str_starts_with($m,'0098')) $m='0'.substr($m,4);
        elseif (str_starts_with($m,'98') && strlen($m)===12) $m='0'.substr($m,2);
        elseif (str_starts_with($m,'9') && strlen($m)===10) $m='0'.$m;
        return preg_match('/^09\d{9}$/',$m) ? $m : '';
    }

    public function isBlocked(int $clinicId, string $identifier): bool
    {
        $id=$this->normalize($identifier);
        if ($id==='') return false;
        $q=$this->db->prepare('SELECT 1 FROM booking_blacklist WHERE clinic_id=? AND identifier_hash=? LIMIT 1');
        $q->execute([$clinicId,hash('sha256',$id)]);
        return (bool)$q->fetchColumn();
    }

    public function isBlockedAny(int $clinicId, ?string $identifier, ?string $mobile): bool
    {
        if ($identifier !== null && $identifier !== '' && $this->isBlocked($clinicId,$identifier)) return true;
        $m=$this->normalizeMobile((string)$mobile);
        return $m !== '' && $this->isBlocked($clinicId,$m);
    }

    public function isPatientBlocked(int $clinicId, int $patientId): bool
    {
        if ($patientId < 1) return false;
        $q=$this->db->prepare('SELECT 1 FROM booking_blacklist WHERE clinic_id=? AND patient_id=? LIMIT 1');
        $q->execute([$clinicId,$patientId]);
        if ($q->fetchColumn()) return true;
        $q=$this->db->prepare('SELECT national_id,mobile FROM patients WHERE id=? AND clinic_id=? AND deleted_at IS NULL LIMIT 1');
        $q->execute([$patientId,$clinicId]);
        $p=$q->fetch();
        return $p ? $this->isBlockedAny($clinicId,(string)($p['national_id']??''),(string)($p['mobile']??'')) : false;
    }

    /**
     * @param array<int,array<string,mixed>> $patients
     * @return array<int,bool>
     */
    public function blockedPatientIds(int $clinicId, array $patients): array
    {
        $patientIds=[];
        $hashToPatientIds=[];
        foreach ($patients as $row) {
            $pid=(int)($row['id']??0);
            if ($pid<1) continue;
            $patientIds[$pid]=true;
            foreach ([(string)($row['national_id']??''),$this->normalizeMobile((string)($row['mobile']??''))] as $identifier) {
                $normalized=$this->normalize($identifier);
                if ($normalized==='') continue;
                $hash=hash('sha256',$normalized);
                $hashToPatientIds[$hash][$pid]=true;
            }
        }
        if (!$patientIds) return [];

        $where=[];$params=[$clinicId];
        $ids=array_keys($patientIds);
        $where[]='patient_id IN ('.implode(',',array_fill(0,count($ids),'?')).')';
        array_push($params,...$ids);
        $hashes=array_keys($hashToPatientIds);
        if ($hashes) {
            $where[]='identifier_hash IN ('.implode(',',array_fill(0,count($hashes),'?')).')';
            array_push($params,...$hashes);
        }
        $q=$this->db->prepare(
            'SELECT patient_id,identifier_hash FROM booking_blacklist WHERE clinic_id=? AND ('.implode(' OR ',$where).')'
        );
        $q->execute($params);

        $blocked=[];
        foreach ($q->fetchAll() as $row) {
            $pid=(int)($row['patient_id']??0);
            if ($pid>0) $blocked[$pid]=true;
            $hash=(string)($row['identifier_hash']??'');
            foreach (array_keys($hashToPatientIds[$hash]??[]) as $matchedId) $blocked[(int)$matchedId]=true;
        }
        return $blocked;
    }

    public function isIntakeBlocked(int $clinicId, int $intakeId, int $patientId = 0): bool
    {
        if ($patientId > 0 && $this->isPatientBlocked($clinicId,$patientId)) return true;
        if ($intakeId < 1) return false;
        $q=$this->db->prepare(
          'SELECT patient_id,national_id,mobile,raw_payload FROM intakes
           WHERE id=? AND clinic_id=? AND source_type="booking" AND deleted_at IS NULL LIMIT 1'
        );
        $q->execute([$intakeId,$clinicId]);
        $row=$q->fetch();
        if (!$row) return false;
        $linkedPatient=(int)($row['patient_id'] ?? 0);
        if ($linkedPatient > 0 && $linkedPatient !== $patientId && $this->isPatientBlocked($clinicId,$linkedPatient)) return true;
        if ($this->isBlockedAny($clinicId,(string)($row['national_id']??''),(string)($row['mobile']??''))) return true;
        $raw=json_decode((string)($row['raw_payload'] ?? ''),true);
        if (is_array($raw)) {
            $passport=(string)($raw['passport_number'] ?? $raw['passportNumber'] ?? '');
            if ($passport !== '' && $this->isBlocked($clinicId,$passport)) return true;
        }
        return false;
    }

    public function isBookingBlocked(int $clinicId, int $patientId, int $bookingId): bool
    {
        if ($this->isPatientBlocked($clinicId,$patientId)) return true;
        if ($bookingId < 1) return false;
        $q=$this->db->prepare(
          'SELECT intake_id FROM appointment_booking_requests
           WHERE id=? AND clinic_id=? AND patient_id=? LIMIT 1'
        );
        $q->execute([$bookingId,$clinicId,$patientId]);
        $intakeId=(int)($q->fetchColumn() ?: 0);
        return $intakeId > 0 && $this->isIntakeBlocked($clinicId,$intakeId,$patientId);
    }

    public function add(int $clinicId, string $identifier, string $type, ?int $patientId, string $name, string $reason, ?int $staffId, string $source='manual'): int
    {
        $type=in_array($type,['national_id','passport','mobile'],true)?$type:'national_id';
        $id=$type==='mobile' ? $this->normalizeMobile($identifier) : $this->normalize($identifier);
        $min=$type==='mobile'?11:6;
        if (strlen($id)<$min || strlen($id)>32) throw new \InvalidArgumentException($type==='mobile'?'شماره موبایل معتبر نیست.':'شناسه معتبر نیست.');
        $stmt=$this->db->prepare(
          'INSERT INTO booking_blacklist (clinic_id,patient_id,identifier_type,identifier_hash,identifier_last4,patient_name,reason,source,created_by,created_at)
           VALUES (?,?,?,?,?,?,?,?,?,UTC_TIMESTAMP())
           ON DUPLICATE KEY UPDATE patient_id=COALESCE(VALUES(patient_id),patient_id),patient_name=IF(VALUES(patient_name)<>"",VALUES(patient_name),patient_name),reason=IF(VALUES(reason)<>"",VALUES(reason),reason),source=VALUES(source),created_by=VALUES(created_by)'
        );
        $stmt->execute([$clinicId,$patientId,$type,hash('sha256',$id),substr($id,-4),mb_substr(trim($name),0,200),mb_substr(trim($reason),0,500),mb_substr(trim($source),0,30),$staffId]);
        $newId=(int)$this->db->lastInsertId();
        if($newId>0) return $newId;
        $q=$this->db->prepare('SELECT id FROM booking_blacklist WHERE clinic_id=? AND identifier_hash=? LIMIT 1');
        $q->execute([$clinicId,hash('sha256',$id)]);
        return (int)($q->fetchColumn()?:0);
    }

    public function addPatient(int $clinicId, int $patientId, string $reason, ?int $staffId): void
    {
        $q=$this->db->prepare('SELECT first_name,last_name,national_id,mobile FROM patients WHERE id=? AND clinic_id=? AND deleted_at IS NULL LIMIT 1');
        $q->execute([$patientId,$clinicId]);
        $p=$q->fetch();
        if (!$p) throw new \RuntimeException('بیمار یافت نشد.');
        $name=trim((string)$p['first_name'].' '.(string)$p['last_name']);
        $added=0;
        if (!empty($p['national_id'])) { $this->add($clinicId,(string)$p['national_id'],'national_id',$patientId,$name,$reason,$staffId,'patient_list'); $added++; }
        if ($this->normalizeMobile((string)($p['mobile']??''))!=='') { $this->add($clinicId,(string)$p['mobile'],'mobile',$patientId,$name,$reason,$staffId,'patient_list'); $added++; }
        if ($added===0) throw new \InvalidArgumentException('برای این بیمار کد ملی یا شماره موبایل معتبر ثبت نشده است.');
    }

    public function all(int $clinicId): array
    {
        $q=$this->db->prepare('SELECT id,patient_id,identifier_type,identifier_last4,patient_name,reason,source,created_at FROM booking_blacklist WHERE clinic_id=? ORDER BY id DESC');
        $q->execute([$clinicId]);
        return $q->fetchAll() ?: [];
    }

    public function remove(int $clinicId,int $id): bool
    {
        $q=$this->db->prepare('DELETE FROM booking_blacklist WHERE id=? AND clinic_id=?');
        $q->execute([$id,$clinicId]);
        return $q->rowCount()>0;
    }

    public function removePatient(int $clinicId,int $patientId): int
    {
        $q=$this->db->prepare('DELETE FROM booking_blacklist WHERE clinic_id=? AND patient_id=?');
        $q->execute([$clinicId,$patientId]);
        return $q->rowCount();
    }

}