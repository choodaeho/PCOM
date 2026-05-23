<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  factionStats: Object,   // { conservative: { score, post_count, vote_count, rank }, ... }
  dailyStats: Array,      // [{ date, conservative_score, moderate_score, progressive_score }]
  period: String,         // 'daily' | 'monthly' | 'yearly'
})

const currentPeriod = ref(props.period ?? 'daily')

const periodLabels = { daily: '일간', monthly: '월간', yearly: '연간' }

const factionConfig = {
  conservative: {
    label: '보수',
    emoji: '🦅',
    color: '#E24B4A',
    textClass: 'text-red-400',
    bgClass: 'bg-red-500/10',
    borderClass: 'border-red-500/30',
    barClass: 'bg-red-500',
  },
  moderate: {
    label: '중도',
    emoji: '⚖️',
    color: '#7F77DD',
    textClass: 'text-violet-400',
    bgClass: 'bg-violet-500/10',
    borderClass: 'border-violet-500/30',
    barClass: 'bg-violet-500',
  },
  progressive: {
    label: '진보',
    emoji: '🕊️',
    color: '#378ADD',
    textClass: 'text-blue-400',
    bgClass: 'bg-blue-500/10',
    borderClass: 'border-blue-500/30',
    barClass: 'bg-blue-500',
  },
}

// Sort factions by score for ranking
const rankedFactions = computed(() => {
  return ['conservative', 'moderate', 'progressive']
    .map(type => ({
      type,
      ...factionConfig[type],
      stats: props.factionStats?.[type] ?? { score: 0, post_count: 0, vote_count: 0 },
    }))
    .sort((a, b) => (b.stats.score ?? 0) - (a.stats.score ?? 0))
})

const maxScore = computed(() => Math.max(1, ...rankedFactions.value.map(f => f.stats.score ?? 0)))

// Simple canvas chart
const chartCanvas = ref(null)

const drawChart = () => {
  const canvas = chartCanvas.value
  if (!canvas || !props.dailyStats?.length) return

  const ctx = canvas.getContext('2d')
  const data = props.dailyStats
  const width = canvas.width
  const height = canvas.height
  const padding = { top: 20, right: 20, bottom: 40, left: 50 }
  const chartWidth = width - padding.left - padding.right
  const chartHeight = height - padding.top - padding.bottom

  ctx.clearRect(0, 0, width, height)

  // Background
  ctx.fillStyle = '#0f172a'
  ctx.fillRect(0, 0, width, height)

  const factions = ['conservative', 'moderate', 'progressive']
  const colors = { conservative: '#E24B4A', moderate: '#7F77DD', progressive: '#378ADD' }
  const scoreKeys = { conservative: 'conservative_score', moderate: 'moderate_score', progressive: 'progressive_score' }

  const allScores = data.flatMap(d => factions.map(f => d[scoreKeys[f]] ?? 0))
  const maxVal = Math.max(1, ...allScores)
  const minVal = Math.min(0, ...allScores)
  const range = maxVal - minVal || 1

  const xStep = chartWidth / Math.max(1, data.length - 1)

  // Grid lines
  ctx.strokeStyle = '#1e293b'
  ctx.lineWidth = 1
  for (let i = 0; i <= 4; i++) {
    const y = padding.top + (chartHeight / 4) * i
    ctx.beginPath()
    ctx.moveTo(padding.left, y)
    ctx.lineTo(padding.left + chartWidth, y)
    ctx.stroke()

    // Y label
    const val = Math.round(maxVal - (range / 4) * i)
    ctx.fillStyle = '#475569'
    ctx.font = '10px sans-serif'
    ctx.textAlign = 'right'
    ctx.fillText(val.toString(), padding.left - 6, y + 4)
  }

  // X labels (dates)
  ctx.fillStyle = '#475569'
  ctx.font = '9px sans-serif'
  ctx.textAlign = 'center'
  const labelStep = Math.max(1, Math.floor(data.length / 6))
  data.forEach((d, i) => {
    if (i % labelStep === 0) {
      const x = padding.left + i * xStep
      const label = d.date ? d.date.slice(5) : ''  // MM-DD
      ctx.fillText(label, x, height - padding.bottom + 15)
    }
  })

  // Lines for each faction
  factions.forEach(faction => {
    const color = colors[faction]
    const key = scoreKeys[faction]

    ctx.beginPath()
    ctx.strokeStyle = color
    ctx.lineWidth = 2
    ctx.lineJoin = 'round'

    data.forEach((d, i) => {
      const x = padding.left + i * xStep
      const score = d[key] ?? 0
      const y = padding.top + chartHeight - ((score - minVal) / range) * chartHeight

      if (i === 0) ctx.moveTo(x, y)
      else ctx.lineTo(x, y)
    })
    ctx.stroke()

    // Dots
    data.forEach((d, i) => {
      const x = padding.left + i * xStep
      const score = d[key] ?? 0
      const y = padding.top + chartHeight - ((score - minVal) / range) * chartHeight
      ctx.beginPath()
      ctx.arc(x, y, 2.5, 0, Math.PI * 2)
      ctx.fillStyle = color
      ctx.fill()
    })
  })
}

