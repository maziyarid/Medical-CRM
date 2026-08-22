<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\OtpService;

final class AuthController extends Controller
{
    public function me(Request $req): array
    {
        $user = $req->user ?? [];
        unset($user['_token_id'], $user['_token_hash']);
        return $this->success($user);
    }

    public function logout(Request $req): array
    {
        $tokenId = (int)($req->user['_token_id'] ?? 0);
        if ($tokenId > 0) {
            (new OtpService())->revokeTokenById($tokenId);
        }
        return $this->success(['revoked' => true]);
    }

    public function logoutAll(Request $req): array
    {
        $user = $req->user ?? [];
        $userId = (int)($user['id'] ?? 0);
        $userType = (string)($user['user_type'] ?? '');
        if ($userId < 1 || !in_array($userType, ['patient', 'staff'], true)) {
            return $this->error('نشست نامعتبر است.', 401);
        }
        (new OtpService())->revokeAllSessions($userId, $userType);
        return $this->success(['revoked' => true]);
    }
}
