<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BoardType;
use App\Enums\PostStatus;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Support\Carbon;

class UserLevelService
{
    /**
     * 게시판 유형별 XP 가중치
     *
     * @var array<string, array{post: int, comment: int}>
     */
    public const XP_RATES = [
        'battle'     => ['post' => 20, 'comment' => 5],
        'azit'       => ['post' => 10, 'comment' => 3],
        'playground' => ['post' => 5,  'comment' => 2],
        'notice'     => ['post' => 5,  'comment' => 2],
    ];

    /** 받은 추천 1개당 XP */
    public const XP_VOTE_UP = 2;

    /**
     * 50레벨 정의 (레벨 번호 => [xp, emoji, name])
     * 난이도: 레벨 20부터 급격히 가파름. 레벨 50은 사실상 도달 불가.
     *
     * @var array<int, array{xp: int, emoji: string, name: string}>
     */
    public const LEVELS = [
        // ── 입문 (1-5) ──────────────────────────────────────────────
        1  => ['xp' => 0,            'emoji' => '🌱', 'name' => '새싹'],
        2  => ['xp' => 150,          'emoji' => '🌿', 'name' => '풀잎'],
        3  => ['xp' => 400,          'emoji' => '📖', 'name' => '견습생'],
        4  => ['xp' => 800,          'emoji' => '🔍', 'name' => '탐색자'],
        5  => ['xp' => 1_500,        'emoji' => '📰', 'name' => '논객'],
        // ── 성장 (6-10) ─────────────────────────────────────────────
        6  => ['xp' => 2_600,        'emoji' => '🎙️', 'name' => '웅변가'],
        7  => ['xp' => 4_200,        'emoji' => '⚡', 'name' => '활동가'],
        8  => ['xp' => 6_500,        'emoji' => '🔥', 'name' => '열혈당원'],
        9  => ['xp' => 10_000,       'emoji' => '📣', 'name' => '대변인'],
        10 => ['xp' => 15_000,       'emoji' => '⚔️', 'name' => '전사'],
        // ── 중견 (11-15) ────────────────────────────────────────────
        11 => ['xp' => 22_000,       'emoji' => '🗡️', 'name' => '베테랑'],
        12 => ['xp' => 33_000,       'emoji' => '🛡️', 'name' => '수호자'],
        13 => ['xp' => 50_000,       'emoji' => '🦅', 'name' => '선봉대'],
        14 => ['xp' => 75_000,       'emoji' => '💎', 'name' => '정예'],
        15 => ['xp' => 115_000,      'emoji' => '🌟', 'name' => '스타'],
        // ── 고수 (16-20) ────────────────────────────────────────────
        16 => ['xp' => 175_000,      'emoji' => '🔮', 'name' => '전략가'],
        17 => ['xp' => 265_000,      'emoji' => '🦁', 'name' => '진영 리더'],
        18 => ['xp' => 400_000,      'emoji' => '👁️', 'name' => '감시자'],
        19 => ['xp' => 610_000,      'emoji' => '🏆', 'name' => '챔피언'],
        20 => ['xp' => 930_000,      'emoji' => '⭐', 'name' => '슈퍼스타'],
        // ── 엘리트 (21-25) ──────────────────────────────────────────
        21 => ['xp' => 1_420_000,    'emoji' => '🌙', 'name' => '야전대장'],
        22 => ['xp' => 2_170_000,    'emoji' => '☀️', 'name' => '빛의 전사'],
        23 => ['xp' => 3_320_000,    'emoji' => '🌊', 'name' => '파도'],
        24 => ['xp' => 5_080_000,    'emoji' => '🏔️', 'name' => '철옹성'],
        25 => ['xp' => 7_780_000,    'emoji' => '🦋', 'name' => '각성자'],
        // ── 전설 (26-30) ────────────────────────────────────────────
        26 => ['xp' => 11_900_000,   'emoji' => '🔱', 'name' => '신화'],
        27 => ['xp' => 18_200_000,   'emoji' => '💫', 'name' => '초월자'],
        28 => ['xp' => 27_800_000,   'emoji' => '🌌', 'name' => '우주'],
        29 => ['xp' => 42_500_000,   'emoji' => '🌠', 'name' => '불꽃 전설'],
        30 => ['xp' => 65_000_000,   'emoji' => '👑', 'name' => '폴릿의 신'],
        // ── 신화 (31-35) ─────────────────────────────────────────────
        31 => ['xp' => 99_000_000,   'emoji' => '🌋', 'name' => '화산'],
        32 => ['xp' => 152_000_000,  'emoji' => '⚡', 'name' => '번개왕'],
        33 => ['xp' => 232_000_000,  'emoji' => '🌪️', 'name' => '폭풍의 눈'],
        34 => ['xp' => 355_000_000,  'emoji' => '🌈', 'name' => '여명'],
        35 => ['xp' => 543_000_000,  'emoji' => '🦄', 'name' => '전설의 시작'],
        // ── 초월 (36-40) ─────────────────────────────────────────────
        36 => ['xp' => 830_000_000,  'emoji' => '💀', 'name' => '심연'],
        37 => ['xp' => 1_270_000_000,'emoji' => '🕯️', 'name' => '영겁'],
        38 => ['xp' => 1_940_000_000,'emoji' => '🔯', 'name' => '각인된 자'],
        39 => ['xp' => 2_970_000_000,'emoji' => '⚗️', 'name' => '연금술사'],
        40 => ['xp' => 4_540_000_000,'emoji' => '🌀', 'name' => '차원 이동자'],
        // ── 신계 (41-45) ─────────────────────────────────────────────
        41 => ['xp' => 6_940_000_000, 'emoji' => '🐉', 'name' => '고룡'],
        42 => ['xp' => 10_610_000_000,'emoji' => '🦅', 'name' => '창공의 지배자'],
        43 => ['xp' => 16_230_000_000,'emoji' => '🌑', 'name' => '암흑성'],
        44 => ['xp' => 24_820_000_000,'emoji' => '✨', 'name' => '빛의 화신'],
        45 => ['xp' => 37_970_000_000,'emoji' => '🌞', 'name' => '태양의 신'],
        // ── 창조주 (46-50) ───────────────────────────────────────────
        46 => ['xp' => 58_080_000_000, 'emoji' => '💠', 'name' => '결정체'],
        47 => ['xp' => 88_860_000_000, 'emoji' => '🔮', 'name' => '예언자'],
        48 => ['xp' => 135_950_000_000,'emoji' => '🌠', 'name' => '별의 화신'],
        49 => ['xp' => 207_970_000_000,'emoji' => '🪐', 'name' => '행성 지배자'],
        50 => ['xp' => 318_190_000_000,'emoji' => '👑', 'name' => '창조주'],
    ];

