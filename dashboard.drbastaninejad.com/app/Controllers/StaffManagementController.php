<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\StaffAccountService;
use RuntimeException;

final class StaffManagementController extends Controller
{
    public function index(Request $req): array
    {
        return $this->success((new StaffAccountService())->list((int)$req->user['clinic_id']));
    }

    public function create(Request $req): array
    {
        try {
            $data=(new StaffAccountService())->create((int)$req->user['clinic_id'],(int)$req->user['id'],$req->body);
            return $this->success($data,201);
        } catch (RuntimeException $e) { return $this->domainError($e); }
    }

    public function update(Request $req,string $id): array
    {
        try {
            return $this->success((new StaffAccountService())->update((int)$req->user['clinic_id'],(int)$req->user['id'],(int)$id,$req->body));
        } catch (RuntimeException $e) { return $this->domainError($e); }
    }

    public function resendInvite(Request $req,string $id): array
    {
        try {
            return $this->success((new StaffAccountService())->resendInvite((int)$req->user['clinic_id'],(int)$id));
        } catch (RuntimeException $e) { return $this->domainError($e); }
    }

    private function domainError(RuntimeException $e): array
    {
        $m=$e->getMessage();
        $status=match($m){
            'staff user not found'=>404,
            'staff mobile already registered','cannot remove the last super admin','cannot deactivate your own account'=>409,
            'staff schema migration lock timeout'=>503,
            default=>422,
        };
        return $this->error($m,$status);
    }
}
