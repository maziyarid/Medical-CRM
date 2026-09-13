<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Validators\ValidatorService;
use RuntimeException;

final class StaffAccountService
{
    private const ROLES = ['super_admin','doctor','receptionist','nurse'];

    /** @return array<int,array<string,mixed>> */
    public function list(int $clinicId): array
    {
        (new StaffSchemaBootstrapService())->ensure();
        $stmt=Database::conn()->prepare(
            'SELECT u.id,u.uuid,u.full_name,u.mobile,u.email,u.is_active,u.invited_at,u.activated_at,u.last_login_at,u.created_at,
                    GROUP_CONCAT(r.name ORDER BY r.id SEPARATOR ",") roles
             FROM users u
             LEFT JOIN role_user ru ON ru.user_id=u.id
             LEFT JOIN roles r ON r.id=ru.role_id
             WHERE u.clinic_id=? AND u.deleted_at IS NULL
             GROUP BY u.id,u.uuid,u.full_name,u.mobile,u.email,u.is_active,u.invited_at,u.activated_at,u.last_login_at,u.created_at
             ORDER BY u.created_at DESC'
        );
        $stmt->execute([$clinicId]);
        $rows=$stmt->fetchAll();
        foreach($rows as &$row){
            $row['id']=(int)$row['id'];
            $row['is_active']=(bool)$row['is_active'];
            $row['roles']=array_values(array_filter(array_map('trim',explode(',',(string)($row['roles']??'')))));
            $row['activation_status']=$row['activated_at'] ? 'active' : 'invited';
        }
        unset($row);
        return $rows;
    }

