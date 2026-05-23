<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Swagger;

use OpenApi\Attributes as OA;

/**
 * 관리자 API Swagger 문서 Annotation.
 * 모든 엔드포인트는 `is_admin = true` 계정만 접근 가능.
 */
class AdminDocs
{
    // ─────────────────────────────────────────────
    // 관리자 대시보드 요약
    // ─────────────────────────────────────────────

    #[OA\Get(
        path: '/admin/summary',
        tags: ['Admin - Stats'],
        summary: '관리자 대시보드 요약',
        description: '사용자 현황, 게시글 수, 미처리 신고 수, 실시간 진영 점수 등을 반환합니다.',
        responses: [
            new OA\Response(
                response: 200,
                description: '성공',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'users',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total', type: 'integer'),
                                new OA\Property(property: 'today_new', type: 'integer'),
                                new OA\Property(property: 'pending', type: 'integer'),
                                new OA\Property(property: 'suspended', type: 'integer'),
                                new OA\Property(property: 'banned', type: 'integer'),
                            ],
                        ),
                        new OA\Property(property: 'posts', type: 'object'),
                        new OA\Property(
                            property: 'reports',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'pending', type: 'integer', description: '미처리 신고 수'),
                            ],
                        ),
                        new OA\Property(property: 'faction_scores', type: 'object'),
                    ],
                ),
            ),
            new OA\Response(response: 403, description: '관리자 권한 없음'),
        ],
    )]
    public function summary(): void {}

    // ─────────────────────────────────────────────
    // 사용자 관리
    // ─────────────────────────────────────────────

    #[OA\Get(
        path: '/admin/users',
        tags: ['Admin - Users'],
        summary: '사용자 목록 조회',
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', description: '이메일/닉네임 검색', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'faction', in: 'query', schema: new OA\Schema(type: 'string', enum: ['conservative', 'moderate', 'progressive'])),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['pending', 'active', 'suspended', 'banned'])),
        ],
        responses: [
            new OA\Response(response: 200, description: '페이지네이션 사용자 목록'),
        ],
    )]
    public function userIndex(): void {}

    #[OA\Post(
        path: '/admin/users/{user}/suspend',
        tags: ['Admin - Users'],
        summary: '사용자 일시 정지',
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['days', 'reason'],
                properties: [
                    new OA\Property(property: 'days', type: 'integer', minimum: 1, maximum: 365, example: 7),
                    new OA\Property(property: 'reason', type: 'string', example: '욕설 반복 사용'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '정지 완료',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'suspended_until', type: 'string', format: 'date-time'),
                    ],
                ),
            ),
        ],
    )]
    public function userSuspend(): void {}

    #[OA\Post(
        path: '/admin/users/{user}/ban',
        tags: ['Admin - Users'],
        summary: '사용자 영구 차단',
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['reason'],
                properties: [
                    new OA\Property(property: 'reason', type: 'string', example: '허위정보 반복 유포'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: '차단 완료'),
        ],
    )]
    public function userBan(): void {}

    // ─────────────────────────────────────────────
    // 신고 처리
    // ─────────────────────────────────────────────

    #[OA\Get(
        path: '/admin/reports',
        tags: ['Admin - Reports'],
        summary: '신고 목록',
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['pending', 'reviewed', 'actioned', 'dismissed'])),
            new OA\Parameter(name: 'reason', in: 'query', schema: new OA\Schema(type: 'string', enum: ['hate_speech', 'misinformation', 'spam', 'obscene', 'other'])),
        ],
        responses: [
            new OA\Response(response: 200, description: '신고 목록 (페이지네이션)'),
        ],
    )]
    public function reportIndex(): void {}

    #[OA\Post(
        path: '/admin/reports/{report}/action',
        tags: ['Admin - Reports'],
        summary: '신고 처리 (콘텐츠 숨김 + 작성자 manner_score -10)',
        parameters: [
            new OA\Parameter(name: 'report', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'admin_note', type: 'string', nullable: true, example: '혐오 발언 확인'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: '처리 완료'),
        ],
    )]
    public function reportAction(): void {}

    // ─────────────────────────────────────────────
    // 점수 가중치 관리
    // ─────────────────────────────────────────────

    #[OA\Get(
        path: '/admin/score-weights',
        tags: ['Admin - Score Weights'],
        summary: '진영 점수 가중치 목록',
        responses: [
            new OA\Response(
                response: 200,
                description: '성공',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'weights',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'action_type', type: 'string', enum: ['post', 'comment', 'vote_up', 'vote_down', 'report']),
                                    new OA\Property(property: 'weight', type: 'number', format: 'float', example: 3.0),
                                    new OA\Property(property: 'description', type: 'string'),
                                    new OA\Property(property: 'is_active', type: 'boolean'),
                                ],
                            ),
                        ),
                    ],
                ),
            ),
        ],
    )]
    public function weightIndex(): void {}

    #[OA\Put(
        path: '/admin/score-weights/{scoreWeight}',
        tags: ['Admin - Score Weights'],
        summary: '점수 가중치 수정 (변경 즉시 Redis 캐시 무효화)',
        parameters: [
            new OA\Parameter(name: 'scoreWeight', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['weight'],
                properties: [
                    new OA\Property(property: 'weight', type: 'number', format: 'float', minimum: -100, maximum: 100, example: 5.0),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'is_active', type: 'boolean'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: '수정 완료'),
        ],
    )]
    public function weightUpdate(): void {}

    // ─────────────────────────────────────────────
    // 집계 수동 실행
    // ─────────────────────────────────────────────

    #[OA\Post(
        path: '/admin/aggregate/daily',
        tags: ['Admin - Stats'],
        summary: '일간 점수 집계 수동 실행',
        description: '스케줄러 대신 수동으로 집계를 실행합니다. 재집계 또는 테스트 목적.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'date', type: 'string', format: 'date', nullable: true, example: '2026-05-19', description: '기본값: 어제'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: '집계 작업 시작'),
        ],
    )]
    public function aggregateDaily(): void {}
}