    /**
     * 100개 뱃지 정의 (badge_key => [emoji, name, desc, category])
     *
     * @var array<string, array{emoji: string, name: string, desc: string, category: string}>
     */
    public const BADGES = [
        // ── 게시글 (10) ──────────────────────────────────────────────
        'first_post'        => ['emoji' => '🌱', 'name' => '첫 걸음',        'desc' => '게시글 1개 이상 작성',          'category' => 'post'],
        'writer_5'          => ['emoji' => '📝', 'name' => '새내기 작가',     'desc' => '게시글 5개 이상 작성',          'category' => 'post'],
        'writer_10'         => ['emoji' => '✍️', 'name' => '활발한 작가',     'desc' => '게시글 10개 이상 작성',         'category' => 'post'],
        'writer_30'         => ['emoji' => '📓', 'name' => '꾸준한 작가',     'desc' => '게시글 30개 이상 작성',         'category' => 'post'],
        'writer_100'        => ['emoji' => '📚', 'name' => '백 편의 글',      'desc' => '게시글 100개 이상 작성',        'category' => 'post'],
        'writer_300'        => ['emoji' => '📖', 'name' => '다작가',          'desc' => '게시글 300개 이상 작성',        'category' => 'post'],
        'writer_500'        => ['emoji' => '🏛️', 'name' => '전설적 작가',     'desc' => '게시글 500개 이상 작성',        'category' => 'post'],
        'writer_1000'       => ['emoji' => '🖋️', 'name' => '천 편의 역사',   'desc' => '게시글 1,000개 이상 작성',      'category' => 'post'],
        'writer_2000'       => ['emoji' => '⌨️', 'name' => '글의 제왕',      'desc' => '게시글 2,000개 이상 작성',      'category' => 'post'],
        'writer_5000'       => ['emoji' => '📜', 'name' => '필경사',          'desc' => '게시글 5,000개 이상 작성',      'category' => 'post'],
        // ── 댓글 (10) ───────────────────────────────────────────────
        'first_comment'     => ['emoji' => '🗨️', 'name' => '첫 댓글',        'desc' => '댓글 1개 이상 작성',            'category' => 'comment'],
        'commenter_5'       => ['emoji' => '💬', 'name' => '말문 열기',       'desc' => '댓글 5개 이상 작성',            'category' => 'comment'],
        'commenter_20'      => ['emoji' => '💭', 'name' => '수다 시작',       'desc' => '댓글 20개 이상 작성',           'category' => 'comment'],
        'commenter_50'      => ['emoji' => '🗣️', 'name' => '이야기꾼',        'desc' => '댓글 50개 이상 작성',           'category' => 'comment'],
        'commenter_100'     => ['emoji' => '🎤', 'name' => '논쟁 가담자',     'desc' => '댓글 100개 이상 작성',          'category' => 'comment'],
        'commenter_300'     => ['emoji' => '📢', 'name' => '토론 고수',       'desc' => '댓글 300개 이상 작성',          'category' => 'comment'],
        'commenter_500'     => ['emoji' => '📣', 'name' => '댓글 마스터',     'desc' => '댓글 500개 이상 작성',          'category' => 'comment'],
        'commenter_1000'    => ['emoji' => '🔊', 'name' => '천 개의 한마디',  'desc' => '댓글 1,000개 이상 작성',        'category' => 'comment'],
        'commenter_2000'    => ['emoji' => '📡', 'name' => '메아리',          'desc' => '댓글 2,000개 이상 작성',        'category' => 'comment'],
        'commenter_5000'    => ['emoji' => '🌐', 'name' => '언어의 달인',     'desc' => '댓글 5,000개 이상 작성',        'category' => 'comment'],
        // ── 추천 합계 (10) ──────────────────────────────────────────
        'popular_10'        => ['emoji' => '👍', 'name' => '좋아요 시작',     'desc' => '받은 추천 합계 10개 이상',      'category' => 'vote'],
        'popular_50'        => ['emoji' => '🤩', 'name' => '인기 상승 중',    'desc' => '받은 추천 합계 50개 이상',      'category' => 'vote'],
        'popular_100'       => ['emoji' => '⭐', 'name' => '인기인',          'desc' => '받은 추천 합계 100개 이상',     'category' => 'vote'],
        'popular_300'       => ['emoji' => '🌟', 'name' => '스타',            'desc' => '받은 추천 합계 300개 이상',     'category' => 'vote'],
        'popular_500'       => ['emoji' => '💫', 'name' => '인기폭발',        'desc' => '받은 추천 합계 500개 이상',     'category' => 'vote'],
        'popular_1000'      => ['emoji' => '✨', 'name' => '슈퍼스타',        'desc' => '받은 추천 합계 1,000개 이상',   'category' => 'vote'],
        'popular_3000'      => ['emoji' => '🌠', 'name' => '팬덤 보유자',     'desc' => '받은 추천 합계 3,000개 이상',   'category' => 'vote'],
        'popular_5000'      => ['emoji' => '🌌', 'name' => '전설적 인기',     'desc' => '받은 추천 합계 5,000개 이상',   'category' => 'vote'],
        'popular_10000'     => ['emoji' => '🔥', 'name' => '만인의 스타',     'desc' => '받은 추천 합계 10,000개 이상',  'category' => 'vote'],
        'popular_50000'     => ['emoji' => '☄️', 'name' => '추천 신화',       'desc' => '받은 추천 합계 50,000개 이상',  'category' => 'vote'],
        // ── 단건 추천 (6) ───────────────────────────────────────────
        'hot_post_10'       => ['emoji' => '🌶️', 'name' => '화제의 씨앗',    'desc' => '단건 게시글 추천 10개 이상',    'category' => 'hot'],
        'hot_post'          => ['emoji' => '🔥', 'name' => '화제의 글',       'desc' => '단건 게시글 추천 30개 이상',    'category' => 'hot'],
        'hot_post_50'       => ['emoji' => '💥', 'name' => '대박 글',         'desc' => '단건 게시글 추천 50개 이상',    'category' => 'hot'],
        'hot_post_100'      => ['emoji' => '⚡', 'name' => '폭발적 반응',     'desc' => '단건 게시글 추천 100개 이상',   'category' => 'hot'],
        'hot_post_200'      => ['emoji' => '🌪️', 'name' => '바이럴',          'desc' => '단건 게시글 추천 200개 이상',   'category' => 'hot'],
        'hot_post_500'      => ['emoji' => '☄️', 'name' => '역대급 글',       'desc' => '단건 게시글 추천 500개 이상',   'category' => 'hot'],
        // ── 전쟁터 (9) ──────────────────────────────────────────────
        'warrior_1'         => ['emoji' => '🗡️', 'name' => '입대',            'desc' => '전쟁터 게시글 1개 이상',        'category' => 'battle'],
        'warrior_10'        => ['emoji' => '⚔️', 'name' => '전투 경험',       'desc' => '전쟁터 게시글 10개 이상',       'category' => 'battle'],
        'warrior'           => ['emoji' => '🛡️', 'name' => '전사',            'desc' => '전쟁터 게시글 20개 이상',       'category' => 'battle'],
        'warrior_50'        => ['emoji' => '🏹', 'name' => '베테랑 전사',     'desc' => '전쟁터 게시글 50개 이상',       'category' => 'battle'],
        'warrior_100'       => ['emoji' => '💪', 'name' => '전쟁의 고수',     'desc' => '전쟁터 게시글 100개 이상',      'category' => 'battle'],
        'warrior_300'       => ['emoji' => '🌋', 'name' => '전쟁의 신',       'desc' => '전쟁터 게시글 300개 이상',      'category' => 'battle'],
        'warrior_500'       => ['emoji' => '👊', 'name' => '전쟁 영웅',       'desc' => '전쟁터 게시글 500개 이상',      'category' => 'battle'],
        'warrior_1000'      => ['emoji' => '💀', 'name' => '불멸의 전사',     'desc' => '전쟁터 게시글 1,000개 이상',    'category' => 'battle'],
        'warrior_2000'      => ['emoji' => '👹', 'name' => '전장의 악마',     'desc' => '전쟁터 게시글 2,000개 이상',    'category' => 'battle'],
        // ── 아지트 (7) ──────────────────────────────────────────────
        'azit_1'            => ['emoji' => '🏠', 'name' => '아지트 입성',     'desc' => '아지트 게시글 1개 이상',        'category' => 'azit'],
        'azit_5'            => ['emoji' => '🛖', 'name' => '단골손님',         'desc' => '아지트 게시글 5개 이상',        'category' => 'azit'],
        'azit_10'           => ['emoji' => '🏡', 'name' => '아지트 단골',     'desc' => '아지트 게시글 10개 이상',       'category' => 'azit'],
        'azit_30'           => ['emoji' => '🏘️', 'name' => '진영의 일원',     'desc' => '아지트 게시글 30개 이상',       'category' => 'azit'],
        'azit_50'           => ['emoji' => '🛡️', 'name' => '아지트 수호자',   'desc' => '아지트 게시글 50개 이상',       'category' => 'azit'],
        'azit_100'          => ['emoji' => '🗼', 'name' => '진영의 기둥',     'desc' => '아지트 게시글 100개 이상',      'category' => 'azit'],
        'azit_200'          => ['emoji' => '🏰', 'name' => '아지트의 왕',     'desc' => '아지트 게시글 200개 이상',      'category' => 'azit'],
        // ── 놀이터 (7) ──────────────────────────────────────────────
        'playground_1'      => ['emoji' => '🎮', 'name' => '놀이터 입성',     'desc' => '놀이터 게시글 1개 이상',        'category' => 'playground'],
        'playground_5'      => ['emoji' => '🎯', 'name' => '오락 시작',       'desc' => '놀이터 게시글 5개 이상',        'category' => 'playground'],
        'playground_10'     => ['emoji' => '🎡', 'name' => '놀이터 단골',     'desc' => '놀이터 게시글 10개 이상',       'category' => 'playground'],
        'playground_30'     => ['emoji' => '🎪', 'name' => '재미 추구자',     'desc' => '놀이터 게시글 30개 이상',       'category' => 'playground'],
        'playground_50'     => ['emoji' => '🎭', 'name' => '엔터테이너',      'desc' => '놀이터 게시글 50개 이상',       'category' => 'playground'],
        'playground_100'    => ['emoji' => '🎬', 'name' => '놀이터 챔피언',   'desc' => '놀이터 게시글 100개 이상',      'category' => 'playground'],
        'playground_200'    => ['emoji' => '🎠', 'name' => '놀이의 달인',     'desc' => '놀이터 게시글 200개 이상',      'category' => 'playground'],
        // ── 조회수 (7) ──────────────────────────────────────────────
        'view_500'          => ['emoji' => '👀', 'name' => '주목받기 시작',   'desc' => '총 조회수 500회 이상',          'category' => 'view'],
        'view_1000'         => ['emoji' => '👁️', 'name' => '조회 스타',       'desc' => '총 조회수 1,000회 이상',        'category' => 'view'],
        'view_5000'         => ['emoji' => '🔍', 'name' => '많이 읽힌 글',    'desc' => '총 조회수 5,000회 이상',        'category' => 'view'],
        'view_10000'        => ['emoji' => '🌐', 'name' => '만회 돌파',       'desc' => '총 조회수 10,000회 이상',       'category' => 'view'],
        'view_30000'        => ['emoji' => '🌍', 'name' => '인기 작가',       'desc' => '총 조회수 30,000회 이상',       'category' => 'view'],
        'view_100000'       => ['emoji' => '🌏', 'name' => '십만 독자',       'desc' => '총 조회수 100,000회 이상',      'category' => 'view'],
        'view_500000'       => ['emoji' => '🌌', 'name' => '조회수 신화',     'desc' => '총 조회수 500,000회 이상',      'category' => 'view'],
        // ── 매너 점수 (8) ───────────────────────────────────────────
        'manner_105'        => ['emoji' => '😊', 'name' => '예의 바른 시작',  'desc' => '매너 점수 105점 이상',          'category' => 'manner'],
        'manner_110'        => ['emoji' => '😇', 'name' => '매너 있는 유저',  'desc' => '매너 점수 110점 이상',          'category' => 'manner'],
        'manner_120'        => ['emoji' => '🕊️', 'name' => '평화주의자',      'desc' => '매너 점수 120점 이상',          'category' => 'manner'],
        'manner_130'        => ['emoji' => '🌸', 'name' => '매너리스트',      'desc' => '매너 점수 130점 이상',          'category' => 'manner'],
        'manner_150'        => ['emoji' => '💝', 'name' => '매너왕',          'desc' => '매너 점수 150점 이상',          'category' => 'manner'],
        'manner_180'        => ['emoji' => '🙏', 'name' => '진정한 신사',     'desc' => '매너 점수 180점 이상',          'category' => 'manner'],
        'manner_200'        => ['emoji' => '👼', 'name' => '성인군자',        'desc' => '매너 점수 200점 이상',          'category' => 'manner'],
        'manner_250'        => ['emoji' => '🌈', 'name' => '완전무결',        'desc' => '매너 점수 250점 이상',          'category' => 'manner'],
        // ── 레벨 달성 (10) ──────────────────────────────────────────
        'level5'            => ['emoji' => '📰', 'name' => '논객의 길',       'desc' => '레벨 5 달성',                   'category' => 'level'],
        'level10'           => ['emoji' => '⚔️', 'name' => '전사의 증명',     'desc' => '레벨 10 달성',                  'category' => 'level'],
        'level15'           => ['emoji' => '🌟', 'name' => '스타의 반열',     'desc' => '레벨 15 달성',                  'category' => 'level'],
        'level20'           => ['emoji' => '⭐', 'name' => '슈퍼스타',        'desc' => '레벨 20 달성',                  'category' => 'level'],
        'level25'           => ['emoji' => '🦋', 'name' => '각성의 시작',     'desc' => '레벨 25 달성',                  'category' => 'level'],
        'level30'           => ['emoji' => '👑', 'name' => '폴릿의 신',       'desc' => '레벨 30 달성',                  'category' => 'level'],
        'level35'           => ['emoji' => '🦄', 'name' => '전설의 시작',     'desc' => '레벨 35 달성',                  'category' => 'level'],
        'level40'           => ['emoji' => '🌀', 'name' => '차원 이동자',     'desc' => '레벨 40 달성',                  'category' => 'level'],
        'level45'           => ['emoji' => '🌞', 'name' => '태양의 신',       'desc' => '레벨 45 달성',                  'category' => 'level'],
        'level50'           => ['emoji' => '💠', 'name' => '창조주',          'desc' => '레벨 50 달성',                  'category' => 'level'],
        // ── 특별 (16) ───────────────────────────────────────────────
        'test_taker'        => ['emoji' => '🧭', 'name' => '성향 탐구자',     'desc' => '정치 성향 테스트 완료',         'category' => 'special'],
        'all_rounder'       => ['emoji' => '🌈', 'name' => '올라운더',        'desc' => '아지트·전쟁터·놀이터 모두 1개 이상', 'category' => 'special'],
        'vet_30days'        => ['emoji' => '📅', 'name' => '한 달의 기록',    'desc' => '가입 30일 이상',                'category' => 'special'],
        'vet_100days'       => ['emoji' => '🗓️', 'name' => '100일의 인연',   'desc' => '가입 100일 이상',               'category' => 'special'],
        'vet_365days'       => ['emoji' => '🎂', 'name' => '1주년',           'desc' => '가입 1년 이상',                 'category' => 'special'],
        'vet_3years'        => ['emoji' => '🎊', 'name' => '3주년',           'desc' => '가입 3년 이상',                 'category' => 'special'],
        'hot_commenter'     => ['emoji' => '💬', 'name' => '댓글 반응왕',     'desc' => '댓글 추천 합계 100개 이상',     'category' => 'special'],
        'hot_commenter_500' => ['emoji' => '🎤', 'name' => '댓글 스타',       'desc' => '댓글 추천 합계 500개 이상',     'category' => 'special'],
        'triple_winner'     => ['emoji' => '🏆', 'name' => '삼관왕',          'desc' => '아지트·전쟁터·놀이터 각 50개 이상', 'category' => 'special'],
        'popular_100000'    => ['emoji' => '🌟', 'name' => '추천 10만',       'desc' => '받은 추천 합계 100,000개 이상', 'category' => 'special'],
        'view_1000000'      => ['emoji' => '🌌', 'name' => '백만 조회',       'desc' => '총 조회수 1,000,000회 이상',    'category' => 'special'],
        'commenter_10000'   => ['emoji' => '🌀', 'name' => '만 개의 댓글',    'desc' => '댓글 10,000개 이상 작성',       'category' => 'special'],
        'writer_3000'       => ['emoji' => '🏆', 'name' => '글의 전설',       'desc' => '게시글 3,000개 이상 작성',      'category' => 'special'],
        'warrior_3000'      => ['emoji' => '👿', 'name' => '전쟁의 화신',     'desc' => '전쟁터 게시글 3,000개 이상',    'category' => 'special'],
        'azit_300'          => ['emoji' => '💎', 'name' => '아지트 전설',     'desc' => '아지트 게시글 300개 이상',      'category' => 'special'],
        'playground_300'    => ['emoji' => '🎆', 'name' => '놀이터 전설',     'desc' => '놀이터 게시글 300개 이상',      'category' => 'special'],
    ];

