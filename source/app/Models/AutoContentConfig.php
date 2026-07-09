<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * AI 자동 콘텐츠 생성 설정 (싱글턴 row, id=1 고정)
 */
class AutoContentConfig extends Model
{
    protected $fillable = [
        'gemini_api_key',
        'pixabay_api_key',
        'is_enabled',
        'posts_per_faction',
        'comments_per_post_min',
        'comments_per_post_max',
        'start_hour',
        'end_hour',
        'include_images',
        'include_news_links',
        'include_youtube',
        'use_grounding',
        'target_boards',
        'topics',
        'last_run_at',
        'last_run_stats',
    ];

    protected $casts = [
        'is_enabled'            => 'boolean',
        'posts_per_faction'     => 'integer',
        'comments_per_post_min' => 'integer',
        'comments_per_post_max' => 'integer',
        'start_hour'            => 'integer',
        'end_hour'              => 'integer',
        'include_images'        => 'boolean',
        'include_news_links'    => 'boolean',
        'include_youtube'       => 'boolean',
        'use_grounding'         => 'boolean',
        'target_boards'         => 'array',
        'topics'                => 'array',
        'last_run_at'           => 'datetime',
        'last_run_stats'        => 'array',
    ];

    /** 설정 1건(싱글턴)을 가져오거나 기본값으로 생성 */
    public static function getInstance(): self
    {
        // find() 로 먼저 조회 → 없으면 직접 new + save (id mass-assignment 우회)
        $existing = self::find(1);
        if ($existing) {
            return $existing;
        }

        $config     = new self();
        $config->id = 1; // PK 직접 할당 (mass assignment 우회)
        $config->fill([
            'gemini_api_key'        => '',
            'pixabay_api_key'       => '',
            'is_enabled'            => false,
            'posts_per_faction'     => 2,    // 기본 2개 (무료 RPD=20 제약: 2×3진영+댓글 ≈ 18콜/일)
            'comments_per_post_min' => 1,
            'comments_per_post_max' => 3,
            'start_hour'            => 6,
            'end_hour'              => 24,
            'include_images'        => true,
            'include_news_links'    => true,
            'include_youtube'       => true,
            'use_grounding'         => true,
            'target_boards'         => self::defaultTargetBoards(),
            'topics'                => self::defaultTopics(),
        ]);
        $config->save();

        return $config;
    }

    /** 진영별 기본 타겟 게시판 */
    public static function defaultTargetBoards(): array
    {
        return [
            'conservative' => ['conservative-azit', 'battle-politics', 'battle-economy'],
            'moderate'     => ['moderate-azit', 'battle-politics', 'battle-society'],
            'progressive'  => ['progressive-azit', 'battle-politics', 'battle-economy'],
        ];
    }

    /** 진영별 기본 주제 키워드 */
    public static function defaultTopics(): array
    {
        return [
            'conservative' => [
                '경제성장과 기업 친화 정책', '안보 강화와 한미동맹', '규제 완화와 자유시장',
                '법치주의와 질서', '전통 가치와 가족', '국방력 강화', '부동산 규제 완화',
                '감세 정책과 투자 활성화', '에너지 자립과 원전', '교육 자유화',
            ],
            'moderate'     => [
                '사회 통합과 균형 발전', '실용주의 복지 개혁', '미래 교육과 디지털 전환',
                '기후변화 대응', '양극화 해소', '중소기업 지원', '청년 정책',
                '저출생 대책', '지역 균형 발전', '외교 다변화',
            ],
            'progressive'  => [
                '복지 확대와 불평등 해소', '노동권 강화', '환경 보호와 탈탄소',
                '재벌 개혁과 경제 민주화', '검찰 개혁', '평화 외교와 남북 관계',
                '세금 정의', '의료 공공성 강화', '주거권 보장', '젠더 평등',
            ],
        ];
    }

    /** 총 일일 댓글 목표치 (posts * avg_comments) */
    public function estimatedDailyComments(): int
    {
        $avg = ($this->comments_per_post_min + $this->comments_per_post_max) / 2;
        return (int) ($this->posts_per_faction * 3 * $avg);
    }
}
