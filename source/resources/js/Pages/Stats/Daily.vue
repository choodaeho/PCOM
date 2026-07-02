<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
    data:    Object, // { conservative: [...], moderate: [...], progressive: [...] }
    days:    Number,
})

const periodOptions = [7, 14, 30, 60, 90]
const selectedDays = ref(props.days ?? 30)

const factionConfig = {
    conservative: { label: '보수', emoji: '🦅', textClass: 'text-blue-400',   barClass: 'bg-blue-500',   borderClass: 'border-blue-500/30',   bgClass: 'bg-blue-500/10'   },
    moderate:     { label: '중도', emoji: '⚖️',  textClass: 'text-violet-400', barClass: 'bg-violet-500', borderClass: 'border-violet-500/30', bgClass: 'bg-violet-500/10' },
    progressive:  { label: '진보', emoji: '🕊️', textClass: 'text-red-400',    barClass: 'bg-red-500',    borderClass: 'border-red-500/30',    bgClass: 'bg-red-500/10'    },
}

// YYYY-MM-DD 포맷 헬퍼
const formatDate = (val) => {
    if (!val) return ''
    return String(val).slice(0, 10)
}

// 날짜별로 병합해 차트용 데이터 생성 (stat_date 사용)
const chartData = computed(() => {
    const dateMap = {}
    Object.entries(props.data ?? {}).forEach(([faction, rows]) => {
        rows.forEach(row => {
            const key = formatDate(row.stat_date)
            if (!dateMap[key]) dateMap[key] = { stat_date: key }
            dateMap[key][faction] = row.normalized_score ?? row.raw_score ?? 0
        })
    })
    return Object.values(dateMap).sort((a, b) => a.stat_date.localeCompare(b.stat_date))
})

// 각 진영 최신 점수
const latestScores = computed(() => {
    const result = {}
    Object.entries(props.data ?? {}).forEach(([faction, rows]) => {
        const sorted = [...rows].sort((a, b) => formatDate(b.stat_date).localeCompare(formatDate(a.stat_date)))
        result[faction] = sorted[0] ?? null
    })
    return result
})

const maxScore = computed(() => {
    let max = 1
    chartData.value.forEach(d => {
        Object.keys(factionConfig).forEach(f => {
            if ((d[f] ?? 0) > max) max = d[f]
        })
    })
    return max
})

const changePeriod = () => {
    router.get('/stats/daily', { days: selectedDays.value }, { preserveState: true })
}
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- 헤더 -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-8">
            <div>
                <div class="flex items-center gap-2 flex-wrap mb-1">
                    <Link href="/stats" class="text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white text-sm transition-colors">← 통계 개요</Link>
                    <span class="text-slate-400 dark:text-slate-600">|</span>
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">일간 진영 점수</h1>
                </div>
                <p class="text-slate-500 dark:text-slate-400 text-sm">최근 {{ days }}일간 진영별 활동 점수 추이</p>
            </div>

            <!-- 기간 선택 -->
            <div class="flex items-center gap-2">
                <span class="text-slate-500 dark:text-slate-400 text-sm">기간:</span>
                <div class="flex gap-1">
                    <button
                        v-for="d in periodOptions" :key="d"
                        @click="selectedDays = d; changePeriod()"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                            selectedDays === d
                                ? 'bg-violet-600 text-white'
                                : 'bg-gray-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-gray-200 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white'
                        ]"
                    >{{ d }}일</button>
                </div>
            </div>
        </div>

        <!-- 최신 점수 카드 -->
        <div class="grid grid-cols-3 gap-2 sm:gap-4 mb-8">
            <div
                v-for="(cfg, faction) in factionConfig" :key="faction"
                :class="['rounded-xl p-3 sm:p-5 border', cfg.bgClass, cfg.borderClass]"
            >
                <div class="flex items-center gap-1.5 sm:gap-2 mb-2 sm:mb-3">
                    <span class="text-base sm:text-xl">{{ cfg.emoji }}</span>
                    <span :class="['font-semibold text-xs sm:text-sm', cfg.textClass]">{{ cfg.label }}</span>
                </div>
                <div class="text-xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-1 tabular-nums">
                    {{ latestScores[faction]?.normalized_score?.toFixed(1) ?? latestScores[faction]?.raw_score?.toFixed(0) ?? '-' }}
                </div>
                <div class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400">{{ formatDate(latestScores[faction]?.stat_date) || '없음' }}</div>
                <div class="mt-2 sm:mt-3 text-[10px] sm:text-xs text-slate-400 dark:text-slate-500 space-y-0.5">
                    <div>게시글: {{ latestScores[faction]?.post_count ?? 0 }}</div>
                    <div>추천: {{ latestScores[faction]?.vote_count ?? 0 }}</div>
                </div>
            </div>
        </div>

        <!-- 막대 차트 -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 p-6">
            <h2 class="text-slate-900 dark:text-white font-semibold mb-6">일간 점수 추이</h2>

            <div v-if="chartData.length === 0" class="text-center text-slate-400 dark:text-slate-500 py-16">
                집계된 데이터가 없습니다.
            </div>

            <div v-else class="overflow-x-auto">
                <div class="min-w-[600px]">
                    <!-- 범례 -->
                    <div class="flex gap-4 mb-4">
                        <div v-for="(cfg, faction) in factionConfig" :key="faction" class="flex items-center gap-1.5">
                            <span :class="['w-3 h-3 rounded-full', cfg.barClass]"></span>
                            <span class="text-xs text-slate-500 dark:text-slate-400">{{ cfg.label }}</span>
                        </div>
                    </div>

                    <!-- 차트 바디 -->
                    <div class="space-y-2">
                        <div v-for="row in chartData" :key="row.stat_date" class="flex items-center gap-3">
                            <span class="text-xs text-slate-400 dark:text-slate-500 w-20 shrink-0">{{ row.stat_date?.slice(5) }}</span>
                            <div class="flex-1 flex gap-1 h-6">
                                <div
                                    v-for="(cfg, faction) in factionConfig" :key="faction"
                                    :class="['rounded-sm transition-all', cfg.barClass]"
                                    :style="{ width: maxScore > 0 ? ((row[faction] ?? 0) / maxScore * 33) + '%' : '0%', minWidth: '2px' }"
                                    :title="`${cfg.label}: ${(row[faction] ?? 0).toFixed(2)}`"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 기간 전환 링크 -->
        <div class="flex gap-3 mt-6">
            <Link href="/stats/monthly" class="text-sm text-slate-400 hover:text-violet-400 transition-colors">월간 통계 →</Link>
            <Link href="/stats/yearly" class="text-sm text-slate-400 hover:text-violet-400 transition-colors">연간 통계 →</Link>
        </div>
    </div>
</template>