    /**
     * 사용자의 XP를 재계산하고 레벨 및 배지를 동기화합니다.
     */
    public function syncUser(User $user): void
    {
        $xp    = $this->calculateXp($user);
        $level = $this->levelFromXp($xp);

        $user->experience_points = $xp;
        $user->level             = $level;
        $user->save();

        $postCount    = $this->publishedPostCount($user);
        $commentCount = (int) $user->comments()->count();
        $totalVotes   = $this->totalReceivedVotes($user);

        $this->checkAndAwardBadges($user, $postCount, $commentCount, $totalVotes, $level);
    }

    /**
     * 게시판 유형별 가중치를 적용해 XP를 계산합니다.
     */
    public function calculateXp(User $user): int
    {
        $status = PostStatus::Published->value;

        $postsByType = Post::where('posts.user_id', $user->id)
            ->where('posts.status', $status)
            ->join('boards', 'posts.board_id', '=', 'boards.id')
            ->selectRaw('boards.board_type, COUNT(*) as cnt')
            ->groupBy('boards.board_type')
            ->pluck('cnt', 'board_type')
            ->toArray();

        $commentsByType = Comment::where('comments.user_id', $user->id)
            ->join('posts', 'comments.post_id', '=', 'posts.id')
            ->join('boards', 'posts.board_id', '=', 'boards.id')
            ->selectRaw('boards.board_type, COUNT(*) as cnt')
            ->groupBy('boards.board_type')
            ->pluck('cnt', 'board_type')
            ->toArray();

        $xp = 0;
        foreach (self::XP_RATES as $type => $rates) {
            $xp += ($postsByType[$type]    ?? 0) * $rates['post'];
            $xp += ($commentsByType[$type] ?? 0) * $rates['comment'];
        }
        $xp += $this->totalReceivedVotes($user) * self::XP_VOTE_UP;

        return $xp;
    }