    /** @return array<string,mixed> */
    public function create(int $clinicId,int $invitedBy,array $input): array
    {
        (new StaffSchemaBootstrapService())->ensure();
        $name=trim((string)($input['full_name']??$input['name']??''));
        $mobile=ValidatorService::normalizeMobile(trim((string)($input['mobile']??'')));
        $email=strtolower(trim((string)($input['email']??'')));
        $role=strtolower(trim((string)($input['role']??'')));
        if (mb_strlen($name)<2 || mb_strlen($name)>200) throw new RuntimeException('invalid staff name');
        if (!ValidatorService::isValidMobile($mobile)) throw new RuntimeException('invalid staff mobile');
        if ($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('invalid staff email');
        if (!in_array($role,self::ROLES,true)) throw new RuntimeException('invalid staff role');

        $db=Database::conn();
        $db->beginTransaction();
        try {
            $q=$db->prepare('SELECT id FROM users WHERE mobile=? AND deleted_at IS NULL LIMIT 1 FOR UPDATE');
            $q->execute([$mobile]);
            if ($q->fetchColumn()) throw new RuntimeException('staff mobile already registered');
            $roleStmt=$db->prepare('SELECT id FROM roles WHERE name=? LIMIT 1');
            $roleStmt->execute([$role]);
            $roleId=(int)$roleStmt->fetchColumn();
            if ($roleId<1) throw new RuntimeException('staff role is not configured');
            $uuid=bin2hex(random_bytes(16));
            $db->prepare(
                'INSERT INTO users (uuid,clinic_id,full_name,mobile,email,password_hash,invited_by,invited_at,is_active,created_at,updated_at)
                 VALUES (?,?,?,?,NULLIF(?,""),NULL,?,UTC_TIMESTAMP(),1,UTC_TIMESTAMP(),UTC_TIMESTAMP())'
            )->execute([$uuid,$clinicId,$name,$mobile,$email,$invitedBy]);
            $id=(int)$db->lastInsertId();
            $db->prepare('INSERT INTO role_user (user_id,role_id,granted_at) VALUES (?,?,UTC_TIMESTAMP())')->execute([$id,$roleId]);
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
        $notification=$this->notify($id,false);
        return ['id'=>$id,'uuid'=>$uuid,'full_name'=>$name,'mobile'=>$mobile,'email'=>$email ?: null,'role'=>$role,'activation_status'=>'invited','notification'=>$notification];
    }

    /** @return array<string,mixed> */
    public function update(int $clinicId,int $actorId,int $userId,array $input): array
    {
        (new StaffSchemaBootstrapService())->ensure();
        $db=Database::conn();
        $db->beginTransaction();
        try {
            $q=$db->prepare('SELECT * FROM users WHERE id=? AND clinic_id=? AND deleted_at IS NULL FOR UPDATE');
            $q->execute([$userId,$clinicId]);
            $user=$q->fetch();
            if (!$user) throw new RuntimeException('staff user not found');

            $roles=$this->rolesForUser($db,$userId);
            $isSuper=in_array('super_admin',$roles,true);
            $name=array_key_exists('full_name',$input)?trim((string)$input['full_name']):(string)$user['full_name'];
            $email=array_key_exists('email',$input)?strtolower(trim((string)$input['email'])):(string)($user['email']??'');
            $active=array_key_exists('is_active',$input)?(bool)$input['is_active']:(bool)$user['is_active'];
            $role=array_key_exists('role',$input)?strtolower(trim((string)$input['role'])):($roles[0]??'');
            if (mb_strlen($name)<2 || mb_strlen($name)>200) throw new RuntimeException('invalid staff name');
            if ($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('invalid staff email');
            if (!in_array($role,self::ROLES,true)) throw new RuntimeException('invalid staff role');

            $removesSuper=$isSuper && $role!=='super_admin';
            $deactivatesSuper=$isSuper && !$active;
            if ($removesSuper || $deactivatesSuper) {
                $count=$this->activeSuperAdminCount($db,$clinicId);
                if ($count<=1) throw new RuntimeException('cannot remove the last super admin');
            }
            if ($actorId===$userId && !$active) throw new RuntimeException('cannot deactivate your own account');

            $db->prepare('UPDATE users SET full_name=?,email=NULLIF(?,""),is_active=?,updated_at=UTC_TIMESTAMP() WHERE id=?')
                ->execute([$name,$email,$active?1:0,$userId]);
            if (($roles[0]??'')!==$role || count($roles)!==1) {
                $r=$db->prepare('SELECT id FROM roles WHERE name=? LIMIT 1'); $r->execute([$role]); $rid=(int)$r->fetchColumn();
                if ($rid<1) throw new RuntimeException('staff role is not configured');
                $db->prepare('DELETE FROM role_user WHERE user_id=?')->execute([$userId]);
                $db->prepare('INSERT INTO role_user (user_id,role_id,granted_at) VALUES (?,?,UTC_TIMESTAMP())')->execute([$userId,$rid]);
            }
            if (!$active) (new OtpService())->revokeAllSessions($userId,'staff');
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
        return ['id'=>$userId,'updated'=>true,'role'=>$role,'is_active'=>$active];
    }

    /** @return array<string,mixed> */
    public function resendInvite(int $clinicId,int $userId): array
    {
        (new StaffSchemaBootstrapService())->ensure();
        $q=Database::conn()->prepare('SELECT id FROM users WHERE id=? AND clinic_id=? AND is_active=1 AND deleted_at IS NULL LIMIT 1');
        $q->execute([$userId,$clinicId]);
        if (!$q->fetchColumn()) throw new RuntimeException('staff user not found');
        return $this->notify($userId,true);
    }

    /** @return array<string,mixed> */
    public function bootstrapFirstSuperAdmin(int $clinicId,string $name,string $mobile,string $email=''): array
    {
        (new StaffSchemaBootstrapService())->ensure();
        $db=Database::conn();
        $db->beginTransaction();
        try {
            if ($this->activeSuperAdminCount($db,$clinicId)>0) throw new RuntimeException('super admin already exists');
            $name=trim($name); $mobile=ValidatorService::normalizeMobile($mobile); $email=strtolower(trim($email));
            if (mb_strlen($name)<2 || mb_strlen($name)>200) throw new RuntimeException('invalid staff name');
            if (!ValidatorService::isValidMobile($mobile)) throw new RuntimeException('invalid staff mobile');
            if ($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('invalid staff email');
            $exists=$db->prepare('SELECT id FROM users WHERE mobile=? AND deleted_at IS NULL LIMIT 1 FOR UPDATE'); $exists->execute([$mobile]);
            if ($exists->fetchColumn()) throw new RuntimeException('staff mobile already registered');
            $r=$db->query("SELECT id FROM roles WHERE name='super_admin' LIMIT 1"); $rid=(int)$r->fetchColumn();
            if ($rid<1) throw new RuntimeException('super admin role is not configured');
            $uuid=bin2hex(random_bytes(16));
            $db->prepare('INSERT INTO users (uuid,clinic_id,full_name,mobile,email,invited_at,activated_at,is_active,created_at,updated_at) VALUES (?,?,?,?,NULLIF(?,""),UTC_TIMESTAMP(),UTC_TIMESTAMP(),1,UTC_TIMESTAMP(),UTC_TIMESTAMP())')
                ->execute([$uuid,$clinicId,$name,$mobile,$email]);
            $id=(int)$db->lastInsertId();
            $db->prepare('INSERT INTO role_user (user_id,role_id,granted_at) VALUES (?,?,UTC_TIMESTAMP())')->execute([$id,$rid]);
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
        $notification=$this->notify($id,false);
        return ['id'=>$id,'uuid'=>$uuid,'role'=>'super_admin','notification'=>$notification];
    }

    /** @return array{sms:string,email:string} */
    private function notify(int $userId,bool $resend): array
    {
        $db=Database::conn();
        $q=$db->prepare('SELECT full_name,mobile,email FROM users WHERE id=? LIMIT 1'); $q->execute([$userId]); $u=$q->fetch();
        if (!$u) return ['sms'=>'failed','email'=>'skipped'];
        $login=trim((string)($_ENV['STAFF_LOGIN_URL']??'https://app.drbastaninejad.com/Frontend/pages/auth/login.html'));
        $text='حساب کارکنان کلینیک دکتر شاهین باستانی‌نژاد برای شما '.($resend?'فعال است':'ایجاد شد').'. برای ورود با شماره همراه و دریافت کد یکبارمصرف: '.$login.' پس از ورود می‌توانید رمز عبور تعیین کنید.';
        $sms=(new SmsProviderChain())->sendMessage((string)$u['mobile'],mb_substr($text,0,800));
        $smsStatus=!empty($sms['ok'])?'sent':'failed';
        $emailStatus='skipped';
        if (!empty($u['email'])) {
            $emailStatus=(new EmailService())->sendStaffInvitation((string)$u['email'],(string)$u['full_name'],$login)?'sent':'failed';
        }
        return ['sms'=>$smsStatus,'email'=>$emailStatus];
    }

    /** @return list<string> */
    private function rolesForUser(\PDO $db,int $userId): array
    {
        $q=$db->prepare('SELECT r.name FROM role_user ru JOIN roles r ON r.id=ru.role_id WHERE ru.user_id=? ORDER BY r.id');
        $q->execute([$userId]);
        return array_values(array_map('strval',$q->fetchAll(\PDO::FETCH_COLUMN)));
    }

    private function activeSuperAdminCount(\PDO $db,int $clinicId): int
    {
        $q=$db->prepare("SELECT COUNT(DISTINCT u.id) FROM users u JOIN role_user ru ON ru.user_id=u.id JOIN roles r ON r.id=ru.role_id WHERE u.clinic_id=? AND u.is_active=1 AND u.deleted_at IS NULL AND r.name='super_admin'");
        $q->execute([$clinicId]);
        return (int)$q->fetchColumn();
    }
}
