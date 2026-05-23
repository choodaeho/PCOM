<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Server Side Rendering (SSR)
    |--------------------------------------------------------------------------
    |
    | 활성화 시 PHP가 Node.js SSR 서버(port 13714)에 페이지 렌더링을 요청합니다.
    | Docker 환경에서는 INERTIA_SSR_URL을 컨테이너 내부 hostname으로 설정하세요.
    |
    | 개발:  INERTIA_SSR_ENABLED=true, INERTIA_SSR_URL=http://ssr:13714
    | 운영:  INERTIA_SSR_ENABLED=true, INERTIA_SSR_URL=http://127.0.0.1:13714
    |
    */

    'ssr' => [
        'enabled' => env('INERTIA_SSR_ENABLED', true),
        'url'     => env('INERTIA_SSR_URL', 'http://ssr:13714'),
    ],

];