    /**
     * XP로 레벨을 반환합니다.
     */
    public function levelFromXp(int $xp): int
    {
        $level = 1;
        foreach (self::LEVELS as $lvl => $info) {
            if ($xp >= $info['xp']) {
                $level = $lvl;
            }
        }
        return $level;
    }

    /**
     * 레벨 정보를 반환합니다.
     *
     * @return array{emoji: string, name: string, current_xp: int, next_xp: int|null}
     */
    public function levelInfo(int $level): array
    {
        $info   = self::LEVELS[$level] ?? self::LEVELS[1];
        $nextXp = isset(self::LEVELS[$level + 1]) ? self::LEVELS[$level + 1]['xp'] : null;

        return [
            'emoji'      => $info['emoji'],
            'name'       => $info['name'],
            'current_xp' => $info['xp'],
            'next_xp'    => $nextXp,
        ];
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function publishedPostCount(User $user): int
    {
        return (int) $user->posts()
            ->where('status', PostStatus::Published->value)
            ->count();
    }

    private function totalReceivedVotes(User $user): int
    {
        $postVotes    = (int) $user->posts()
            ->where('status', PostStatus::Published->value)
            ->sum('vote_up_count');
        $commentVotes = (int) $user->comments()->sum('vote_up_count');

        return $postVotes + $commentVotes;
    }

    private function commentReceivedVotes(User $user): int
    {
        return (int) $user->comments()->sum('vote_up_count');
    }

    /**
     * 배지 조건을 확인하고 신규 배지를 부여합니다.
     */
    private function checkAndAwardBadges(
        User $user,
        int  $postCount,
        int  $commentCount,
        int  $totalVotes,
        int  $level
    ): void {
        $existing = UserBadge::where('user_id', $user->id)
            ->pluck('badge_key')
            ->flip()
            ->toArray();

        $toAward = [];
        $now     = Carbon::now();
        $uid     = $user->id;
        $manner  = $user->manner_score ?? 0;
        $status  = PostStatus::Published->value;

        $entry = fn (string $key): array => [
            'user_id'    => $uid,
            'badge_key'  => $key,
            'awarded_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        // 지연 계산 클로저 ────────────────────────────────────────────
        $battleCount = $azitCount = $playgroundCount = $maxVote = $totalViews = null;
        $commentVotes = null;

        $getBattle = function () use (&$battleCount, $uid, $status): int {
            return $battleCount ??= (int) Post::where('user_id', $uid)
                ->where('status', $status)
                ->whereHas('board', fn ($q) => $q->where('board_type', BoardType::Battle->value))
                ->count();
        };
        $getAzit = function () use (&$azitCount, $uid, $status): int {
            return $azitCount ??= (int) Post::where('user_id', $uid)
                ->where('status', $status)
                ->whereHas('board', fn ($q) => $q->where('board_type', BoardType::Azit->value))
                ->count();
        };
        $getPlayground = function () use (&$playgroundCount, $uid, $status): int {
            return $playgroundCount ??= (int) Post::where('user_id', $uid)
                ->where('status', $status)
                ->whereHas('board', fn ($q) => $q->where('board_type', BoardType::Playground->value))
                ->count();
        };
        $getMaxVote = function () use (&$maxVote, $uid, $status): int {
            return $maxVote ??= (int) Post::where('user_id', $uid)
                ->where('status', $status)
                ->max('vote_up_count');
        };
        $getTotalViews = function () use (&$totalViews, $uid, $status): int {
            return $totalViews ??= (int) Post::where('user_id', $uid)
                ->where('status', $status)
                ->sum('view_count');
        };
        $getCommentVotes = function () use (&$commentVotes, $user): int {
            return $commentVotes ??= $this->commentReceivedVotes($user);
        };

        $daysSinceJoin = (int) $user->created_at?->diffInDays(now());

        // ── 게시글 ────────────────────────────────────────────────────
        if ($postCount >= 1    && ! isset($existing['first_post']))   { $toAward[] = $entry('first_post'); }
        if ($postCount >= 5    && ! isset($existing['writer_5']))     { $toAward[] = $entry('writer_5'); }
        if ($postCount >= 10   && ! isset($existing['writer_10']))    { $toAward[] = $entry('writer_10'); }
        if ($postCount >= 30   && ! isset($existing['writer_30']))    { $toAward[] = $entry('writer_30'); }
        if ($postCount >= 100  && ! isset($existing['writer_100']))   { $toAward[] = $entry('writer_100'); }
        if ($postCount >= 300  && ! isset($existing['writer_300']))   { $toAward[] = $entry('writer_300'); }
        if ($postCount >= 500  && ! isset($existing['writer_500']))   { $toAward[] = $entry('writer_500'); }
        if ($postCount >= 1000 && ! isset($existing['writer_1000']))  { $toAward[] = $entry('writer_1000'); }
        if ($postCount >= 2000 && ! isset($existing['writer_2000']))  { $toAward[] = $entry('writer_2000'); }
        if ($postCount >= 3000 && ! isset($existing['writer_3000']))  { $toAward[] = $entry('writer_3000'); }
        if ($postCount >= 5000 && ! isset($existing['writer_5000']))  { $toAward[] = $entry('writer_5000'); }

        // ── 댓글 ──────────────────────────────────────────────────────
        if ($commentCount >= 1     && ! isset($existing['first_comment']))   { $toAward[] = $entry('first_comment'); }
        if ($commentCount >= 5     && ! isset($existing['commenter_5']))     { $toAward[] = $entry('commenter_5'); }
        if ($commentCount >= 20    && ! isset($existing['commenter_20']))    { $toAward[] = $entry('commenter_20'); }
        if ($commentCount >= 50    && ! isset($existing['commenter_50']))    { $toAward[] = $entry('commenter_50'); }
        if ($commentCount >= 100   && ! isset($existing['commenter_100']))   { $toAward[] = $entry('commenter_100'); }
        if ($commentCount >= 300   && ! isset($existing['commenter_300']))   { $toAward[] = $entry('commenter_300'); }
        if ($commentCount >= 500   && ! isset($existing['commenter_500']))   { $toAward[] = $entry('commenter_500'); }
        if ($commentCount >= 1000  && ! isset($existing['commenter_1000']))  { $toAward[] = $entry('commenter_1000'); }
        if ($commentCount >= 2000  && ! isset($existing['commenter_2000']))  { $toAward[] = $entry('commenter_2000'); }
        if ($commentCount >= 5000  && ! isset($existing['commenter_5000']))  { $toAward[] = $entry('commenter_5000'); }
        if ($commentCount >= 10000 && ! isset($existing['commenter_10000'])) { $toAward[] = $entry('commenter_10000'); }

        // ── 추천 합계 ─────────────────────────────────────────────────
        if ($totalVotes >= 10     && ! isset($existing['popular_10']))     { $toAward[] = $entry('popular_10'); }
        if ($totalVotes >= 50     && ! isset($existing['popular_50']))     { $toAward[] = $entry('popular_50'); }
        if ($totalVotes >= 100    && ! isset($existing['popular_100']))    { $toAward[] = $entry('popular_100'); }
        if ($totalVotes >= 300    && ! isset($existing['popular_300']))    { $toAward[] = $entry('popular_300'); }
        if ($totalVotes >= 500    && ! isset($existing['popular_500']))    { $toAward[] = $entry('popular_500'); }
        if ($totalVotes >= 1000   && ! isset($existing['popular_1000']))   { $toAward[] = $entry('popular_1000'); }
        if ($totalVotes >= 3000   && ! isset($existing['popular_3000']))   { $toAward[] = $entry('popular_3000'); }
        if ($totalVotes >= 5000   && ! isset($existing['popular_5000']))   { $toAward[] = $entry('popular_5000'); }
        if ($totalVotes >= 10000  && ! isset($existing['popular_10000']))  { $toAward[] = $entry('popular_10000'); }
        if ($totalVotes >= 50000  && ! isset($existing['popular_50000']))  { $toAward[] = $entry('popular_50000'); }
        if ($totalVotes >= 100000 && ! isset($existing['popular_100000'])) { $toAward[] = $entry('popular_100000'); }

        // ── 단건 추천 ─────────────────────────────────────────────────
        $needHot = ! isset($existing['hot_post_10'])  || ! isset($existing['hot_post'])    ||
                   ! isset($existing['hot_post_50'])  || ! isset($existing['hot_post_100']) ||
                   ! isset($existing['hot_post_200']) || ! isset($existing['hot_post_500']);
        if ($needHot) {
            $mv = $getMaxVote();
            if ($mv >= 10  && ! isset($existing['hot_post_10']))  { $toAward[] = $entry('hot_post_10'); }
            if ($mv >= 30  && ! isset($existing['hot_post']))     { $toAward[] = $entry('hot_post'); }
            if ($mv >= 50  && ! isset($existing['hot_post_50']))  { $toAward[] = $entry('hot_post_50'); }
            if ($mv >= 100 && ! isset($existing['hot_post_100'])) { $toAward[] = $entry('hot_post_100'); }
            if ($mv >= 200 && ! isset($existing['hot_post_200'])) { $toAward[] = $entry('hot_post_200'); }
            if ($mv >= 500 && ! isset($existing['hot_post_500'])) { $toAward[] = $entry('hot_post_500'); }
        }

        // ── 전쟁터 ────────────────────────────────────────────────────
        $needBattle = ! isset($existing['warrior_1'])    || ! isset($existing['warrior_10'])   ||
                      ! isset($existing['warrior'])      || ! isset($existing['warrior_50'])    ||
                      ! isset($existing['warrior_100'])  || ! isset($existing['warrior_300'])   ||
                      ! isset($existing['warrior_500'])  || ! isset($existing['warrior_1000'])  ||
                      ! isset($existing['warrior_2000']) || ! isset($existing['warrior_3000']);
        if ($needBattle) {
            $bc = $getBattle();
            if ($bc >= 1    && ! isset($existing['warrior_1']))    { $toAward[] = $entry('warrior_1'); }
            if ($bc >= 10   && ! isset($existing['warrior_10']))   { $toAward[] = $entry('warrior_10'); }
            if ($bc >= 20   && ! isset($existing['warrior']))      { $toAward[] = $entry('warrior'); }
            if ($bc >= 50   && ! isset($existing['warrior_50']))   { $toAward[] = $entry('warrior_50'); }
            if ($bc >= 100  && ! isset($existing['warrior_100']))  { $toAward[] = $entry('warrior_100'); }
            if ($bc >= 300  && ! isset($existing['warrior_300']))  { $toAward[] = $entry('warrior_300'); }
            if ($bc >= 500  && ! isset($existing['warrior_500']))  { $toAward[] = $entry('warrior_500'); }
            if ($bc >= 1000 && ! isset($existing['warrior_1000'])) { $toAward[] = $entry('warrior_1000'); }
            if ($bc >= 2000 && ! isset($existing['warrior_2000'])) { $toAward[] = $entry('warrior_2000'); }
            if ($bc >= 3000 && ! isset($existing['warrior_3000'])) { $toAward[] = $entry('warrior_3000'); }
        }

        // ── 아지트 ────────────────────────────────────────────────────
        $needAzit = ! isset($existing['azit_1'])   || ! isset($existing['azit_5'])   ||
                    ! isset($existing['azit_10'])  || ! isset($existing['azit_30'])  ||
                    ! isset($existing['azit_50'])  || ! isset($existing['azit_100']) ||
                    ! isset($existing['azit_200']) || ! isset($existing['azit_300']);
        if ($needAzit) {
            $ac = $getAzit();
            if ($ac >= 1   && ! isset($existing['azit_1']))   { $toAward[] = $entry('azit_1'); }
            if ($ac >= 5   && ! isset($existing['azit_5']))   { $toAward[] = $entry('azit_5'); }
            if ($ac >= 10  && ! isset($existing['azit_10']))  { $toAward[] = $entry('azit_10'); }
            if ($ac >= 30  && ! isset($existing['azit_30']))  { $toAward[] = $entry('azit_30'); }
            if ($ac >= 50  && ! isset($existing['azit_50']))  { $toAward[] = $entry('azit_50'); }
            if ($ac >= 100 && ! isset($existing['azit_100'])) { $toAward[] = $entry('azit_100'); }
            if ($ac >= 200 && ! isset($existing['azit_200'])) { $toAward[] = $entry('azit_200'); }
            if ($ac >= 300 && ! isset($existing['azit_300'])) { $toAward[] = $entry('azit_300'); }
        }

        // ── 놀이터 ────────────────────────────────────────────────────
        $needPlay = ! isset($existing['playground_1'])   || ! isset($existing['playground_5'])   ||
                    ! isset($existing['playground_10'])  || ! isset($existing['playground_30'])  ||
                    ! isset($existing['playground_50'])  || ! isset($existing['playground_100']) ||
                    ! isset($existing['playground_200']) || ! isset($existing['playground_300']);
        if ($needPlay) {
            $pc = $getPlayground();
            if ($pc >= 1   && ! isset($existing['playground_1']))   { $toAward[] = $entry('playground_1'); }
            if ($pc >= 5   && ! isset($existing['playground_5']))   { $toAward[] = $entry('playground_5'); }
            if ($pc >= 10  && ! isset($existing['playground_10']))  { $toAward[] = $entry('playground_10'); }
            if ($pc >= 30  && ! isset($existing['playground_30']))  { $toAward[] = $entry('playground_30'); }
            if ($pc >= 50  && ! isset($existing['playground_50']))  { $toAward[] = $entry('playground_50'); }
            if ($pc >= 100 && ! isset($existing['playground_100'])) { $toAward[] = $entry('playground_100'); }
            if ($pc >= 200 && ! isset($existing['playground_200'])) { $toAward[] = $entry('playground_200'); }
            if ($pc >= 300 && ! isset($existing['playground_300'])) { $toAward[] = $entry('playground_300'); }
        }

        // ── 조회수 ────────────────────────────────────────────────────
        $needView = ! isset($existing['view_500'])    || ! isset($existing['view_1000'])   ||
                    ! isset($existing['view_5000'])   || ! isset($existing['view_10000'])  ||
                    ! isset($existing['view_30000'])  || ! isset($existing['view_100000']) ||
                    ! isset($existing['view_500000']) || ! isset($existing['view_1000000']);
        if ($needView) {
            $tv = $getTotalViews();
            if ($tv >= 500     && ! isset($existing['view_500']))     { $toAward[] = $entry('view_500'); }
            if ($tv >= 1000    && ! isset($existing['view_1000']))    { $toAward[] = $entry('view_1000'); }
            if ($tv >= 5000    && ! isset($existing['view_5000']))    { $toAward[] = $entry('view_5000'); }
            if ($tv >= 10000   && ! isset($existing['view_10000']))   { $toAward[] = $entry('view_10000'); }
            if ($tv >= 30000   && ! isset($existing['view_30000']))   { $toAward[] = $entry('view_30000'); }
            if ($tv >= 100000  && ! isset($existing['view_100000']))  { $toAward[] = $entry('view_100000'); }
            if ($tv >= 500000  && ! isset($existing['view_500000']))  { $toAward[] = $entry('view_500000'); }
            if ($tv >= 1000000 && ! isset($existing['view_1000000'])) { $toAward[] = $entry('view_1000000'); }
        }

        // ── 매너 점수 ─────────────────────────────────────────────────
        if ($manner >= 105 && ! isset($existing['manner_105'])) { $toAward[] = $entry('manner_105'); }
        if ($manner >= 110 && ! isset($existing['manner_110'])) { $toAward[] = $entry('manner_110'); }
        if ($manner >= 120 && ! isset($existing['manner_120'])) { $toAward[] = $entry('manner_120'); }
        if ($manner >= 130 && ! isset($existing['manner_130'])) { $toAward[] = $entry('manner_130'); }
        if ($manner >= 150 && ! isset($existing['manner_150'])) { $toAward[] = $entry('manner_150'); }
        if ($manner >= 180 && ! isset($existing['manner_180'])) { $toAward[] = $entry('manner_180'); }
        if ($manner >= 200 && ! isset($existing['manner_200'])) { $toAward[] = $entry('manner_200'); }
        if ($manner >= 250 && ! isset($existing['manner_250'])) { $toAward[] = $entry('manner_250'); }

        // ── 레벨 달성 ─────────────────────────────────────────────────
        if ($level >= 5  && ! isset($existing['level5']))  { $toAward[] = $entry('level5'); }
        if ($level >= 10 && ! isset($existing['level10'])) { $toAward[] = $entry('level10'); }
        if ($level >= 15 && ! isset($existing['level15'])) { $toAward[] = $entry('level15'); }
        if ($level >= 20 && ! isset($existing['level20'])) { $toAward[] = $entry('level20'); }
        if ($level >= 25 && ! isset($existing['level25'])) { $toAward[] = $entry('level25'); }
        if ($level >= 30 && ! isset($existing['level30'])) { $toAward[] = $entry('level30'); }
        if ($level >= 35 && ! isset($existing['level35'])) { $toAward[] = $entry('level35'); }
        if ($level >= 40 && ! isset($existing['level40'])) { $toAward[] = $entry('level40'); }
        if ($level >= 45 && ! isset($existing['level45'])) { $toAward[] = $entry('level45'); }
        if ($level >= 50 && ! isset($existing['level50'])) { $toAward[] = $entry('level50'); }

        // ── 특별 ──────────────────────────────────────────────────────
        // 성향 테스트 완료
        if (! isset($existing['test_taker']) && $user->test_completed_at !== null) {
            $toAward[] = $entry('test_taker');
        }

        // 올라운더: 세 게시판 모두 게시글 1개 이상
        if (! isset($existing['all_rounder'])) {
            if ($getAzit() >= 1 && $getBattle() >= 1 && $getPlayground() >= 1) {
                $toAward[] = $entry('all_rounder');
            }
        }

        // 삼관왕: 세 게시판 각 50개 이상
        if (! isset($existing['triple_winner'])) {
            if ($getAzit() >= 50 && $getBattle() >= 50 && $getPlayground() >= 50) {
                $toAward[] = $entry('triple_winner');
            }
        }

        // 가입 기간
        if ($daysSinceJoin >= 30   && ! isset($existing['vet_30days']))  { $toAward[] = $entry('vet_30days'); }
        if ($daysSinceJoin >= 100  && ! isset($existing['vet_100days'])) { $toAward[] = $entry('vet_100days'); }
        if ($daysSinceJoin >= 365  && ! isset($existing['vet_365days'])) { $toAward[] = $entry('vet_365days'); }
        if ($daysSinceJoin >= 1095 && ! isset($existing['vet_3years']))  { $toAward[] = $entry('vet_3years'); }

        // 댓글 추천
        $needCommentVote = ! isset($existing['hot_commenter']) || ! isset($existing['hot_commenter_500']);
        if ($needCommentVote) {
            $cv = $getCommentVotes();
            if ($cv >= 100 && ! isset($existing['hot_commenter']))     { $toAward[] = $entry('hot_commenter'); }
            if ($cv >= 500 && ! isset($existing['hot_commenter_500'])) { $toAward[] = $entry('hot_commenter_500'); }
        }

        if (! empty($toAward)) {
            UserBadge::insertOrIgnore($toAward);
        }
    }
}
