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
        $this->ensureSchema();
    }

    public function normalize(string $identifier): string
    {
        $identifier = strtoupper(trim($identifier));
        $identifier = strtr($identifier, ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9']);
        return preg_replace('/[^A-Z0-9]/', '', $identifier) ?? '';
    }

    public function isBlocked(int $clinicId, string $identifier): bool
    {
        $id=$this->normalize($identifier);
        if ($id==='') return false;
        $q=$this->db->prepare('SELECT 1 FROM booking_blacklist WHERE clinic_id=? AND identifier_hash=? LIMIT 1');
        $q->execute([$clinicId,hash('sha256',$id)]);
        return (bool)$q->fetchColumn();
    }

    public function isPatientBlocked(int $clinicId, int $patientId): bool
    {
        if ($patientId < 1) return false;
        $q=$this->db->prepare('SELECT 1 FROM booking_blacklist WHERE clinic_id=? AND patient_id=? LIMIT 1');
        $q->execute([$clinicId,$patientId]);
        if ($q->fetchColumn()) return true;
        $q=$this->db->prepare('SELECT national_id FROM patients WHERE id=? AND clinic_id=? AND deleted_at IS NULL LIMIT 1');
        $q->execute([$patientId,$clinicId]);
        $nationalId=(string)($q->fetchColumn() ?: '');
        return $nationalId !== '' && $this->isBlocked($clinicId,$nationalId);
    }

    public function isIntakeBlocked(int $clinicId, int $intakeId, int $patientId = 0): bool
    {
        if ($patientId > 0 && $this->isPatientBlocked($clinicId,$patientId)) return true;
        if ($intakeId < 1) return false;
        $q=$this->db->prepare(
          'SELECT patient_id,national_id,raw_payload FROM intakes
           WHERE id=? AND clinic_id=? AND source_type="booking" AND deleted_at IS NULL LIMIT 1'
        );
        $q->execute([$intakeId,$clinicId]);
        $row=$q->fetch();
        if (!$row) return false;
        $linkedPatient=(int)($row['patient_id'] ?? 0);
        if ($linkedPatient > 0 && $linkedPatient !== $patientId && $this->isPatientBlocked($clinicId,$linkedPatient)) return true;
        $nationalId=(string)($row['national_id'] ?? '');
        if ($nationalId !== '' && $this->isBlocked($clinicId,$nationalId)) return true;
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

    public function add(int $clinicId, string $identifier, string $type, ?int $patientId, string $name, string $reason, ?int $staffId): int
    {
        $id=$this->normalize($identifier);
        if (strlen($id)<6 || strlen($id)>32) throw new \InvalidArgumentException('شناسه معتبر نیست.');
        $type=$type==='passport'?'passport':'national_id';
        $stmt=$this->db->prepare(
          'INSERT INTO booking_blacklist (clinic_id,patient_id,identifier_type,identifier_hash,identifier_last4,patient_name,reason,created_by,created_at)
           VALUES (?,?,?,?,?,?,?,?,UTC_TIMESTAMP())
           ON DUPLICATE KEY UPDATE patient_id=VALUES(patient_id),patient_name=VALUES(patient_name),reason=VALUES(reason),created_by=VALUES(created_by)'
        );
        $stmt->execute([$clinicId,$patientId,$type,hash('sha256',$id),substr($id,-4),mb_substr(trim($name),0,200),mb_substr(trim($reason),0,500),$staffId]);
        return (int)$this->db->lastInsertId();
    }

    public function addPatient(int $clinicId, int $patientId, string $reason, ?int $staffId): void
    {
        $q=$this->db->prepare('SELECT first_name,last_name,national_id FROM patients WHERE id=? AND clinic_id=? AND deleted_at IS NULL LIMIT 1');
        $q->execute([$patientId,$clinicId]);
        $p=$q->fetch();
        if (!$p) throw new \RuntimeException('بیمار یافت نشد.');
        if (empty($p['national_id'])) throw new \InvalidArgumentException('برای این بیمار کد ملی ثبت نشده است.');
        $this->add($clinicId,(string)$p['national_id'],'national_id',$patientId,trim((string)$p['first_name'].' '.(string)$p['last_name']),$reason,$staffId);
    }

    public function all(int $clinicId): array
    {
        $q=$this->db->prepare('SELECT id,patient_id,identifier_type,identifier_last4,patient_name,reason,created_at FROM booking_blacklist WHERE clinic_id=? ORDER BY id DESC');
        $q->execute([$clinicId]);
        return $q->fetchAll() ?: [];
    }

    public function remove(int $clinicId,int $id): bool
    {
        $q=$this->db->prepare('DELETE FROM booking_blacklist WHERE id=? AND clinic_id=?');
        $q->execute([$id,$clinicId]);
        return $q->rowCount()>0;
    }

    private function ensureSchema(): void
    {
        $this->db->exec(
          'CREATE TABLE IF NOT EXISTS booking_blacklist (
             id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
             clinic_id BIGINT UNSIGNED NOT NULL,
             patient_id BIGINT UNSIGNED NULL,
             identifier_type VARCHAR(20) NOT NULL DEFAULT "national_id",
             identifier_hash CHAR(64) NOT NULL,
             identifier_last4 VARCHAR(4) NOT NULL,
             patient_name VARCHAR(200) NOT NULL DEFAULT "",
             reason VARCHAR(500) NOT NULL DEFAULT "",
             created_by BIGINT UNSIGNED NULL,
             created_at DATETIME NOT NULL,
             PRIMARY KEY (id),
             UNIQUE KEY uq_booking_blacklist_identifier (clinic_id,identifier_hash),
             KEY idx_booking_blacklist_patient (clinic_id,patient_id)
           ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }
}
