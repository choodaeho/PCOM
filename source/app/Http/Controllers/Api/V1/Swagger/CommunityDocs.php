<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Swagger;

use OpenApi\Attributes as OA;

/**
 * 게시판 / 게시글 / 댓글 API Swagger 문서 Annotation.
 */
class CommunityDocs
{
    // ─────────────────────────────────────────────
    // 게시판
    // ─────────────────────────────────────────────

    #[OA\Get(
        path: '/boards',
        tags: ['Boards'],
        summary: '게시판 목록',
        description: '사용자 진영에 따라 접근 가능한 게시판만 반환합니다. 아지트는 본인 진영만 노출.',
        responses: [
            new OA\Response(
                response: 200,
                description: '성공',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'azit', type: 'array', description: '아지트 목록', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'battle', type: 'array', description: '전쟁터 목록', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'notice', type: 'array', description: '공지사항 목록', items: new OA\Items(type: 'object')),
                    ],
                ),
            ),
        ],
    )]
    public function boardIndex(): void {}

    // ─────────────────────────────────────────────
    // 게시글 목록
    // ─────────────────────────────────────────────

    #[OA\Get(
        path: '/boards/{slug}/posts',
        tags: ['Posts'],
        summary: '게시글 목록',
        description: '아지트 게시판은 해당 진영만 접근 가능합니다.',
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 50)),
            new OA\Parameter(
                name: 'sort',
                in: 'query',
                schema: new OA\Schema(type: 'string', enum: ['latest', 'popular', 'views'], default: 'latest'),
            ),
            new OA\Parameter(
                name: 'faction',
                in: 'query',
                description: '진영 필터 (전쟁터에서만 유효)',
                schema: new OA\Schema(type: 'string', enum: ['conservative', 'moderate', 'progressive']),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '성공',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Post')),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                    ],
                ),
            ),
            new OA\Response(response: 403, description: '진영 접근 불가'),
        ],
    )]
    public function postIndex(): void {}

    // ─────────────────────────────────────────────
    // 게시글 작성
    // ─────────────────────────────────────────────

    #[OA\Post(
        path: '/boards/{slug}/posts',
        tags: ['Posts'],
        summary: '게시글 작성',
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'content'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', minLength: 2, maxLength: 300, example: '오늘 경제 뉴스 분석'),
                    new OA\Property(property: 'content', type: 'string', minLength: 10, example: '오늘 발표된 GDP 성장률을 보면...'),
                    new OA\Property(property: 'is_anonymous', type: 'boolean', default: false),
                    new OA\Property(
                        property: 'attachments',
                        type: 'array',
                        maxItems: 5,
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'url', type: 'string', format: 'uri'),
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(property: 'type', type: 'string', enum: ['image', 'file']),
                                new OA\Property(property: 'size', type: 'integer'),
                            ],
                        ),
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: '작성 성공',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'post', ref: '#/components/schemas/Post'),
                    ],
                ),
            ),
            new OA\Response(response: 403, description: '진영 접근 불가'),
        ],
    )]
    public function postStore(): void {}

    // ─────────────────────────────────────────────
    // 게시글 상세
    // ─────────────────────────────────────────────

    #[OA\Get(
        path: '/posts/{post}',
        tags: ['Posts'],
        summary: '게시글 상세 조회',
        description: '조회 시 view_count 자동 증가 (본인 제외).',
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '성공',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'post', ref: '#/components/schemas/Post'),
                        new OA\Property(property: 'my_vote', type: 'string', nullable: true, enum: ['up', 'down', null]),
                    ],
                ),
            ),
            new OA\Response(response: 404, description: '없는 게시글'),
        ],
    )]
    public function postShow(): void {}

    // ─────────────────────────────────────────────
    // 게시글 수정 / 삭제
    // ─────────────────────────────────────────────

    #[OA\Put(
        path: '/posts/{post}',
        tags: ['Posts'],
        summary: '게시글 수정 (본인만)',
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'content', type: 'string'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: '수정 성공'),
            new OA\Response(response: 403, description: '권한 없음'),
        ],
    )]
    public function postUpdate(): void {}

    #[OA\Delete(
        path: '/posts/{post}',
        tags: ['Posts'],
        summary: '게시글 삭제 (본인 또는 관리자)',
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: '삭제 성공'),
            new OA\Response(response: 403, description: '권한 없음'),
        ],
    )]
    public function postDestroy(): void {}

    // ─────────────────────────────────────────────
    // 댓글
    // ─────────────────────────────────────────────

    #[OA\Get(
        path: '/posts/{post}/comments',
        tags: ['Comments'],
        summary: '댓글 목록',
        description: '최상위 댓글과 대댓글(replies)을 중첩 구조로 반환합니다.',
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '성공',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'comments', type: 'array', items: new OA\Items(ref: '#/components/schemas/Comment')),
                        new OA\Property(property: 'total', type: 'integer'),
                    ],
                ),
            ),
        ],
    )]
    public function commentIndex(): void {}

    #[OA\Post(
        path: '/posts/{post}/comments',
        tags: ['Comments'],
        summary: '댓글 / 대댓글 작성',
        description: '`parent_id` 없으면 최상위 댓글, 있으면 대댓글 (1-depth만 허용).',
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content'],
                properties: [
                    new OA\Property(property: 'content', type: 'string', minLength: 2, maxLength: 2000, example: '공감합니다!'),
                    new OA\Property(property: 'parent_id', type: 'integer', nullable: true, example: null),
                    new OA\Property(property: 'is_anonymous', type: 'boolean', default: false),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: '작성 성공'),
            new OA\Response(response: 422, description: '잘못된 parent_id'),
        ],
    )]
    public function commentStore(): void {}
}
