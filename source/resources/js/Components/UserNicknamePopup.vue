<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { getFactionPillClass, getGrayPillClass } from '@/composables/useFactionPill'

const props = defineProps({
    userId:        { type: Number,  default: null },
    nickname:      { type: String,  default: '알 수 없음' },
    levelEmoji:    { type: String,  default: '🌱' },
    level:         { type: Number,  default: 1 },
    politicalType: { type: String,  default: null },  // 'conservative'|'moderate'|'progressive'|null
    pillSm:        { type: Boolean, default: false },  // 작은 사이즈 (댓글/답글용)
})

const showPopup  = ref(false)
const opensAbove = ref(false)   // true: 팝업이 닉네임 위쪽에 열림
const triggerRef = ref(null)
const popupPos   = ref({ top: '0px', left: '0px' })

// ── 팝업 예상 높이 / 너비 (팝업 열기 전에 스크린 공간 계산용) ──
const POPUP_H = 160  // 팝업 높이 근사값 (px)
const POPUP_W = 208  // w-52

// ── 진영 메타 ──────────────────────────────────────────────────
const FACTION_META = {
    conservative: { label: '보수', emoji: '🔴', color: '#E24B4A' },
    moderate:     { label: '중도', emoji: '🟣', color: '#7F77DD' },
    progressive:  { label: '진보', emoji: '🔵', color: '#378ADD' },
}

const factionMeta  = computed(() => FACTION_META[props.politicalType] ?? null)
const pillClass    = computed(() =>
    props.politicalType
        ? getFactionPillClass(props.politicalType, props.level)
        : getGrayPillClass(props.level)
)
const pillPad  = computed(() => props.pillSm ? 'px-2 py-0.5'   : 'px-2.5 py-1')
const pillText = computed(() => props.pillSm ? 'text-xs'        : 'text-sm')
const emjSize  = computed(() => props.pillSm ? 'text-[11px]'    : 'text-[13px]')

// ── 팝업 열기/닫기 ──────────────────────────────────────────────
const openPopup = (e) => {
    e.stopPropagation()
    e.preventDefault()
    if (!props.userId) return

    if (!showPopup.value) {
        const rect = triggerRef.value?.getBoundingClientRect()
        if (rect) {
            // 수평: 뷰포트 오른쪽을 벗어나지 않도록 보정
            const left = Math.max(8, Math.min(rect.left, window.innerWidth - POPUP_W - 8))

            // 수직: 아래 공간이 충분하면 아래, 부족하면 위쪽으로 열기
            const spaceBelow = window.innerHeight - rect.bottom

            if (spaceBelow >= POPUP_H + 12) {
                // 닉네임 아래 충분한 공간 → 팝업을 아래에 표시
                opensAbove.value = false
                popupPos.value = {
                    top:  `${rect.bottom + window.scrollY + 8}px`,
                    left: `${left}px`,
                }
            } else {
                // 하단 공간 부족 → 팝업을 위에 표시
                opensAbove.value = true
                popupPos.value = {
                    top:  `${rect.top + window.scrollY - POPUP_H - 8}px`,
                    left: `${left}px`,
                }
            }
        }
    }
    showPopup.value = !showPopup.value
}

const close = () => { showPopup.value = false }

const goToProfile = () => {
    close()
    if (props.userId) router.visit(`/users/${props.userId}`)
}

// ── 외부 클릭 / ESC 닫기 ───────────────────────────────────────
const handleOutside = (e) => {
    if (triggerRef.value && !triggerRef.value.contains(e.target)) close()
}
const handleEsc    = (e) => { if (e.key === 'Escape') close() }

onMounted(() => {
    document.addEventListener('click',   handleOutside, true)
    document.addEventListener('keydown', handleEsc)
})
onUnmounted(() => {
    document.removeEventListener('click',   handleOutside, true)
    document.removeEventListener('keydown', handleEsc)
})
</script>

<template>
    <span ref="triggerRef" class="inline-flex">
        <!-- Pill 트리거 -->
        <span
            :class="[
                'inline-flex items-center gap-1 rounded-full font-semibold transition-opacity',
                pillPad, pillText, pillClass,
                userId ? 'cursor-pointer hover:opacity-75' : 'cursor-default',
            ]"
            @click="openPopup"
        >
            <span v-if="levelEmoji" :class="['leading-none', emjSize]">{{ levelEmoji }}</span>
            {{ nickname }}
        </span>

        <!-- 팝업 (body 에 Teleport) -->
        <Teleport to="body">
            <Transition
                :enter-active-class="`transition-all duration-150 ease-out`"
                :enter-from-class="`opacity-0 scale-95 ${opensAbove ? '-translate-y-1' : 'translate-y-1'}`"
                enter-to-class="opacity-100 scale-100 translate-y-0"
                :leave-active-class="`transition-all duration-100 ease-in`"
                leave-from-class="opacity-100 scale-100 translate-y-0"
                :leave-to-class="`opacity-0 scale-95 ${opensAbove ? '-translate-y-1' : 'translate-y-1'}`"
            >
                <div
                    v-if="showPopup && userId"
                    :style="{ position: 'absolute', top: popupPos.top, left: popupPos.left }"
                    :class="['z-[9999] bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-2xl shadow-2xl p-4 w-52', opensAbove ? 'origin-bottom-left' : 'origin-top-left']"
                    @click.stop
                >
                    <!-- 화살표: 아래 열림 → 팝업 위쪽 / 위 열림 → 팝업 아래쪽 -->
                    <div
                        v-if="!opensAbove"
                        class="absolute -top-1.5 left-5 w-3 h-3 bg-white dark:bg-slate-900 border-l border-t border-gray-200 dark:border-slate-700 rotate-45 rounded-tl-[1px]"
                    ></div>
                    <div
                        v-else
                        class="absolute -bottom-1.5 left-5 w-3 h-3 bg-white dark:bg-slate-900 border-r border-b border-gray-200 dark:border-slate-700 rotate-45 rounded-br-[1px]"
                    ></div>

                    <!-- 유저 정보 -->
                    <div class="flex items-center gap-3 mb-3.5">
                        <div :class="['w-11 h-11 rounded-xl flex items-center justify-center text-xl flex-shrink-0', pillClass]">
                            {{ levelEmoji }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-slate-900 dark:text-white truncate leading-snug">{{ nickname }}</p>
                            <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                <span
                                    v-if="factionMeta"
                                    class="text-xs font-semibold"
                                    :style="{ color: factionMeta.color }"
                                >{{ factionMeta.emoji }} {{ factionMeta.label }}</span>
                                <span class="text-[11px] text-slate-400 dark:text-slate-500">Lv.{{ level }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- 프로필 보기 버튼 -->
                    <button
                        @click="goToProfile"
                        class="w-full text-center text-xs font-bold py-2 px-3 rounded-xl bg-violet-600 hover:bg-violet-500 active:bg-violet-700 text-white transition-colors"
                    >
                        프로필 보기 →
                    </button>
                </div>
            </Transition>
        </Teleport>
    </span>
</template>
