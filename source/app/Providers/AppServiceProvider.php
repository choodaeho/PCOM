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
        // Gemini API 큐 Job Rate Limiter
        // -----------------------------------------------------------------

        // 무료 티어: 15 RPM → Post(grounding포함 최대 2콜) + Comment 합산
        // 안전 마진 확보: 분당 최대 10 Job 처리
        // - 10 Job × 평균 1.2 API콜 = ~12 RPM (한도 내)
        // - 초과 시 job을 큐에 release (tries 차감 없음), 다음 슬롯에서 재처리
        RateLimiter::for('gemini', function (object $job) {
            return Limit::perMinute(10);
        });
    }
}
