<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
    data:  Object,
    years: Number,
})

const selectedYears = ref(props.years ?? 5)
const yearOptions = [3, 5, 7, 10]

const factionConfig = {
    conservative: { label: '보수', emoji: '🦅', textClass: 'text-red-400',    bgClass: 'bg-red-500/10',    borderClass: 'border-red-500/30',    barClass: 'bg-red-500'    },
    moderate:     { label: '중도', emoji: '⚖️',  textClass: 'text-violet-400', bgClass: 'bg-violet-500/10', borderClass: 'border-violet-500/30', barClass: 'bg-violet-500' },
    progressive:  { label: '진보', emoji: '🕊️', textClass: 'text-blue-400',   bgClass: 'bg-blue-500/10',   borderClass: 'border-blue-500/30',   barClass: 'bg-blue-500'   },
}

// 연도별 통합 데이터
const yearlyRows = (() => {
    const map = {}
    Object.entries(props.data ?? {}).forEach(([faction, rows]) => {
        rows.forEach(row => {
            const key = String(row.stat_year)
            if (!map[key]) map[key] = { year: key }
            map[key][faction] = row.total_score ?? 0
            map[key][`${faction}_champion`] = row.champion_nickname ?? null
        })
    })
    return Object.values(map).sort((a, b) => b.year.localeCompare(a.year))
})()

const changePeriod = () => {
    router.get('/stats/yearly', { years: selectedYears.value }, { preserveState: true })
}

const getChampionFaction = (row) => {
    let top = null; let maxVal = -Infinity
    Object.keys(factionConfig).forEach(f => {
        if ((row[f] ?? 0) > maxVal) { maxVal = row[f] ?? 0; top = f }
    })
    return top
}

// 연도별 최고 점수 (비율 계산용)
const allScores = yearlyRows.flatMap(r => Object.keys(factionConfig).map(f => r[f] ?? 0))
const globalMax = Math.max(...allScores, 1)
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- 헤더 -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <Link href="/stats" class="text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white text-sm transition-colors">← 통계 개요</Link>
                    <span class="text-slate-400 dark:text-slate-600">|</span>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">연간 진영 점수</h1>
                </div>
                <p class="text-slate-500 dark:text-slate-400 text-sm">최근 {{ years }}년 연간 진영 총점 및 챔피언 기록</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-slate-500 dark:text-slate-400 text-sm">기간:</span>
                <div class="flex gap-1">
                    <button
                        v-for="y in yearOptions" :key="y"
                        @click="selectedYears = y; changePeriod()"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                            selectedYears === y ? 'bg-violet-600 text-white' : 'bg-gray-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-gray-200 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white'
                        ]"
                    >{{ y }}년</button>
                </div>
            </div>
        </div>

        <!-- 연도별 카드 -->
        <div v-if="yearlyRows.length === 0" class="text-center text-slate-400 dark:text-slate-500 py-20 bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800">
            집계된 연간 데이터가 없습니다.
        </div>

        <div v-else class="space-y-4">
            <div v-for="row in yearlyRows" :key="row.year"
                 class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 p-6">
                <!-- 연도 헤더 -->
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ row.year }}년</h2>
                    <div v-if="getChampionFaction(row)">
                        <span class="text-xs text-slate-500 dark:text-slate-400 mr-2">연간 챔피언:</span>
                        <span :class="[
                            'text-sm font-bold px-3 py-1 rounded-full',
                            factionConfig[getChampionFaction(row)].textClass,
                            factionConfig[getChampionFaction(row)].bgClass,
                        ]">
                            {{ factionConfig[getChampionFaction(row)].emoji }}
                            {{ factionConfig[getChampionFaction(row)].label }}
                        </span>
                    </div>
                </div>

                <!-- 진영별 점수 바 -->
                <div class="space-y-3">
                    <div v-for="(cfg, faction) in factionConfig" :key="faction">
                        <div class="flex items-center justify-between mb-1">
                            <span :class="['text-sm font-medium', cfg.textClass]">
                                {{ cfg.emoji }} {{ cfg.label }}
                            </span>
                            <span :class="['text-sm font-bold', cfg.textClass]">
                                {{ (row[faction] ?? 0).toLocaleString() }}점
                            </span>
                        </div>
                        <div class="h-3 bg-gray-200 dark:bg-slate-800 rounded-full overflow-hidden">
                            <div
                                :class="['h-full rounded-full transition-all', cfg.barClass]"
                                :style="{ width: ((row[faction] ?? 0) / globalMax * 100) + '%' }"
                            ></div>
                        </div>
                        <div v-if="row[`${faction}_champion`]" class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">
                            MVP: {{ row[`${faction}_champion`] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <Link href="/stats/monthly" class="text-sm text-slate-400 hover:text-violet-400 transition-colors">← 월간 통계</Link>
        </div>
    </div>
</template>
