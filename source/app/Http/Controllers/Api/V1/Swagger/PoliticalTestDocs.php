<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Swagger;

use OpenApi\Attributes as OA;

/**
 * 성향 테스트 API Swagger 문서 Annotation.
 */
class PoliticalTestDocs
{
    #[OA\Get(
        path: '/political-test/questions',
        tags: ['Political Test'],
        summary: '성향 테스트 문항 조회',
        description: '가입 후 최초 1회 필수. 로그인 + 계정 활성 상태 필요.',
        responses: [
            new OA\Response(
                response: 200,
                description: '성공',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'questions',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'question', type: 'string', example: '최저임금을 시장 자율에 맡겨야 한다.'),
                                    new OA\Property(
                                        property: 'options',
                                        type: 'array',
                                        items: new OA\Items(
                                            properties: [
                                                new OA\Property(property: 'value', type: 'integer', example: 2),
                                                new OA\Property(property: 'label', type: 'string', example: '매우 찬성'),
                                            ],
                                        ),
                                    ),
                                    new OA\Property(property: 'category', type: 'string', nullable: true, example: '경제'),
                                ],
                            ),
                        ),
                        new OA\Property(property: 'total', type: 'integer', example: 20),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: '미인증'),
        ],
    )]
    public function questions(): void {}

    #[OA\Post(
        path: '/political-test/submit',
        tags: ['Political Test'],
        summary: '성향 테스트 응답 제출',
        description: '모든 문항에 응답 제출 후 진영이 결정됩니다. 재테스트 가능(최신 결과 적용).',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['answers'],
                properties: [
                    new OA\Property(
                        property: 'answers',
                        type: 'object',
                        description: '{ question_id: selected_value } 맵. value 범위: -2 ~ +2',
                        example: ['1' => 2, '2' => -1, '3' => 0, '4' => 1],
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '테스트 완료',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'total_score', type: 'integer', example: 42, description: '-100 ~ +100'),
                        new OA\Property(property: 'result_type', type: 'string', enum: ['conservative', 'moderate', 'progressive']),
                        new OA\Property(property: 'faction_label', type: 'string', example: '보수'),
                        new OA\Property(property: 'faction_color', type: 'string', example: '#378ADD'),
                        new OA\Property(property: 'faction_emoji', type: 'string', example: '🔵'),
                    ],
                ),
            ),
            new OA\Response(response: 400, description: '응답 부족'),
        ],
    )]
    public function submit(): void {}

    #[OA\Get(
        path: '/political-test/result',
        tags: ['Political Test'],
        summary: '최근 성향 테스트 결과 조회',
        responses: [
            new OA\Response(response: 200, description: '성공'),
            new OA\Response(response: 404, description: '테스트 미완료'),
        ],
    )]
    public function result(): void {}
}
