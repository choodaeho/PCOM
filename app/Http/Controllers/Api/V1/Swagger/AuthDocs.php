<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Swagger;

use OpenApi\Attributes as OA;

/**
 * 인증 API Swagger 문서 Annotation.
 */
class AuthDocs
{
    // ─────────────────────────────────────────
    // 이메일 회원가입
    // ─────────────────────────────────────────
    #[OA\Post(
        path: '/auth/register',
        tags: ['Auth'],
        summary: '이메일 회원가입',
        description: '이메일과 비밀번호로 계정을 생성합니다. 성공 시 인증 메일이 발송됩니다.',
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password', 'password_confirmation', 'nickname'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'password1!'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'password1!'),
                    new OA\Property(property: 'nickname', type: 'string', minLength: 2, maxLength: 50, example: '폴릿유저'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: '회원가입 성공',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                    ],
                ),
            ),
            new OA\Response(response: 422, description: '입력값 오류', ref: '#/components/schemas/ValidationError'),
        ],
    )]
    public function register(): void {}

    // ─────────────────────────────────────────
    // 이메일 로그인
    // ─────────────────────────────────────────
    #[OA\Post(
        path: '/auth/login',
        tags: ['Auth'],
        summary: '이메일 로그인',
        description: '이메일/비밀번호로 로그인하고 Bearer Token을 발급받습니다.',
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password1!'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '로그인 성공',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: '1|abc123...'),
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                    ],
                ),
            ),
            new OA\Response(response: 422, description: '인증 실패'),
            new OA\Response(response: 403, description: '계정 정지/차단'),
        ],
    )]
    public function login(): void {}

    // ─────────────────────────────────────────
    // 로그아웃
    // ─────────────────────────────────────────
    #[OA\Post(
        path: '/auth/logout',
        tags: ['Auth'],
        summary: '로그아웃',
        description: '현재 Bearer Token을 무효화합니다.',
        responses: [
            new OA\Response(response: 204, description: '로그아웃 성공'),
            new OA\Response(response: 401, description: '미인증'),
        ],
    )]
    public function logout(): void {}

    // ─────────────────────────────────────────
    // 내 정보 조회
    // ─────────────────────────────────────────
    #[OA\Get(
        path: '/auth/me',
        tags: ['Auth'],
        summary: '내 정보 조회',
        responses: [
            new OA\Response(
                response: 200,
                description: '성공',
                content: new OA\JsonContent(ref: '#/components/schemas/User'),
            ),
            new OA\Response(response: 401, description: '미인증'),
        ],
    )]
    public function me(): void {}

    // ─────────────────────────────────────────
    // 소셜 로그인 URL 반환
    // ─────────────────────────────────────────
    #[OA\Get(
        path: '/auth/social/{provider}',
        tags: ['Auth - Social'],
        summary: '소셜 로그인 리다이렉트 URL 반환',
        description: '프론트엔드에서 이 URL로 팝업/리다이렉트를 열어 소셜 인증을 시작합니다.',
        security: [],
        parameters: [
            new OA\Parameter(
                name: 'provider',
                in: 'path',
                required: true,
                description: '소셜 로그인 제공자',
                schema: new OA\Schema(type: 'string', enum: ['kakao', 'naver', 'google']),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '성공',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'redirect_url', type: 'string', format: 'uri', example: 'https://kauth.kakao.com/oauth/authorize?...'),
                    ],
                ),
            ),
        ],
    )]
    public function socialRedirect(): void {}

    // ─────────────────────────────────────────
    // 소셜 코드 교환
    // ─────────────────────────────────────────
    #[OA\Post(
        path: '/auth/social/{provider}/callback',
        tags: ['Auth - Social'],
        summary: '소셜 인가 코드 교환 → Bearer Token 발급',
        description: '소셜 플랫폼에서 받은 code를 서버에서 교환합니다. 신규 사용자면 자동 회원가입됩니다.',
        security: [],
        parameters: [
            new OA\Parameter(
                name: 'provider',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', enum: ['kakao', 'naver', 'google']),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['code'],
                properties: [
                    new OA\Property(property: 'code', type: 'string', description: '소셜 플랫폼 인가 코드', example: 'abc123xyz'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '로그인 성공',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: '2|xyz456...'),
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                    ],
                ),
            ),
            new OA\Response(response: 422, description: '소셜 코드 오류'),
        ],
    )]
    public function socialCallback(): void {}
}
