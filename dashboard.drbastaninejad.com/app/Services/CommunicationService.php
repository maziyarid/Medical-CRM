<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use RuntimeException;

final class CommunicationService
{
    private PDO $db;

    public function __construct()
    {
        (new CommunicationSchemaBootstrapService())->ensure();
        $this->db = Database::conn();
    }

    /** @return array<string,mixed> */
    public function settings(int $clinicId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM communication_settings WHERE clinic_id = ? LIMIT 1');
        $stmt->execute([$clinicId]);
        $row = $stmt->fetch();
        if (!$row) {
            (new CommunicationSchemaBootstrapService())->ensure();
            $stmt->execute([$clinicId]);
            $row = $stmt->fetch();
        }
        $row = $row ?: [];
        $row['auto_response_enabled'] = (bool)($row['auto_response_enabled'] ?? false);
        $row['preview_html'] = (new EmailService())->renderBrandTemplate(
            (string)($row['auto_response_subject'] ?? 'پیام شما دریافت شد'),
            (string)($row['auto_response_body'] ?? ''),
            $row,
            true,
            'مراجع'
        );
        return $row;
    }

    /** @return array<string,mixed> */
    public function updateSettings(int $clinicId, array $in): array
    {
        $allowed = [
            'from_name' => 190,
            'auto_response_subject' => 255,
            'auto_response_body' => 4000,
            'signature_text' => 2000,
            'booking_url' => 500,
            'footer_phone' => 255,
            'footer_address' => 500,
        ];
        $sets = [];
        $params = [];
        foreach ($allowed as $key => $max) {
            if (!array_key_exists($key, $in)) continue;
            $value = trim((string)$in[$key]);
            if (mb_strlen($value) > $max) throw new RuntimeException('مقدار ' . $key . ' بیش از حد طولانی است.');
            if ($key === 'booking_url' && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
                throw new RuntimeException('نشانی صفحه رزرو معتبر نیست.');
            }
            $sets[] = "{$key} = ?";
            $params[] = $value;
        }
        if (array_key_exists('auto_response_enabled', $in)) {
            $sets[] = 'auto_response_enabled = ?';
            $params[] = filter_var($in['auto_response_enabled'], FILTER_VALIDATE_BOOL) ? 1 : 0;
        }
        if (!$sets) throw new RuntimeException('تنظیمی برای ذخیره ارسال نشده است.');
        $sets[] = 'updated_at = UTC_TIMESTAMP()';
        $params[] = $clinicId;
        $this->db->prepare('UPDATE communication_settings SET ' . implode(', ', $sets) . ' WHERE clinic_id = ?')->execute($params);
        return $this->settings($clinicId);
    }

