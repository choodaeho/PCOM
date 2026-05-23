/**
 * Inertia.js SSR 엔트리포인트
 *
 * `php artisan inertia:start-ssr` 가 이 파일을 빌드한 결과물을 Node.js로 실행합니다.
 * 서버에서 Vue 컴포넌트를 미리 렌더링하여 완성된 HTML을 브라우저에 전달하므로
 * SEO 크롤러(Google/Naver/Kakao 봇)가 콘텐츠를 읽을 수 있습니다.
 *
 * 빌드:
 *   npm run build          # CSR + SSR 동시 빌드 → bootstrap/ssr/ssr.js 생성
 *
 * 실행:
 *   php artisan inertia:start-ssr   # (docker: make ssr-start)
 */

import { createSSRApp, h } from 'vue'
import { renderToString } from '@vue/server-renderer'
import { createInertiaApp } from '@inertiajs/vue3'
import createServer from '@inertiajs/vue3/server'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createPinia } from 'pinia'

const appName = '폴릿'

createServer((page) =>
    createInertiaApp({
        page,
        render: renderToString,
        title: (title) => (title ? `${title} — ${appName}` : appName),
        resolve: (name) =>
            resolvePageComponent(
                `./Pages/${name}.vue`,
                import.meta.glob('./Pages/**/*.vue'),
            ),
        setup({ App, props, plugin }) {
            return createSSRApp({ render: () => h(App, props) })
                .use(plugin)
                .use(createPinia())
        },
    }),
)
