<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Vote;
use App\Observers\CommentObserver;
use App\Observers\PostObserver;
use App\Observers\VoteObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * 애플리케이션 서비스 등록.
     */
    public function register(): void
    {
        //
    }

    /**
     * 애플리케이션 서비스 부트스트랩.
     */
    public function boot(): void
    {
        // -----------------------------------------------------------------
        // Eloquent 보안 설정
        // -----------------------------------------------------------------

        // production 환경에서 lazy loading 금지 (N+1 문제 사전 차단)
        Model::preventLazyLoading(! app()->isProduction());

        // 채워지지 않은 fillable 필드에 예외 발생 (실수 방지)
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        // -----------------------------------------------------------------
        // Model Observer 등록
        // -----------------------------------------------------------------

        Post::observe(PostObserver::class);
        Comment::observe(CommentObserver::class);
        Vote::observe(VoteObserver::class);

        // -----------------------------------------------------------------
        // 브로드캐스트 채널 인증 라우트 (Laravel Reverb)
        // -----------------------------------------------------------------
        // Laravel 11 스켈레톤은 기본적으로 /broadcasting/auth 라우트를
        // 등록하지 않으므로 여기서 직접 등록. routes/channels.php에
        // Private/Presence 채널 인가 로직이 정의되어 있음.
        Broadcast::routes();
        require base_path('routes/channels.php');

        // -----------------------------------------------------------------
        // Gemini API 큐 Job Rate Limiter
        // -----------------------------------------------------------------

        // 현재 모델: gemini-2.5-flash (무료 RPM=5 / RPD=20)
        //            gemini-2.5-flash-lite (무료 별도 RPD 한도, fallback)
        // ⚠️ gemini-2.0-flash / 2.0-flash-lite → 2026년 6월 1일 지원 종료
        //
        // 안전 마진 계산:
        //   4 Job/min × 평균 1.1 API콜/Job = ~4.4 RPM (RPM=5 한도의 88%)
        //   → Post + Comment 합산, 재시도 포함해도 RPM=5 이내 유지
        //   → RPD=20 제약: posts_per_faction × 3 + 댓글 ≤ 18 이내 운영 권장
        //
        // 한도 초과 시 job을 큐에 release (tries 차감 없음), 다음 슬롯에서 재처리
        RateLimiter::for('gemini', function (object $job) {
            return Limit::perMinute(4);
        });
    }
}
