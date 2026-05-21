<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Swagger;

use OpenApi\Attributes as OA;

/**
 * 추천/비추천, 신고 API Swagger 문서 Annotation.
 */
class VoteReportDocs
{
    // ─────────────────────────────────────────────
    // 추천 / 비추천
    // ─────────────────────────────────────────────

    #[OA\Post(
        path: '/posts/{post}/vote',
        tags: ['Votes'],
        summary: '게시글 추천/비추천 토글',
        description: <<<'MD'
            같은 vote_type 재요청 → **취소**
            다른 vote_type 요청 → **변경**
            본인 게시글 투표 불가.
            MD,
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['vote_type'],
                properties: [
                    new OA\Property(property: 'vote_type', type: 'string', enum: ['up', 'down']),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '성공',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'action', type: 'string', enum: ['voted', 'cancelled'], example: 'voted'),
                        new OA\Property(property: 'vote_type', type: 'string', nullable: true, example: 'up'),
                        new OA\Property(property: 'vote_up_count', type: 'integer', example: 18),
                        new OA\Property(property: 'vote_down_count', type: 'integer', example: 3),
                    ],
                ),
            ),
            new OA\Response(response: 422, description: '본인 게시글 투표 불가'),
        ],
    )]
    public function votePost(): void {}

    #[OA\Post(
        path: '/comments/{comment}/vote',
        tags: ['Votes'],
        summary: '댓글 추천/비추천 토글',
        parameters: [
            new OA\Parameter(name: 'comment', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['vote_type'],
                properties: [
                    new OA\Property(property: 'vote_type', type: 'string', enum: ['up', 'down']),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: '성공'),
            new OA\Response(response: 422, description: '본인 댓글 투표 불가'),
        ],
    )]
    public function voteComment(): void {}

    // ─────────────────────────────────────────────
    // 신고
    // ─────────────────────────────────────────────

    #[OA\Post(
        path: '/posts/{post}/report',
        tags: ['Reports'],
        summary: '게시글 신고',
        description: '동일 게시글에 중복 신고 불가. 본인 게시글 신고 불가.',
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['reason'],
                properties: [
                    new OA\Property(
                        property: 'reason',
                        type: 'string',
                        enum: ['hate_speech', 'misinformation', 'spam', 'obscene', 'other'],
                        example: 'hate_speech',
                    ),
                    new OA\Property(property: 'detail', type: 'string', nullable: true, maxLength: 500, example: '특정 집단 혐오 발언 포함'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: '신고 접수'),
            new OA\Response(response: 422, description: '중복 신고 또는 본인 게시글'),
        ],
    )]
    public function reportPost(): void {}

    #[OA\Post(
        path: '/comments/{comment}/report',
        tags: ['Reports'],
        summary: '댓글 신고',
        parameters: [
            new OA\Parameter(name: 'comment', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['reason'],
                properties: [
                    new OA\Property(property: 'reason', type: 'string', enum: ['hate_speech', 'misinformation', 'spam', 'obscene', 'other']),
                    new OA\Property(property: 'detail', type: 'string', nullable: true),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: '신고 접수'),
        ],
    )]
    public function reportComment(): void {}
}
