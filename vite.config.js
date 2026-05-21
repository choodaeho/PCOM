import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: ['resources/js/app.js'],
            ssr: 'resources/js/ssr.js',   // SSR 엔트리포인트
            refresh: true,
        }),
        vue({ template: { transformAssetUrls: { base: null, includeAbsolute: false } } }),
    ],

    // SSR 빌드 설정
    ssr: {
        noExternal: ['@inertiajs/vue3'],
    },

    // 개발 서버 (Docker + WSL2 HMR)
    server: {
        host: '0.0.0.0',
        port: parseInt(process.env.VITE_PORT ?? '5173'),
        hmr: {
            host: process.env.VITE_HMR_HOST ?? 'localhost',
            port: parseInt(process.env.VITE_PORT ?? '5173'),
        },
    },
});