onMounted(() => {
  drawChart()
})

const changePeriod = (period) => {
  currentPeriod.value = period
  // In real app: router.get('/stats', { period })
}

const rankEmoji = (i) => ['🥇', '🥈', '🥉'][i] ?? `${i + 1}`
</script>

<template>
  <div class="max-w-6xl mx-auto px-4 py-10">
    <!-- Page Header -->
    <div class="mb-8 flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1 class="text-3xl font-black text-white mb-1">📊 진영 통계</h1>
        <p class="text-slate-400 text-sm">진영별 실시간 영향력 점수와 활동 현황</p>
      </div>

      <!-- Period Tabs -->
      <div class="flex bg-slate-900 border border-slate-800 rounded-xl p-1 gap-0.5">
        <button
          v-for="(label, period) in periodLabels"
          :key="period"
          @click="changePeriod(period)"
          :class="[
            'px-4 py-1.5 rounded-lg text-sm font-medium transition-colors',
            currentPeriod === period
              ? 'bg-violet-600 text-white'
              : 'text-slate-400 hover:text-white'
          ]"
        >
          {{ label }}
        </button>
      </div>
    </div>

    <!-- Faction Score Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
      <div
        v-for="(f, i) in rankedFactions"
        :key="f.type"
        :class="['rounded-2xl border p-6 relative overflow-hidden', f.bgClass, f.borderClass]"
      >
        <!-- Rank badge -->
        <div class="absolute top-4 right-4 text-2xl">{{ rankEmoji(i) }}</div>

        <div class="flex items-center gap-3 mb-4">
          <span class="text-3xl">{{ f.emoji }}</span>
          <div>
            <p class="text-slate-400 text-xs">진영</p>
            <h3 :class="['text-xl font-black', f.textClass]">{{ f.label }}</h3>
          </div>
        </div>

        <!-- Score -->
        <div class="mb-4">
          <p class="text-slate-400 text-xs mb-1">영향력 점수</p>
          <div :class="['text-4xl font-black tabular-nums', f.textClass]">
            {{ f.stats.score?.toFixed(1) ?? '0.0' }}
          </div>
        </div>

        <!-- Score bar -->
        <div class="h-2 bg-slate-800 rounded-full overflow-hidden mb-4">
          <div
            :class="['h-full rounded-full transition-all duration-1000', f.barClass]"
            :style="{ width: Math.max(0, Math.min(100, ((f.stats.score ?? 0) / maxScore) * 100)) + '%' }"
          ></div>
        </div>

        <!-- Sub stats -->
        <div class="grid grid-cols-2 gap-3 text-center">
          <div class="bg-slate-900/60 rounded-lg py-2">
            <p class="text-xs text-slate-500">게시글</p>
            <p class="text-sm font-bold text-slate-200">{{ (f.stats.post_count ?? 0).toLocaleString() }}</p>
          </div>
          <div class="bg-slate-900/60 rounded-lg py-2">
            <p class="text-xs text-slate-500">추천 수</p>
            <p class="text-sm font-bold text-slate-200">{{ (f.stats.vote_count ?? 0).toLocaleString() }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Trend Chart -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden mb-8">
      <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
        <h2 class="font-bold text-white">점수 추세</h2>
        <!-- Legend -->
        <div class="flex items-center gap-4">
          <div v-for="f in Object.values(factionConfig)" :key="f.label" class="flex items-center gap-1.5">
            <div class="w-3 h-0.5 rounded-full" :style="{ backgroundColor: f.color }"></div>
            <span class="text-xs text-slate-400">{{ f.label }}</span>
          </div>
        </div>
      </div>

      <div class="p-4">
        <div v-if="dailyStats?.length" class="relative">
          <canvas
            ref="chartCanvas"
            width="900"
            height="280"
            class="w-full rounded-lg"
          ></canvas>
        </div>
        <div v-else class="h-48 flex items-center justify-center text-slate-500 text-sm">
          통계 데이터가 없습니다.
        </div>
      </div>
    </div>

    <!-- Score Comparison Bar -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
      <h2 class="font-bold text-white mb-5">진영별 점수 비교</h2>
      <div class="space-y-4">
        <div v-for="f in rankedFactions" :key="f.type">
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
              <span>{{ f.emoji }}</span>
              <span :class="['text-sm font-semibold', f.textClass]">{{ f.label }}</span>
            </div>
            <span :class="['text-sm font-bold tabular-nums', f.textClass]">
              {{ f.stats.score?.toFixed(1) ?? '0.0' }}점
            </span>
          </div>
          <div class="h-3 bg-slate-800 rounded-full overflow-hidden">
            <div
              :class="['h-full rounded-full transition-all duration-1000', f.barClass]"
              :style="{ width: Math.max(2, Math.min(100, ((f.stats.score ?? 0) / maxScore) * 100)) + '%' }"
            ></div>
          </div>
          <div class="flex items-center gap-4 mt-1.5 text-xs text-slate-500">
            <span>게시글 {{ (f.stats.post_count ?? 0).toLocaleString() }}개</span>
            <span>추천 {{ (f.stats.vote_count ?? 0).toLocaleString() }}회</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
