<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Swagger;

use OpenApi\Attributes as OA;

/**
 * 실시간 투표(Poll) 및 통계 API Swagger 문서.
 */
class PollStatsDocs
{
    // ─────────────────────────────────────────────
    // 실시간 투표
    // ─────────────────────────────────────────────

    #[OA\Get(
        path: '/polls/active',
        tags: ['Polls'],
        summary: '현재 진행 중인 투표 조회',
        description: '전쟁터 상단 위젯에 표시되는 활성 투표 + 진영별 현황을 반환합니다.',
        responses: [
            new OA\Response(
                response: 200,
                description: '성공',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'poll',
                            nullable: true,
                            description: '진행 중인 투표 없으면 null',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'title', type: 'string', example: '차기 대선 지지 후보는?'),
                                new OA\Property(
                                    property: 'options',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer'),
                                            new OA\Property(property: 'label', type: 'string'),
                                            new OA\Property(property: 'vote_count', type: 'integer'),
                                        ],
                                    ),
                                ),
                                new OA\Property(property: 'total_vote_count', type: 'integer'),
                                new OA\Property(property: 'ends_at', type: 'string', format: 'date-time', nullable: true),
                            ],
                            type: 'object',
                        ),
                        new OA\Property(property: 'my_option', type: 'integer', nullable: true, example: 1),
                        new OA\Property(
                            property: 'stats',
                            description: '진영별 option_id → 투표 수 맵',
                            type: 'object',
                            example: ['conservative' => ['1' => 42, '2' => 15], 'moderate' => ['1' => 20, '2' => 30]],
                        ),
                    ],
                ),
            ),
        ],
    )]
    public function pollActive(): void {}

    #[OA\Post(
        path: '/polls/{poll}/vote',
        tags: ['Polls'],
        summary: '투표 참여 (1인 1회)',
        parameters: [
            new OA\Parameter(name: 'poll', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['option_id'],
                properties: [
                    new OA\Property(property: 'option_id', type: 'integer', description: 'polls.options[].id', example: 1),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '투표 완료',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'stats', type: 'object'),
                    ],
                ),
            ),
            new OA\Response(response: 422, description: '중복 투표 또는 종료된 투표'),
        ],
    )]
    public function pollVote(): void {}

    // ─────────────────────────────────────────────
    // 진영 점수 통계
    // ─────────────────────────────────────────────

    #[OA\Get(
        path: '/stats/realtime',
        tags: ['Stats'],
        summary: '진영 실시간 점수',
        description: '모든 페이지 헤더 위젯용. 비로그인 접근 가능. Redis 기반 1분 캐시.',
        security: [],
        responses: [
            new OA\Response(
                response: 200,
                description: '성공',
                headers: [
                    new OA\Header(header: 'Cache-Control', description: 'public, max-age=60', schema: new OA\Schema(type: 'string')),
                ],
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'scores',
                            type: 'array',
                            description: '정규화 점수 내림차순 정렬 (1위 = index 0)',
                            items: new OA\Items(ref: '#/components/schemas/FactionScore'),
                        ),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                    ],
                ),
            ),
        ],
    )]
    public function statsRealtime(): void {}

    #[OA\Get(
        path: '/stats/daily',
        tags: ['Stats'],
        summary: '일간 진영 점수 이력',
        parameters: [
            new OA\Parameter(
                name: 'days',
                in: 'query',
                description: '조회 기간 (최대 90일)',
                schema: new OA\Schema(type: 'integer', default: 30, maximum: 90),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '성공',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object', description: '날짜별 진영 통계 맵'),
                        new OA\Property(property: 'period_days', type: 'integer'),
                    ],
                ),
            ),
        ],
    )]
    public function statsDaily(): void {}

    #[OA\Get(
        path: '/stats/monthly',
        tags: ['Stats'],
        summary: '월간 진영 점수 이력',
        parameters: [
            new OA\Parameter(name: 'months', in: 'query', schema: new OA\Schema(type: 'integer', default: 12, maximum: 36)),
        ],
        responses: [
            new OA\Response(response: 200, description: '성공'),
        ],
    )]
    public function statsMonthly(): void {}

    #[OA\Get(
        path: '/stats/yearly',
        tags: ['Stats'],
        summary: '연간 진영 점수 이력',
        parameters: [
            new OA\Parameter(name: 'years', in: 'query', schema: new OA\Schema(type: 'integer', default: 5, maximum: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: '성공'),
        ],
    )]
    public function statsYearly(): void {}
}
