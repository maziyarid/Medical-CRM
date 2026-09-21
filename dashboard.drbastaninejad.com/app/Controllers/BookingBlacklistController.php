<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\BookingBlacklistService;

final class BookingBlacklistController extends Controller
{
    public function index(Request $req): array
    {
        return $this->success(['rows'=>(new BookingBlacklistService())->all((int)($req->user['clinic_id']??1))]);
    }

    public function store(Request $req): array
    {
        try {
            $s=new BookingBlacklistService();
            $id=$s->add(
              (int)($req->user['clinic_id']??1),
              (string)($req->body['identifier']??''),
              (string)($req->body['identifier_type']??'national_id'),
              null,
              (string)($req->body['patient_name']??''),
              (string)($req->body['reason']??''),
              isset($req->user['id'])?(int)$req->user['id']:null
            );
            return $this->success(['id'=>$id],201);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(),422);
        }
    }

    public function blockPatient(Request $req,string $id): array
    {
        try {
            (new BookingBlacklistService())->addPatient(
              (int)($req->user['clinic_id']??1),(int)$id,
              (string)($req->body['reason']??'مسدود توسط پذیرش'),
              isset($req->user['id'])?(int)$req->user['id']:null
            );
            return $this->success(['blocked'=>true]);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(),422);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(),404);
        }
    }

    public function destroy(Request $req,string $id): array
    {
        $ok=(new BookingBlacklistService())->remove((int)($req->user['clinic_id']??1),(int)$id);
        return $ok?$this->success(['removed'=>true]):$this->error('مورد یافت نشد.',404);
    }
}
