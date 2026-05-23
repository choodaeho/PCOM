<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends Controller
{
    /**
     * GET /api/v1/auth/email/verify/{id}/{hash}
     *
     * 이메일 인증 링크 처리.
     * signed URL 미들웨어로 보호되어 있음.
     */
    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        /** @var User|null $user */
        $user = User::find($id);

        if (! $user) {
            return response()->json(['message' => '유효하지 않은 인증 링크입니다.'], 404);
        }

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return response()->json(['message' => '인증 해시가 일치하지 않습니다.'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => '이미 인증된 이메일입니다.']);
        }

        $user->markEmailAsVerified();

        // 계정 상태를 active 로 전환
        $user->update(['status' => 'active']);

        event(new Verified($user));

        return response()->json(['message' => '이메일 인증이 완료되었습니다. 이제 로그인하실 수 있습니다.']);
    }

    /**
     * POST /api/v1/auth/email/resend
     *
     * 인증 메일 재발송.
     */
    public function resend(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => '이미 인증된 이메일입니다.'], 422);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => '인증 메일을 재발송했습니다.']);
    }
}
