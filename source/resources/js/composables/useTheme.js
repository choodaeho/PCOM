import { ref, computed, onMounted, onUnmounted } from 'vue'

const STORAGE_KEY = 'polit-theme'

// 모듈 수준 싱글톤 — 여러 컴포넌트가 동일 ref를 공유
const theme = ref('auto')  // 'light' | 'dark' | 'auto'

function currentHour() {
    return new Date().getHours()
}

/** 오토 모드 기준: 18:00 ~ 05:59 → 다크 */
function autoDark() {
    const h = currentHour()
    return h >= 18 || h < 6
}

/** document.documentElement에 dark 클래스 토글 */
function applyTheme(t) {
    const dark = t === 'dark' || (t === 'auto' && autoDark())
    document.documentElement.classList.toggle('dark', dark)
}

export function useTheme() {
    let timer = null

    onMounted(() => {
        // localStorage에서 복원
        const stored = localStorage.getItem(STORAGE_KEY) || 'auto'
        theme.value = stored
        applyTheme(stored)

        // 오토 모드: 1분마다 시간 재확인
        timer = setInterval(() => {
            if (theme.value === 'auto') {
                applyTheme('auto')
            }
        }, 60_000)
    })

    onUnmounted(() => {
        if (timer !== null) {
            clearInterval(timer)
            timer = null
        }
    })

    function setTheme(t) {
        theme.value = t
        localStorage.setItem(STORAGE_KEY, t)
        applyTheme(t)
    }

    /** 현재 실제 다크 여부 */
    const isDark = computed(() => {
        if (theme.value === 'dark') return true
        if (theme.value === 'light') return false
        return autoDark()
    })

    return { theme, isDark, setTheme }
}
