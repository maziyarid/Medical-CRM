<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\CommunicationService;
use RuntimeException;

final class CommunicationsController extends Controller
{
    private function staff(Request $req): ?array
    {
        $type = (string)($req->user['user_type'] ?? 'staff');
        $role = (string)($req->user['role'] ?? '');
        if ($type === 'patient' || !in_array($role, ['super_admin','admin','receptionist','doctor','nurse'], true)) {
            return $this->error('دسترسی به پیام‌ها مجاز نیست.', 403);
        }
        return null;
    }

    public function publicContact(Request $req): array
    {
        $configured = trim((string)($_ENV['WORDPRESS_BRIDGE_SECRET'] ?? ''));
        $provided = trim((string)($req->headers['x-wordpress-bridge-secret'] ?? ''));
        if ($configured === '' || $provided === '' || !hash_equals($configured, $provided)) {
            return $this->error('دسترسی غیرمجاز', 403);
        }
        try {
            $clinicId = max(1, (int)($_ENV['DEFAULT_CLINIC_ID'] ?? 1));
            return $this->success((new CommunicationService())->ingestContact($clinicId, $req->body), 201);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function index(Request $req): array
    {
        if ($denied = $this->staff($req)) return $denied;
        $svc = new CommunicationService();
        $svc->syncSpool((int)$req->user['clinic_id'], 50);
        return $this->success($svc->threads((int)$req->user['clinic_id'], $req->query));
    }

    public function summary(Request $req): array
    {
        if ($denied = $this->staff($req)) return $denied;
        $svc = new CommunicationService();
        $svc->syncSpool((int)$req->user['clinic_id'], 50);
        return $this->success($svc->summary((int)$req->user['clinic_id']));
    }

    public function show(Request $req, string $id): array
    {
        if ($denied = $this->staff($req)) return $denied;
        try {
            return $this->success((new CommunicationService())->thread((int)$req->user['clinic_id'], (int)$id));
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    public function markRead(Request $req, string $id): array
    {
        if ($denied = $this->staff($req)) return $denied;
        try {
            (new CommunicationService())->markRead((int)$req->user['clinic_id'], (int)$id);
            return $this->success(['id'=>(int)$id,'read'=>true]);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    public function updateStatus(Request $req, string $id): array
    {
        if ($denied = $this->staff($req)) return $denied;
        try {
            (new CommunicationService())->setStatus(
                (int)$req->user['clinic_id'],
                (int)$id,
                trim((string)($req->body['status'] ?? ''))
            );
            return $this->success(['id'=>(int)$id,'status'=>(string)($req->body['status'] ?? '')]);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }


    public function labels(Request $req): array
    {
        if ($denied = $this->staff($req)) return $denied;
        return $this->success(['rows'=>(new CommunicationService())->labels((int)$req->user['clinic_id'])]);
    }

    public function createLabel(Request $req): array
    {
        if ($denied = $this->staff($req)) return $denied;
        try {
            $label=(new CommunicationService())->createLabel(
                (int)$req->user['clinic_id'],
                (string)($req->body['name'] ?? ''),
                (string)($req->body['color_key'] ?? 'green')
            );
            return $this->success($label,201);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(),422);
        }
    }

    public function deleteLabel(Request $req, string $id): array
    {
        if ($denied = $this->staff($req)) return $denied;
        try {
            (new CommunicationService())->deleteLabel((int)$req->user['clinic_id'],(int)$id);
            return $this->success(['deleted'=>(int)$id]);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(),404);
        }
    }

    public function updateMeta(Request $req, string $id): array
    {
        if ($denied = $this->staff($req)) return $denied;
        try {
            return $this->success([
                'thread'=>(new CommunicationService())->updateMeta(
                    (int)$req->user['clinic_id'],
                    (int)$id,
                    $req->body
                )
            ]);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(),422);
        }
    }

    public function reply(Request $req, string $id): array
    {
        if ($denied = $this->staff($req)) return $denied;
        try {
            $result = (new CommunicationService())->reply(
                (int)$req->user['clinic_id'],
                (int)$id,
                (int)$req->user['id'],
                (string)($req->body['body'] ?? '')
            );
            return $this->success($result, $result['sent'] ? 200 : 502);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function sync(Request $req): array
    {
        if ($denied = $this->staff($req)) return $denied;
        return $this->success((new CommunicationService())->syncSpool((int)$req->user['clinic_id'], 200));
    }

    public function settings(Request $req): array
    {
        if ($denied = $this->staff($req)) return $denied;
        return $this->success((new CommunicationService())->settings((int)$req->user['clinic_id']));
    }

    public function updateSettings(Request $req): array
    {
        if ($denied = $this->staff($req)) return $denied;
        $role = (string)($req->user['role'] ?? '');
        if (!in_array($role, ['super_admin','admin'], true)) {
            return $this->error('فقط مدیر می‌تواند تنظیمات ایمیل را تغییر دهد.', 403);
        }
        try {
            return $this->success((new CommunicationService())->updateSettings((int)$req->user['clinic_id'], $req->body));
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}