<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

/**
 * OpenAPI 3.0 기본 정보 및 공통 스키마 정의.
 * 이 파일은 실제 엔드포인트를 갖지 않는 순수 Annotation 클래스입니다.
 */
#[OA\OpenApi(
    info: new OA\Info(
        version: '1.0.0',
        title: 'Polit API',
        description: <<<'MD'
            # 폴릿(Polit) REST API v1

            정치 성향별 커뮤니티 및 토론 플랫폼 API.

            ## 인증 방식
            - **Bearer Token** (Laravel Sanctum Personal Access Token)
            - 로그인 후 발급된 토큰을 `Authorization: Bearer {token}` 헤더에 포함

            ## 진영(Faction) 구분
            | 값 | 한국어 | 색상 |
            |----|--------|------|
            | `conservative` | 보수 🔵 | #378ADD |
            | `moderate` | 중도 🟣 | #7F77DD |
            | `progressive` | 진보 🔴 | #E24B4A |

            ## 미들웨어 접근 단계
            1. `auth` — 로그인 필수
            2. `verified` — 이메일 인증 완료
            3. `user.active` — 계정 활성 상태
            4. `political.test` — 성향 테스트 완료
            5. `faction.access` — 아지트 진영 접근 권한
            MD,
        contact: new OA\Contact(name: 'Polit Dev Team', email: 'dev@polit.kr'),
        license: new OA\License(name: 'Proprietary'),
    ),
    servers: [
        new OA\Server(url: 'http://localhost/api/v1', description: '로컬 개발 서버'),
        new OA\Server(url: 'https://api.polit.kr/v1', description: '운영 서버'),
    ],
    security: [['bearerAuth' => []]],
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum Personal Access Token',
)]

// ─────────────────────────────────────────────
// 공통 응답 스키마
// ─────────────────────────────────────────────

#[OA\Schema(
    schema: 'MessageResponse',
    description: '단순 메시지 응답',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: '처리가 완료되었습니다.'),
    ],
)]
#[OA\Schema(
    schema: 'ValidationError',
    description: '입력값 유효성 오류 (422)',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: '입력값을 확인해 주세요.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            example: ['email' => ['이미 사용 중인 이메일입니다.']],
        ),
    ],
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    description: '페이지네이션 메타',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 10),
        new OA\Property(property: 'total', type: 'integer', example: 200),
        new OA\Property(property: 'per_page', type: 'integer', example: 20),
    ],
)]

// ─────────────────────────────────────────────
// 도메인 스키마
// ─────────────────────────────────────────────

#[OA\Schema(
    schema: 'User',
    description: '사용자 정보',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
        new OA\Property(property: 'nickname', type: 'string', example: '폴릿유저'),
        new OA\Property(property: 'political_type', type: 'string', enum: ['conservative', 'moderate', 'progressive'], nullable: true),
        new OA\Property(property: 'faction_emoji', type: 'string', example: '🔵'),
        new OA\Property(property: 'is_admin', type: 'boolean', example: false),
        new OA\Property(property: 'test_completed', type: 'boolean', example: true),
        new OA\Property(property: 'email_verified', type: 'boolean', example: true),
    ],
)]
#[OA\Schema(
    schema: 'Post',
    description: '게시글',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: '오늘 뉴스 어떻게 생각하시나요?'),
        new OA\Property(property: 'content', type: 'string', example: '본문 내용...'),
        new OA\Property(property: 'faction', type: 'string', enum: ['conservative', 'moderate', 'progressive']),
        new OA\Property(property: 'view_count', type: 'integer', example: 142),
        new OA\Property(property: 'comment_count', type: 'integer', example: 23),
        new OA\Property(property: 'vote_up_count', type: 'integer', example: 18),
        new OA\Property(property: 'vote_down_count', type: 'integer', example: 3),
        new OA\Property(property: 'is_notice', type: 'boolean', example: false),
        new OA\Property(property: 'is_anonymous', type: 'boolean', example: false),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
)]
#[OA\Schema(
    schema: 'Comment',
    description: '댓글',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 10),
        new OA\Property(property: 'content', type: 'string', example: '좋은 글이네요!'),
        new OA\Property(property: 'faction', type: 'string', enum: ['conservative', 'moderate', 'progressive']),
        new OA\Property(property: 'parent_id', type: 'integer', nullable: true, example: null),
        new OA\Property(property: 'vote_up_count', type: 'integer', example: 5),
        new OA\Property(property: 'is_anonymous', type: 'boolean', example: false),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
)]
#[OA\Schema(
    schema: 'FactionScore',
    description: '진영 점수',
    properties: [
        new OA\Property(property: 'faction', type: 'string', enum: ['conservative', 'moderate', 'progressive']),
        new OA\Property(property: 'label', type: 'string', example: '보수'),
        new OA\Property(property: 'emoji', type: 'string', example: '🔵'),
        new OA\Property(property: 'color', type: 'string', example: '#378ADD'),
        new OA\Property(property: 'normalized_score', type: 'number', format: 'float', example: 3.142857),
        new OA\Property(property: 'rank', type: 'integer', example: 1),
    ],
)]
class SwaggerInfo {}