    /** @return array<string,mixed> */
    public function summary(int $clinicId): array
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) total,
                    SUM(CASE WHEN unread_count > 0 THEN 1 ELSE 0 END) unread_threads,
                    COALESCE(SUM(unread_count),0) unread_messages,
                    SUM(CASE WHEN status = "open" THEN 1 ELSE 0 END) open_threads,
                    SUM(CASE WHEN is_starred = 1 THEN 1 ELSE 0 END) starred_threads,
                    SUM(CASE WHEN source = "contact_form" THEN 1 ELSE 0 END) website_threads,
                    SUM(CASE WHEN source IN ("email","gmail_archive") THEN 1 ELSE 0 END) email_threads
             FROM communication_threads WHERE clinic_id = ?'
        );
        $stmt->execute([$clinicId]);
        $r = $stmt->fetch() ?: [];
        foreach (['total','unread_threads','unread_messages','open_threads','starred_threads','website_threads','email_threads'] as $k) {
            $r[$k] = (int)($r[$k] ?? 0);
        }
        return $r;
    }

    /** @return array<string,mixed> */
    public function threads(int $clinicId, array $filters = []): array
    {
        $where = ['t.clinic_id = ?'];
        $params = [$clinicId];
        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where[] = '(t.contact_name LIKE ? OR t.contact_email LIKE ? OR t.contact_phone LIKE ? OR t.subject LIKE ?)';
            array_push($params, $like, $like, $like, $like);
        }
        $status = trim((string)($filters['status'] ?? ''));
        if (in_array($status, ['open','closed','spam'], true)) {
            $where[] = 't.status = ?';
            $params[] = $status;
        }
        $source = trim((string)($filters['source'] ?? ''));
        if (in_array($source, ['contact_form','email','gmail_archive'], true)) {
            $where[] = 't.source = ?';
            $params[] = $source;
        }
        $category = trim((string)($filters['category'] ?? ''));
        if (in_array($category, ['contact','appointment','followup','system','other'], true)) {
            $where[] = 't.category = ?';
            $params[] = $category;
        }
        if (!empty($filters['unread'])) $where[] = 't.unread_count > 0';
        if (!empty($filters['starred'])) $where[] = 't.is_starred = 1';
        $labelId = (int)($filters['label_id'] ?? 0);
        if ($labelId > 0) {
            $where[] = 'EXISTS (
                SELECT 1 FROM communication_thread_labels tl
                JOIN communication_labels l ON l.id=tl.label_id
                WHERE tl.thread_id=t.id AND l.id=? AND l.clinic_id=t.clinic_id
            )';
            $params[] = $labelId;
        }

        $page = max(1, (int)($filters['page'] ?? 1));
        $perPage = max(10, min(100, (int)($filters['per_page'] ?? 30)));
        $offset = ($page - 1) * $perPage;

        $count = $this->db->prepare('SELECT COUNT(*) FROM communication_threads t WHERE ' . implode(' AND ', $where));
        $count->execute($params);
        $total = (int)$count->fetchColumn();

        $sql = 'SELECT t.*,
                       (SELECT m.body_text FROM communication_messages m WHERE m.thread_id=t.id ORDER BY m.id DESC LIMIT 1) last_body,
                       (SELECT m.direction FROM communication_messages m WHERE m.thread_id=t.id ORDER BY m.id DESC LIMIT 1) last_direction,
                       (SELECT GROUP_CONCAT(CONCAT(l.id,"|",REPLACE(l.name,"|",""),"|",l.color_key) SEPARATOR "~~")
                          FROM communication_thread_labels tl
                          JOIN communication_labels l ON l.id=tl.label_id
                         WHERE tl.thread_id=t.id) labels_raw
                FROM communication_threads t
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY t.last_message_at DESC, t.id DESC
                LIMIT ' . $perPage . ' OFFSET ' . $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) $row = $this->castThread($row);
        unset($row);

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'summary' => $this->summary($clinicId),
        ];
    }

    /** @return array<string,mixed> */
    public function thread(int $clinicId, int $threadId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM communication_threads WHERE id = ? AND clinic_id = ? LIMIT 1');
        $stmt->execute([$threadId, $clinicId]);
        $thread = $stmt->fetch();
        if (!$thread) throw new RuntimeException('پیام یافت نشد.');

        $m = $this->db->prepare(
            'SELECT id,direction,channel,sender_name,sender_email,recipient_email,subject,body_text,
                    external_message_id,in_reply_to,delivery_status,created_by,created_at
             FROM communication_messages WHERE thread_id = ? ORDER BY id ASC'
        );
        $m->execute([$threadId]);
        $messages = $m->fetchAll();
        foreach ($messages as &$msg) {
            $msg['id'] = (int)$msg['id'];
            $msg['created_by'] = $msg['created_by'] === null ? null : (int)$msg['created_by'];
        }
        unset($msg);
        $labelStmt = $this->db->prepare(
            'SELECT l.id,l.name,l.color_key
               FROM communication_thread_labels tl
               JOIN communication_labels l ON l.id=tl.label_id
              WHERE tl.thread_id=? AND l.clinic_id=?
              ORDER BY l.name ASC'
        );
        $labelStmt->execute([$threadId,$clinicId]);
        $labels = array_map(static function(array $row): array {
            return ['id'=>(int)$row['id'],'name'=>(string)$row['name'],'color_key'=>(string)$row['color_key']];
        }, $labelStmt->fetchAll());
        $thread = $this->castThread($thread);
        $thread['labels'] = $labels;
        return ['thread' => $thread, 'messages' => $messages];
    }

    public function markRead(int $clinicId, int $threadId): void
    {
        $stmt = $this->db->prepare('UPDATE communication_threads SET unread_count = 0, updated_at = UTC_TIMESTAMP() WHERE id = ? AND clinic_id = ?');
        $stmt->execute([$threadId, $clinicId]);
        if ($stmt->rowCount() === 0) {
            $exists = $this->db->prepare('SELECT 1 FROM communication_threads WHERE id=? AND clinic_id=?');
            $exists->execute([$threadId,$clinicId]);
            if (!$exists->fetchColumn()) throw new RuntimeException('پیام یافت نشد.');
        }
    }

    public function setStatus(int $clinicId, int $threadId, string $status): void
    {
        if (!in_array($status, ['open','closed','spam'], true)) throw new RuntimeException('وضعیت پیام معتبر نیست.');
        $stmt = $this->db->prepare('UPDATE communication_threads SET status=?, updated_at=UTC_TIMESTAMP() WHERE id=? AND clinic_id=?');
        $stmt->execute([$status,$threadId,$clinicId]);
        if ($stmt->rowCount() === 0) {
            $exists=$this->db->prepare('SELECT 1 FROM communication_threads WHERE id=? AND clinic_id=?');
            $exists->execute([$threadId,$clinicId]);
            if(!$exists->fetchColumn()) throw new RuntimeException('پیام یافت نشد.');
        }
    }


    /** @return array<int,array<string,mixed>> */
    public function labels(int $clinicId): array
    {
        $stmt = $this->db->prepare(
            'SELECT l.id,l.name,l.color_key,COUNT(tl.thread_id) usage_count
               FROM communication_labels l
               LEFT JOIN communication_thread_labels tl ON tl.label_id=l.id
              WHERE l.clinic_id=?
              GROUP BY l.id,l.name,l.color_key
              ORDER BY l.name ASC'
        );
        $stmt->execute([$clinicId]);
        return array_map(static function(array $row): array {
            return [
                'id'=>(int)$row['id'],
                'name'=>(string)$row['name'],
                'color_key'=>(string)$row['color_key'],
                'usage_count'=>(int)$row['usage_count'],
            ];
        }, $stmt->fetchAll());
    }

    /** @return array<string,mixed> */
    public function createLabel(int $clinicId, string $name, string $colorKey = 'green'): array
    {
        $name = trim(preg_replace('/\s+/u',' ',$name) ?? '');
        if ($name === '' || mb_strlen($name) > 80) throw new RuntimeException('نام برچسب معتبر نیست.');
        $allowedColors = ['green','blue','amber','purple','red','gray'];
        if (!in_array($colorKey,$allowedColors,true)) $colorKey='green';

        $stmt = $this->db->prepare(
            'INSERT INTO communication_labels (clinic_id,name,color_key,created_at)
             VALUES (?,?,?,UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE color_key=VALUES(color_key)'
        );
        $stmt->execute([$clinicId,$name,$colorKey]);

        $find=$this->db->prepare('SELECT id,name,color_key FROM communication_labels WHERE clinic_id=? AND name=? LIMIT 1');
        $find->execute([$clinicId,$name]);
        $row=$find->fetch();
        if(!$row) throw new RuntimeException('ساخت برچسب انجام نشد.');
        return ['id'=>(int)$row['id'],'name'=>(string)$row['name'],'color_key'=>(string)$row['color_key']];
    }

    public function deleteLabel(int $clinicId, int $labelId): void
    {
        $stmt=$this->db->prepare('DELETE FROM communication_labels WHERE id=? AND clinic_id=?');
        $stmt->execute([$labelId,$clinicId]);
        if($stmt->rowCount()===0) throw new RuntimeException('برچسب یافت نشد.');
    }

    /** @return array<string,mixed> */
    public function updateMeta(int $clinicId, int $threadId, array $input): array
    {
        $this->assertThread($clinicId,$threadId);

        $sets=[];$params=[];
        if(array_key_exists('is_starred',$input)){
            $sets[]='is_starred=?';
            $params[]=filter_var($input['is_starred'],FILTER_VALIDATE_BOOL)?1:0;
        }
        if(array_key_exists('category',$input)){
            $category=trim((string)$input['category']);
            if(!in_array($category,['contact','appointment','followup','system','other'],true)){
                throw new RuntimeException('دسته‌بندی معتبر نیست.');
            }
            $sets[]='category=?';$params[]=$category;
        }
        if($sets){
            $sets[]='updated_at=UTC_TIMESTAMP()';
            $params[]=$threadId;$params[]=$clinicId;
            $this->db->prepare(
                'UPDATE communication_threads SET '.implode(',',$sets).' WHERE id=? AND clinic_id=?'
            )->execute($params);
        }

        if(array_key_exists('label_ids',$input)){
            $ids=array_values(array_unique(array_filter(array_map('intval',(array)$input['label_ids']),static fn(int $v):bool=>$v>0)));
            if($ids){
                $placeholders=implode(',',array_fill(0,count($ids),'?'));
                $check=$this->db->prepare(
                    'SELECT id FROM communication_labels WHERE clinic_id=? AND id IN ('.$placeholders.')'
                );
                $check->execute(array_merge([$clinicId],$ids));
                $valid=array_map('intval',array_column($check->fetchAll(),'id'));
                sort($valid);$expected=$ids;sort($expected);
                if($valid!==$expected) throw new RuntimeException('یک یا چند برچسب معتبر نیست.');
            }
            $this->db->beginTransaction();
            try{
                $this->db->prepare('DELETE FROM communication_thread_labels WHERE thread_id=?')->execute([$threadId]);
                if($ids){
                    $ins=$this->db->prepare(
                        'INSERT INTO communication_thread_labels (thread_id,label_id,created_at) VALUES (?,?,UTC_TIMESTAMP())'
                    );
                    foreach($ids as $id)$ins->execute([$threadId,$id]);
                }
                $this->db->commit();
            }catch(\Throwable $e){
                if($this->db->inTransaction())$this->db->rollBack();
                throw $e;
            }
        }

        return $this->thread($clinicId,$threadId)['thread'];
    }

    /** @return array<string,mixed> */
    public function reply(int $clinicId, int $threadId, int $staffId, string $body): array
    {
        $body = trim($body);
        if ($body === '' || mb_strlen($body) > 10000) throw new RuntimeException('متن پاسخ معتبر نیست.');
        $data = $this->thread($clinicId, $threadId);
        $thread = $data['thread'];
        $email = trim((string)($thread['contact_email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('برای این پیام ایمیل معتبری ثبت نشده است.');

        $lastInbound = '';
        for ($i = count($data['messages']) - 1; $i >= 0; $i--) {
            $m = $data['messages'][$i];
            if (($m['direction'] ?? '') === 'inbound' && !empty($m['external_message_id'])) {
                $lastInbound = (string)$m['external_message_id'];
                break;
            }
        }

        $settings = $this->settings($clinicId);
        $sent = (new EmailService())->sendCommunicationReply(
            $email,
            (string)($thread['contact_name'] ?? ''),
            (string)($thread['subject'] ?? 'پیام شما'),
            $body,
            $settings,
            $lastInbound
        );

        $stmt = $this->db->prepare(
            'INSERT INTO communication_messages
             (thread_id,direction,channel,sender_name,sender_email,recipient_email,subject,body_text,body_html,
              external_message_id,in_reply_to,delivery_status,created_by,created_at)
             VALUES (?,"outbound","email",?,?,?,?,?,?,?,?,?,?,UTC_TIMESTAMP())'
        );
        $stmt->execute([
            $threadId,
            (string)($settings['from_name'] ?? ''),
            (string)($settings['inbox_email'] ?? ''),
            $email,
            (string)($thread['subject'] ?? ''),
            $body,
            $sent['html'],
            $sent['message_id'] !== '' ? $sent['message_id'] : null,
            $lastInbound !== '' ? $lastInbound : null,
            $sent['sent'] ? 'sent' : 'failed',
            $staffId,
        ]);
        $this->db->prepare(
            'UPDATE communication_threads SET status="open",category="followup",last_message_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE id=?'
        )->execute([$threadId]);
        if($sent['sent']) $this->applyLabelByName($clinicId,$threadId,'پاسخ داده شد');

        return ['sent' => $sent['sent'], 'message_id' => $sent['message_id'], 'thread_id' => $threadId];
    }

    /** @return array<string,mixed> */
    public function ingestContact(int $clinicId, array $data): array
    {
        $externalKey = trim((string)($data['external_key'] ?? ''));
        if ($externalKey !== '') {
            $q = $this->db->prepare('SELECT id FROM communication_threads WHERE clinic_id=? AND external_key=? LIMIT 1');
            $q->execute([$clinicId,$externalKey]);
            $existing = (int)($q->fetchColumn() ?: 0);
            if ($existing > 0) return ['thread_id'=>$existing,'duplicate'=>true,'auto_response'=>'skipped'];
        }

        $name = mb_substr(trim((string)($data['name'] ?? '')),0,190);
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $phone = mb_substr(trim((string)($data['phone'] ?? '')),0,32);
        $subject = mb_substr(trim((string)($data['subject'] ?? '')),0,255);
        $message = trim((string)($data['message'] ?? ''));
        if ($subject === '') $subject = 'پیام از وب‌سایت';
        if ($message === '') $message = 'پیام بدون متن ثبت شده است.';
        if (!filter_var($email,FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('وارد کردن ایمیل معتبر برای ارسال پیام الزامی است.');
        }
        $parts = [];
        if($name!=='') $parts[]='نام: '.$name;
        $parts[]='ایمیل: '.$email;
        if($phone!=='') $parts[]='شماره تماس: '.$phone;
        if($subject!=='') $parts[]='موضوع: '.$subject;
        $parts[]='پیام: '.$message;
        $body = implode("\n",$parts);

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO communication_threads
                 (clinic_id,source,external_key,contact_name,contact_email,contact_phone,subject,status,unread_count,last_message_at,created_at,updated_at)
                 VALUES (?,"contact_form",NULLIF(?,""),?,NULLIF(?,""),NULLIF(?,""),?,"open",1,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())'
            );
            $stmt->execute([$clinicId,$externalKey,$name,$email,$phone,$subject]);
            $threadId = (int)$this->db->lastInsertId();
            $msg = $this->db->prepare(
                'INSERT INTO communication_messages
                 (thread_id,direction,channel,sender_name,sender_email,recipient_email,subject,body_text,delivery_status,created_at)
                 VALUES (?,"inbound","contact_form",?,NULLIF(?,""),"contact@drbastaninejad.com",?,?,"received",UTC_TIMESTAMP())'
            );
            $msg->execute([$threadId,$name,$email,$subject,$body]);
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }

        $auto = 'skipped';
        $settings = $this->settings($clinicId);
        if ($email !== '' && !empty($settings['auto_response_enabled']) && empty($data['suppress_auto_response'])) {
            $result = (new EmailService())->sendContactAutoResponse($email,$name,$settings);
            $auto = $result['sent'] ? 'sent' : 'failed';
            $this->recordAutoResponse($threadId,$email,$subject,$settings,$result);
        }
        return ['thread_id'=>$threadId,'duplicate'=>false,'auto_response'=>$auto];
    }

    /** @return array{processed:int,errors:int} */
    public function syncSpool(int $clinicId, int $limit = 100): array
    {
        $base = '/home/drbastaninejad/private-communication-inbox';
        $processedDir = $base . '/processed';
        if (!is_dir($base)) return ['processed'=>0,'errors'=>0];
        if (!is_dir($processedDir)) @mkdir($processedDir,0700,true);
        $files = glob($base . '/*.eml') ?: [];
        usort($files, static fn(string $a,string $b): int => filemtime($a) <=> filemtime($b));
        $processed = 0; $errors = 0;
        foreach (array_slice($files,0,max(1,min(500,$limit))) as $file) {
            try {
                $raw = file_get_contents($file);
                if ($raw === false || $raw === '') throw new RuntimeException('empty email');
                $this->ingestRawEmail($clinicId,$raw,'spool:' . basename($file));
                $dest = $processedDir . '/' . basename($file);
                if (!@rename($file,$dest)) @unlink($file);
                $processed++;
            } catch (\Throwable) {
                $errors++;
            }
        }
        $this->db->prepare('UPDATE communication_settings SET last_inbox_sync_at=UTC_TIMESTAMP(),updated_at=updated_at WHERE clinic_id=?')
            ->execute([$clinicId]);
        foreach (glob($processedDir . '/*.eml') ?: [] as $old) {
            if (@filemtime($old) < time() - 30*86400) @unlink($old);
        }
        return ['processed'=>$processed,'errors'=>$errors];
    }

    /** @return array<string,mixed> */
    public function ingestRawEmail(int $clinicId, string $raw, string $fallbackKey = ''): array
    {
        $mail = $this->parseRawEmail($raw);
        $messageId = trim((string)($mail['message_id'] ?? ''));
        if ($messageId === '') $messageId = '<sha256-' . hash('sha256',$raw) . '@local>';
        $dup = $this->db->prepare('SELECT thread_id FROM communication_messages WHERE external_message_id=? LIMIT 1');
        $dup->execute([$messageId]);
        $existing = (int)($dup->fetchColumn() ?: 0);
        if ($existing > 0) return ['thread_id'=>$existing,'duplicate'=>true];

        $senderEmail = strtolower(trim((string)($mail['from_email'] ?? '')));
        $senderName = mb_substr(trim((string)($mail['from_name'] ?? '')),0,190);
        $subject = mb_substr(trim((string)($mail['subject'] ?? '')),0,255);
        $body = trim((string)($mail['body_text'] ?? ''));
        if ($subject === '') $subject = 'ایمیل بدون موضوع';
        if ($body === '') $body = 'این ایمیل متن قابل نمایش نداشت.';
        if (!filter_var($senderEmail,FILTER_VALIDATE_EMAIL)) $senderEmail = '';

        $threadId = 0;
        $inReplyTo = trim((string)($mail['in_reply_to'] ?? ''));
        if ($inReplyTo !== '') {
            $q=$this->db->prepare('SELECT thread_id FROM communication_messages WHERE external_message_id=? ORDER BY id DESC LIMIT 1');
            $q->execute([$inReplyTo]);$threadId=(int)($q->fetchColumn()?:0);
        }
        if ($threadId === 0 && $senderEmail !== '') {
            $q=$this->db->prepare('SELECT id FROM communication_threads WHERE clinic_id=? AND contact_email=? AND status<>"spam" ORDER BY last_message_at DESC LIMIT 1');
            $q->execute([$clinicId,$senderEmail]);$threadId=(int)($q->fetchColumn()?:0);
        }

        $isNew = false;
        $this->db->beginTransaction();
        try {
            if ($threadId === 0) {
                $isNew = true;
                $stmt=$this->db->prepare(
                    'INSERT INTO communication_threads
                     (clinic_id,source,external_key,contact_name,contact_email,subject,status,unread_count,last_message_at,created_at,updated_at)
                     VALUES (?,"email",NULLIF(?,""),?,NULLIF(?,""),?,"open",1,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())'
                );
                $stmt->execute([$clinicId,$fallbackKey,$senderName,$senderEmail,$subject]);
                $threadId=(int)$this->db->lastInsertId();
            } else {
                $this->db->prepare(
                    'UPDATE communication_threads SET unread_count=unread_count+1,last_message_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP(),status=IF(status="closed","open",status) WHERE id=? AND clinic_id=?'
                )->execute([$threadId,$clinicId]);
            }
            $stmt=$this->db->prepare(
                'INSERT INTO communication_messages
                 (thread_id,direction,channel,sender_name,sender_email,recipient_email,subject,body_text,external_message_id,in_reply_to,delivery_status,created_at)
                 VALUES (?,"inbound","email",?,NULLIF(?,""),"contact@drbastaninejad.com",?,?,?,?, "received",UTC_TIMESTAMP())'
            );
            $stmt->execute([$threadId,$senderName,$senderEmail,$subject,$body,$messageId,$inReplyTo !== '' ? $inReplyTo : null]);
            $this->db->commit();
        } catch (\Throwable $e) {
            if($this->db->inTransaction())$this->db->rollBack();
            throw $e;
        }

        if ($isNew && $senderEmail !== '' && !$this->isAutomatedMail($mail)) {
            $settings=$this->settings($clinicId);
            if(!empty($settings['auto_response_enabled'])){
                $result=(new EmailService())->sendContactAutoResponse($senderEmail,$senderName,$settings);
                $this->recordAutoResponse($threadId,$senderEmail,$subject,$settings,$result);
            }
        }
        return ['thread_id'=>$threadId,'duplicate'=>false];
    }

    private function recordAutoResponse(int $threadId,string $email,string $subject,array $settings,array $result): void
    {
        $body=(string)($settings['auto_response_body']??'');
        $stmt=$this->db->prepare(
            'INSERT INTO communication_messages
             (thread_id,direction,channel,sender_name,sender_email,recipient_email,subject,body_text,body_html,external_message_id,delivery_status,created_at)
             VALUES (?,"outbound","email",?,?,?,?,?,?,?,?,UTC_TIMESTAMP())'
        );
        $stmt->execute([
            $threadId,
            (string)($settings['from_name']??''),
            (string)($settings['inbox_email']??''),
            $email,
            (string)($settings['auto_response_subject']??$subject),
            $body,
            (string)($result['html']??''),
            !empty($result['message_id'])?$result['message_id']:null,
            !empty($result['sent'])?'auto_sent':'failed'
        ]);
    }

    private function assertThread(int $clinicId, int $threadId): void
    {
        $stmt=$this->db->prepare('SELECT 1 FROM communication_threads WHERE id=? AND clinic_id=? LIMIT 1');
        $stmt->execute([$threadId,$clinicId]);
        if(!$stmt->fetchColumn()) throw new RuntimeException('پیام یافت نشد.');
    }

    private function applyLabelByName(int $clinicId, int $threadId, string $name): void
    {
        $stmt=$this->db->prepare('SELECT id FROM communication_labels WHERE clinic_id=? AND name=? LIMIT 1');
        $stmt->execute([$clinicId,$name]);
        $labelId=(int)($stmt->fetchColumn()?:0);
        if($labelId<=0)return;
        $this->db->prepare(
            'INSERT IGNORE INTO communication_thread_labels (thread_id,label_id,created_at) VALUES (?,?,UTC_TIMESTAMP())'
        )->execute([$threadId,$labelId]);
    }

    /** @return array<string,mixed> */
    private function castThread(array $row): array
    {
        $row['id']=(int)$row['id'];
        $row['clinic_id']=(int)$row['clinic_id'];
        $row['unread_count']=(int)$row['unread_count'];
        $row['is_starred']=(bool)($row['is_starred']??false);
        $row['assigned_user_id']=$row['assigned_user_id']===null?null:(int)$row['assigned_user_id'];

        if(array_key_exists('labels_raw',$row)){
            $labels=[];
            $raw=(string)($row['labels_raw']??'');
            if($raw!==''){
                foreach(explode('~~',$raw) as $chunk){
                    $parts=explode('|',$chunk,3);
                    if(count($parts)===3)$labels[]=['id'=>(int)$parts[0],'name'=>$parts[1],'color_key'=>$parts[2]];
                }
            }
            $row['labels']=$labels;
            unset($row['labels_raw']);
        }
        return $row;
    }

    /** @return array<string,string> */
    private function parseRawEmail(string $raw): array
    {
        [$headerText,$body] = array_pad(preg_split("/\r?\n\r?\n/",$raw,2),2,'');
        $headers=$this->parseHeaders($headerText);
        [$fromName,$fromEmail]=$this->parseAddress((string)($headers['from']??''));
        $contentType=(string)($headers['content-type']??'text/plain');
        $transfer=strtolower(trim((string)($headers['content-transfer-encoding']??'')));
        $text=$this->decodeMimeBody($body,$contentType,$transfer);
        return [
            'from_name'=>$fromName,
            'from_email'=>$fromEmail,
            'to'=>(string)($headers['to']??''),
            'subject'=>$this->decodeHeader((string)($headers['subject']??'')),
            'message_id'=>trim((string)($headers['message-id']??'')),
            'in_reply_to'=>trim((string)($headers['in-reply-to']??'')),
            'auto_submitted'=>strtolower(trim((string)($headers['auto-submitted']??''))),
            'precedence'=>strtolower(trim((string)($headers['precedence']??''))),
            'body_text'=>$text,
        ];
    }

    /** @return array<string,string> */
    private function parseHeaders(string $text): array
    {
        $text=preg_replace("/\r?\n[ \t]+/"," ",$text)??$text;
        $out=[];
        foreach(preg_split("/\r?\n/",$text)?:[] as $line){
            if(!str_contains($line,':'))continue;
            [$k,$v]=explode(':',$line,2);
            $key=strtolower(trim($k));
            if(!isset($out[$key]))$out[$key]=trim($v);
        }
        return $out;
    }

    /** @return array{0:string,1:string} */
    private function parseAddress(string $value): array
    {
        $decoded=$this->decodeHeader($value);
        if(preg_match('/<([^>]+)>/',$decoded,$m)){
            $email=trim($m[1]);
            $name=trim(preg_replace('/<[^>]+>/','',$decoded)??''," \t\n\r\0\x0B\"");
            return [$name,$email];
        }
        if(filter_var(trim($decoded),FILTER_VALIDATE_EMAIL))return ['',trim($decoded)];
        return [$decoded,''];
    }

    private function decodeHeader(string $value): string
    {
        if($value==='')return'';
        if(function_exists('iconv_mime_decode')){
            $v=@iconv_mime_decode($value,0,'UTF-8');
            if(is_string($v))return$v;
        }
        return $value;
    }

    private function decodeMimeBody(string $body,string $contentType,string $transfer): string
    {
        if(preg_match('/boundary="?([^";]+)"?/i',$contentType,$m)){
            $boundary=$m[1];
            $parts=preg_split('/--'.preg_quote($boundary,'/').'(?:--)?\r?\n/',$body)?:[];
            $plain='';$html='';
            foreach($parts as $part){
                if(!str_contains($part,"\n\n")&&!str_contains($part,"\r\n\r\n"))continue;
                [$h,$b]=array_pad(preg_split("/\r?\n\r?\n/",$part,2),2,'');
                $ph=$this->parseHeaders($h);
                $ct=strtolower((string)($ph['content-type']??'text/plain'));
                if(str_starts_with($ct,'multipart/')){
                    $nested=$this->decodeMimeBody($b,$ct,strtolower((string)($ph['content-transfer-encoding']??'')));
                    if($nested!==''&&$plain==='')$plain=$nested;
                    continue;
                }
                $decoded=$this->decodeTransfer($b,strtolower((string)($ph['content-transfer-encoding']??'')));
                $decoded=$this->toUtf8($decoded,$ct);
                if(str_starts_with($ct,'text/plain')&&$plain==='')$plain=trim($decoded);
                elseif(str_starts_with($ct,'text/html')&&$html==='')$html=trim(strip_tags(preg_replace('/<br\s*\/?\s*>/i',"\n",$decoded)??$decoded));
            }
            return trim($plain!==''?$plain:$html);
        }
        $decoded=$this->decodeTransfer($body,$transfer);
        $decoded=$this->toUtf8($decoded,$contentType);
        if(str_starts_with(strtolower($contentType),'text/html')){
            $decoded=strip_tags(preg_replace('/<br\s*\/?\s*>/i',"\n",$decoded)??$decoded);
        }
        return trim($decoded);
    }

    private function decodeTransfer(string $body,string $transfer): string
    {
        return match($transfer){
            'base64'=>base64_decode(preg_replace('/\s+/','',$body)??'',true)?:'',
            'quoted-printable'=>quoted_printable_decode($body),
            default=>$body,
        };
    }

    private function toUtf8(string $text,string $contentType): string
    {
        if(preg_match('/charset="?([^";\s]+)"?/i',$contentType,$m)){
            $charset=trim($m[1]);
            if($charset!==''&&strcasecmp($charset,'utf-8')!==0){
                try{return mb_convert_encoding($text,'UTF-8',$charset);}catch(\Throwable){}
            }
        }
        return $text;
    }

    /** @param array<string,string> $mail */
    private function isAutomatedMail(array $mail): bool
    {
        $auto=$mail['auto_submitted']??'';
        if($auto!==''&&$auto!=='no')return true;
        if(in_array($mail['precedence']??'',['bulk','list','junk'],true))return true;
        $from=strtolower((string)($mail['from_email']??''));
        return str_contains($from,'mailer-daemon')||str_contains($from,'postmaster');
    }
}