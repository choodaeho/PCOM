<script setup>
import { computed } from 'vue'

const props = defineProps({
    xpLevels:         { type: Array,  default: () => [] },
    currentLevelName: { type: String, default: '' },
})

// 50단계를 5단계씩 10개 티어로 나눔
const levelTiers = computed(() => [
    { label: '입문   Lv.1~5',    levels: props.xpLevels.slice(0,  5) },
    { label: '성장   Lv.6~10',   levels: props.xpLevels.slice(5,  10) },
    { label: '중견   Lv.11~15',  levels: props.xpLevels.slice(10, 15) },
    { label: '고수   Lv.16~20',  levels: props.xpLevels.slice(15, 20) },
    { label: '엘리트 Lv.21~25',  levels: props.xpLevels.slice(20, 25) },
    { label: '전설   Lv.26~30',  levels: props.xpLevels.slice(25, 30) },
    { label: '신화   Lv.31~35',  levels: props.xpLevels.slice(30, 35) },
    { label: '초월   Lv.36~40',  levels: props.xpLevels.slice(35, 40) },
    { label: '신계   Lv.41~45',  levels: props.xpLevels.slice(40, 45) },
    { label: '창조주 Lv.46~50',  levels: props.xpLevels.slice(45, 50) },
])

// XP 숫자 → 한국식 단위 축약 (카드 안 표시용)
// 예: 318,190,000,000 → 3182억 / 65,000,000 → 6500만 / 10,000 → 1만
const formatXpShort = (xpStr) => {
    const n = parseInt(String(xpStr).replace(/,/g, ''))
    if (n === 0) return '0'
    if (n >= 1_000_000_000_000) return Math.round(n / 1_000_000_000_000) + '조'
    if (n >= 100_000_000)       return Math.round(n / 100_000_000) + '억'
    if (n >= 10_000)            return Math.round(n / 10_000) + '만'
    return n.toLocaleString()
}
</script>

<template>
    <!-- 게시판별 XP -->
    <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">
        🎮 경험치(XP) 획득 방식
    </p>
    <div class="grid grid-cols-3 gap-2 mb-3">
        <div
            v-for="b in [
                { icon: '⚔️', label: '전쟁터', post: 20, comment: 5,
                  bg: 'bg-red-50 dark:bg-red-950/40', border: 'border-red-300 dark:border-red-700' },
                { icon: '🏠', label: '아지트',  post: 10, comment: 3,
                  bg: 'bg-violet-50 dark:bg-violet-950/40', border: 'border-violet-300 dark:border-violet-700' },
                { icon: '🎮', label: '놀이터', post: 5,  comment: 2,
                  bg: 'bg-slate-100 dark:bg-slate-700', border: 'border-slate-300 dark:border-slate-500' },
            ]"
            :key="b.label"
            :class="['rounded-xl border-2 p-3 text-center', b.bg, b.border]"
        >
            <p class="text-lg mb-1 leading-none">{{ b.icon }}</p>
            <p class="text-xs font-bold text-slate-700 dark:text-slate-200 mb-2">{{ b.label }}</p>
            <div class="space-y-1">
                <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-500 dark:text-slate-400">📝 게시글</span>
                    <span class="font-black text-emerald-600 dark:text-emerald-400">+{{ b.post }}</span>
                </div>
                <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-500 dark:text-slate-400">💬 댓글</span>
                    <span class="font-black text-emerald-600 dark:text-emerald-400">+{{ b.comment }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 추천 -->
    <div class="flex items-center gap-2 bg-emerald-50 dark:bg-emerald-950/40 border-2 border-emerald-300 dark:border-emerald-700 rounded-xl px-4 py-2 mb-4">
        <span class="text-base">👍</span>
        <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">받은 추천</span>
        <span class="ml-auto text-xs font-black text-emerald-600 dark:text-emerald-400">+2 XP</span>
    </div>

    <!-- 레벨 구간 (50단계) -->
    <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">레벨 구간 (50단계)</p>

    <div class="space-y-3">
        <div v-for="tier in levelTiers" :key="tier.label">
            <!-- 티어 레이블 -->
            <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5 px-0.5">
                {{ tier.label }}
            </p>
            <!-- 레벨 카드 5개 한 행 -->
            <div class="grid grid-cols-5 gap-1.5">
                <div
                    v-for="lv in tier.levels"
                    :key="lv.name"
                    :class="[
                        'flex flex-col items-center gap-0.5 rounded-lg py-2 px-1 text-center border-2 transition-colors',
                        currentLevelName === lv.name
                            ? 'bg-violet-100 dark:bg-violet-900/50 border-violet-500 dark:border-violet-400'
                            : 'bg-slate-100 dark:bg-slate-700/60 border-slate-200 dark:border-slate-600',
                    ]"
                >
                    <!-- 이모지 -->
                    <span class="text-sm leading-none">{{ lv.emoji }}</span>
                    <!-- 레벨명 -->
                    <span :class="[
                        'text-[10px] font-bold leading-tight mt-0.5 break-keep text-center',
                        currentLevelName === lv.name
                            ? 'text-violet-700 dark:text-violet-300'
                            : 'text-slate-700 dark:text-slate-200'
                    ]">{{ lv.name }}</span>
                    <!-- XP (축약 표기) -->
                    <span :class="[
                        'text-[10px] leading-tight font-medium',
                        currentLevelName === lv.name
                            ? 'text-violet-500 dark:text-violet-400'
                            : 'text-slate-500 dark:text-slate-400'
                    ]">{{ formatXpShort(lv.xp) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
