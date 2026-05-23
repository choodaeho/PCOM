/**
 * Laravel Echo + Reverb WebSocket 설정
 *
 * 사용법:
 *   import { echo } from '@/echo'
 *   echo.channel('faction.conservative').listen('FactionScoreUpdated', (e) => { ... })
 *   echo.private(`user.${userId}`).listen('MannerScoreChanged', (e) => { ... })
 *
 * 주의: Reverb 컨테이너(port 8080)가 실행 중일 때만 연결됩니다.
 */

import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

const echo = new Echo({
    broadcaster: 'reverb',
    key:         import.meta.env.VITE_REVERB_APP_KEY,
    wsHost:      import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
    wsPort:      parseInt(import.meta.env.VITE_REVERB_PORT ?? '8080'),
    wssPort:     parseInt(import.meta.env.VITE_REVERB_PORT ?? '443'),
    forceTLS:    (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
})

export { echo }
