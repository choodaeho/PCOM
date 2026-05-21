<?php

return [
    'default' => 'default',

    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'Polit API',
            ],

            'routes' => [
                // Swagger UI 접근 경로: /api/documentation
                'api'          => 'api/documentation',
                'docs'         => 'api/docs',
                'oauth2_callback' => 'api/oauth2-callback',
                'middleware' => [
                    'api'  => [],
                    'asset' => [],
                    'docs'  => [],
                    'oauth2_callback' => [],
                ],
                'group_options' => [],
            ],

            'paths' => [
                // 생성된 JSON 저장 경로
                'docs'          => storage_path('api-docs'),
                'docs_json'     => 'api-docs.json',
                'docs_yaml'     => 'api-docs.yaml',
                'annotations'   => [
                    base_path('app/Http/Controllers'),
                ],
                'views'   => base_path('resources/views/vendor/l5-swagger'),
                'base'    => null,
                'swagger_ui_assets_path' => 'vendor/swagger-api/swagger-ui/dist/',
                'excludes' => [],
            ],

            'scanOptions' => [
                // swagger-php v4 (OpenAPI 3.x)
                'default_processors_configuration' => [],
                'analyser'  => null,
                'analysis'  => null,
                'processors'=> [],
                'pattern'   => null,
                'open_api_spec_version' => \L5Swagger\Generator::OPEN_API_DEFAULT_SPEC_VERSION,
            ],

            'securityDefinitions' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type'         => 'http',
                        'scheme'       => 'bearer',
                        'bearerFormat' => 'API Token (Laravel Sanctum)',
                    ],
                ],
                'security' => [
                    ['bearerAuth' => []],
                ],
            ],

            // 개발환경에서 매 요청마다 문서 재생성
            'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', false),

            'generate_yaml_copy' => false,

            // Swagger UI 프록시 신뢰
            'proxy' => false,

            // 추가 설정 포함 파일
            'additional_config_url' => null,

            'operations_sort' => 'alpha',

            'validator_url' => null,

            'ui' => [
                'display' => [
                    'doc_expansion'    => 'none',
                    'filter'           => true,
                    'show_extensions'  => true,
                ],
                'authorization' => [
                    'persist_authorization' => true,
                    'oauth2RedirectUrl' => env('APP_URL') . '/api/oauth2-callback',
                    'initOAuth'         => [
                        'usePkceWithAuthorizationCodeGrant' => true,
                    ],
                ],
            ],

            'constants' => [
                'L5_SWAGGER_CONST_HOST' => env('APP_URL', 'http://localhost'),
            ],
        ],
    ],

    'defaults' => [
        'routes' => [
            'docs'             => 'docs',
            'oauth2_callback'  => 'api/oauth2-callback',
            'middleware'       => ['api' => [], 'asset' => [], 'docs' => [], 'oauth2_callback' => []],
            'group_options'    => [],
        ],

        'paths' => [
            'docs'          => storage_path('api-docs'),
            'docs_json'     => 'api-docs.json',
            'docs_yaml'     => 'api-docs.yaml',
            'annotations'   => [base_path('app')],
            'views'         => base_path('resources/views/vendor/l5-swagger'),
            'base'          => null,
            'swagger_ui_assets_path' => 'vendor/swagger-api/swagger-ui/dist/',
            'excludes'      => [],
        ],

        'scanOptions'         => ['pattern' => null],
        'securityDefinitions' => ['securitySchemes' => [], 'security' => []],
        'generate_always'     => false,
        'generate_yaml_copy'  => false,
        'proxy'               => false,
        'additional_config_url' => null,
        'operations_sort'     => null,
        'validator_url'       => null,
        'ui'                  => ['display' => [], 'authorization' => []],
        'constants'           => [],
    ],
];
