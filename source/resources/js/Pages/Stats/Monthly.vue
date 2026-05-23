<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
    data:   Object,
    months: Number,
})

const selectedMonths = ref(props.months ?? 12)
const monthOptions = [3, 6, 12, 24, 36]

const factionConfig = {
    conservative: { label: '보수', emoji: '🦅', textClass: 'text-red-400',    bgClass: 'bg-red-500/10',    borderClass: 'border-red-500/30',    barClass: 'bg-red-500'    },
    moderate:     { label: '중도', emoji: '⚖️',  textClass: 'text-violet-400', bgClass: 'bg-violet-500/10', borderClass: 'border-violet-500/30', barClass: 'bg-violet-500' },
    progressive:  { label: '진보', emoji: '🕊️', textClass: 'text-blue-400',   bgClass: 'bg-blue-500/10',   borderClass: 'border-blue-500/30',   barClass: 'bg-blue-500'   },
}

// 월별 통합 데이터
const monthlyRows = (() => {
    const map = {}
    Object.entries(props.data ?? {}).forEach(([faction, rows]) => {
        rows.forEach(row => {
            const key = row.stat_year_month
            if (!map[key]) map[key] = { month: key }
            map[key][faction] = row.total_score ?? 0
            map[key][`${faction}_post`] = row.post_count ?? 0
        })
    })
    return Object.values(map).sort((a, b) => a.month.localeCompare(b.month))
})()

const changePeriod = () => {
    router.get('/stats/monthly', { months: selectedMonths.value }, { preserveState: true })
}

// 월별 최고 진영 계산
const getTopFaction = (row) => {
    let top = null; let maxVal = -Infinity
    Object.keys(factionConfig).forEach(f => {
        if ((row[f] ?? 0) > maxVal) { maxVal = row[f] ?? 0; top = f }
    })
    return top
}
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- 헤더 -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <Link href="/stats" class="text-slate-400 hover:text-white text-sm transition-colors">← 통계 개요</Link>
                    <span class="text-slate-600">|</span>
                    <h1 class="text-2xl font-bold text-white">월간 진영 점수</h1>
                </div>
                <p class="text-slate-400 text-sm">최근 {{ months }}개월 진영별 월간 점수 집계</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-slate-400 text-sm">기간:</span>
                <div class="flex gap-1">
                    <button
                        v-for="m in monthOptions" :key="m"
                        @click="selectedMonths = m; changePeriod()"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                            selectedMonths === m ? 'bg-violet-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700 hover:text-white'
                        ]"
                    >{{ m }}개월</button>
                </div>
            </div>
        </div>

        <!-- 월별 테이블 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-slate-800">
                <h2 class="text-white font-semibold">월별 집계</h2>
            </div>

            <div v-if="monthlyRows.length === 0" class="text-center text-slate-500 py-16">
                집계된 데이터가 없습니다.
            </div>

            <table v-else class="w-full text-sm">
                <thead class="bg-slate-800/50">
                    <tr class="text-slate-400 text-left">
                        <th class="px-6 py-3">월</th>
                        <th v-for="(cfg, f) in factionConfig" :key="f" class="px-4 py-3">
                            <span :class="cfg.textClass">{{ cfg.emoji }} {{ cfg.label }}</span>
                        </th>
                        <th class="px-4 py-3">이달의 진영</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    <tr v-for="row in [...monthlyRows].reverse()" :key="row.month" class="hover:bg-slate-800/30">
                        <td class="px-6 py-3 text-slate-300 font-medium">{{ row.month }}</td>
                        <td v-for="(cfg, f) in factionConfig" :key="f" class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span :class="['font-bold', cfg.textClass]">{{ (row[f] ?? 0).toFixed(1) }}</span>
                                <div class="flex-1 h-1.5 bg-slate-700 rounded-full overflow-hidden" style="max-width:60px">
                                    <div :class="['h-full rounded-full', cfg.barClass]"
                                         :style="{ width: Math.min((row[f] ?? 0) / 100 * 100, 100) + '%' }"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span v-if="getTopFaction(row)" :class="[
                                'text-xs px-2 py-0.5 rounded-full font-medium',
                                factionConfig[getTopFaction(row)].textClass,
                                factionConfig[getTopFaction(row)].bgClass,
                            ]">
                                {{ factionConfig[getTopFaction(row)].emoji }} {{ factionConfig[getTopFaction(row)].label }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex gap-3">
            <Link href="/stats/daily" class="text-sm text-slate-400 hover:text-violet-400 transition-colors">← 일간 통계</Link>
            <Link href="/stats/yearly" class="text-sm text-slate-400 hover:text-violet-400 transition-colors">연간 통계 →</Link>
        </div>
    </div>
</template>
