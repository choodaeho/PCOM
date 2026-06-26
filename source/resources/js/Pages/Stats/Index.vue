<script setup>
import { ref, computed, onMounted, watch } from 'vue'

const showFormula = ref(false)
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  factionStats:  { type: Object, default: () => ({}) },
  periodData:    { type: Array,  default: () => [] },
  currentPeriod: { type: String, default: 'daily' },
})

const factionConfig = {
  conservative: { label: '보수', color: '#E24B4A', emoji: '🔴', bg: 'bg-red-50 dark:bg-red-950/30', border: 'border-red-200 dark:border-red-900' },
  moderate:     { label: '중도', color: '#7F77DD', emoji: '🟣', bg: 'bg-purple-50 dark:bg-purple-950/30', border: 'border-purple-200 dark:border-purple-900' },
  progressive:  { label: '진보', color: '#378ADD', emoji: '🔵', bg: 'bg-blue-50 dark:bg-blue-950/30', border: 'border-blue-200 dark:border-blue-900' },
}

const periodTabs = [
  { key: 'daily',   label: '일간' },
  { key: 'monthly', label: '월간' },
  { key: 'yearly',  label: '연간' },
]

const changePeriod = (period) => {
  router.visit('/stats', { data: { period }, preserveScroll: true })
}

// 랭킹 순서 (점수 내림차순)
const rankedFactions = computed(() => {
  return Object.entries(props.factionStats)
    .sort((a, b) => (b[1].score ?? 0) - (a[1].score ?? 0))
    .map(([key, val], i) => ({ key, ...val, rank: i + 1, ...factionConfig[key] }))
})

const maxScore = computed(() => Math.max(1, ...rankedFactions.value.map(f => f.score ?? 0)))

const rankEmoji = (rank) => ['🥇','🥈','🥉'][rank-1] ?? ''

// 차트
const canvasRef = ref(null)

const drawChart = () => {
  const canvas = canvasRef.value
  if (!canvas || !props.periodData.length) return
  const ctx = canvas.getContext('2d')
  const W = canvas.width, H = canvas.height
  const pad = { top: 20, right: 20, bottom: 40, left: 45 }
  const isDark = document.documentElement.classList.contains('dark')

  ctx.clearRect(0, 0, W, H)
  ctx.fillStyle = isDark ? '#0f172a' : '#ffffff'
  ctx.fillRect(0, 0, W, H)

  const data = props.periodData
  const factions = ['conservative', 'moderate', 'progressive']
  const colors   = { conservative: '#E24B4A', moderate: '#7F77DD', progressive: '#378ADD' }

  const allScores = data.flatMap(d => factions.map(f => d[f] ?? 0))
  const maxVal  = Math.max(1, ...allScores)

  const xStep = (W - pad.left - pad.right) / Math.max(data.length - 1, 1)
  const yScale = (H - pad.top - pad.bottom) / maxVal

  // 그리드
  const gridColor = isDark ? '#1e293b' : '#f1f5f9'
  const textColor = isDark ? '#64748b' : '#94a3b8'
  ctx.strokeStyle = gridColor
  ctx.lineWidth = 1
  for (let i = 0; i <= 4; i++) {
    const y = pad.top + (H - pad.top - pad.bottom) * (i / 4)
    ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(W - pad.right, y); ctx.stroke()
    ctx.fillStyle = textColor
    ctx.font = '11px sans-serif'
    ctx.textAlign = 'right'
    ctx.fillText(Math.round(maxVal * (1 - i/4)), pad.left - 5, y + 4)
  }

  // X 레이블
  const maxLabels = Math.min(data.length, 8)
  const step = Math.ceil(data.length / maxLabels)
  ctx.fillStyle = textColor
  ctx.font = '11px sans-serif'
  ctx.textAlign = 'center'
  data.forEach((d, i) => {
    if (i % step !== 0 && i !== data.length - 1) return
    const x = pad.left + i * xStep
    const label = String(d.date ?? '').slice(0, 10)
    ctx.fillText(label.slice(5), x, H - pad.bottom + 16) // MM-DD 형식
  })

  // 라인 그리기
  factions.forEach(faction => {
    ctx.strokeStyle = colors[faction]
    ctx.lineWidth = 2.5
    ctx.lineJoin = 'round'
    ctx.beginPath()
    data.forEach((d, i) => {
      const x = pad.left + i * xStep
      const y = H - pad.bottom - (d[faction] ?? 0) * yScale
      i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y)
    })
    ctx.stroke()
    // 점
    data.forEach((d, i) => {
      if (data.length > 30 && i % 3 !== 0) return
      const x = pad.left + i * xStep
      const y = H - pad.bottom - (d[faction] ?? 0) * yScale
      ctx.fillStyle = colors[faction]
      ctx.beginPath(); ctx.arc(x, y, 3, 0, Math.PI * 2); ctx.fill()
    })
  })
}

onMounted(() => { setTimeout(drawChart, 50) })
watch(() => [props.periodData, props.currentPeriod], () => setTimeout(drawChart, 50))
</script>

<template>
  <div class="max-w-5xl mx-auto px-4 py-8">
    <!-- 헤더 -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-black text-gray-900 dark:text-white flex items-center gap-2">
          📊 진영 통계
        </h1>
        <p class="text-gray-500 dark:text-slate-400 text-sm mt-1">진영별 실시간 영향력 점수와 활동 현황</p>
      </div>
    </div>

    <!-- 진영 랭킹 카드 -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div
        v-for="f in rankedFactions"
        :key="f.key"
        :class="['rounded-2xl border p-5', f.bg, f.border]"
      >
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center gap-2">
            <span class="text-xl">{{ f.emoji }}</span>
            <span class="font-bold text-gray-700 dark:text-slate-300">{{ f.label }}</span>
          </div>
          <span class="text-xl">{{ rankEmoji(f.rank) }}</span>
        </div>
        <p class="text-3xl font-black mb-3" :style="{ color: f.color }">
          {{ Math.round((f.score ?? 0) * 100) }}
        </p>
        <!-- 점수 바 -->
        <div class="h-2 bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden mb-3">
          <div
            class="h-full rounded-full transition-all duration-1000"
            :style="{ width: Math.max(2, Math.min(100, ((f.score ?? 0) / maxScore) * 100)) + '%', backgroundColor: f.color }"
          ></div>
        </div>
        <div class="flex gap-4 text-xs text-gray-500 dark:text-slate-500">
          <span>게시글 <strong class="text-gray-700 dark:text-slate-300">{{ f.post_count ?? 0 }}</strong></span>
          <span>추천 <strong class="text-gray-700 dark:text-slate-300">{{ f.vote_count ?? 0 }}</strong></span>
        </div>
      </div>
    </div>

    <!-- 차트 영역 -->
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-5">
      <!-- 기간 탭 -->
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-bold text-gray-800 dark:text-slate-200">점수 추세</h2>
        <div class="flex bg-gray-100 dark:bg-slate-800 rounded-xl p-1 gap-1">
          <button
            v-for="tab in periodTabs"
            :key="tab.key"
            @click="changePeriod(tab.key)"
            :class="[
              'px-4 py-1.5 rounded-lg text-sm font-medium transition-colors',
              currentPeriod === tab.key
                ? 'bg-white dark:bg-slate-700 text-gray-900 dark:text-white shadow-sm'
                : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200'
            ]"
          >
            {{ tab.label }}
          </button>
        </div>
      </div>

      <!-- 범례 -->
      <div class="flex gap-4 mb-3">
        <div v-for="(cfg, key) in factionConfig" :key="key" class="flex items-center gap-1.5">
          <span class="w-3 h-3 rounded-full inline-block" :style="{ backgroundColor: cfg.color }"></span>
          <span class="text-xs text-gray-500 dark:text-slate-400">{{ cfg.label }}</span>
        </div>
      </div>

      <div v-if="!periodData.length" class="flex items-center justify-center h-48 text-gray-400 dark:text-slate-600">
        집계된 데이터가 없습니다.
      </div>
      <canvas v-else ref="canvasRef" width="900" height="280" class="w-full rounded-xl"></canvas>
    </div>

    <!-- 점수 산정 방식 안내 -->
    <div class="mt-4 rounded-2xl border border-gray-200 dark:border-slate-800 overflow-hidden">
      <button
        @click="showFormula = !showFormula"
        class="w-full flex items-center justify-between px-5 py-3.5 bg-gray-50 dark:bg-slate-900 text-sm font-medium text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors"
      >
        <span class="flex items-center gap-2">
          <span class="text-base">ℹ️</span>
          점수 산정 방식 안내
        </span>
        <svg
          :class="['w-4 h-4 transition-transform duration-200', showFormula ? 'rotate-180' : '']"
          fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
        >
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>

      <transition
        enter-active-class="transition-all duration-200 ease-out"
        enter-from-class="opacity-0 max-h-0"
        enter-to-class="opacity-100 max-h-96"
        leave-active-class="transition-all duration-150 ease-in"
        leave-from-class="opacity-100 max-h-96"
        leave-to-class="opacity-0 max-h-0"
      >
        <div v-if="showFormula" class="px-5 py-4 bg-white dark:bg-slate-900 border-t border-gray-100 dark:border-slate-800 overflow-hidden">
          <!-- 공식 -->
          <div class="mb-4">
            <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-2">점수 공식</p>
            <div class="bg-gray-50 dark:bg-slate-800 rounded-xl px-4 py-3 font-mono text-sm text-gray-700 dark:text-slate-300 leading-relaxed">
              <span class="text-violet-600 dark:text-violet-400 font-bold">원점수</span>
              &nbsp;=&nbsp;게시글 × <span class="text-emerald-600 dark:text-emerald-400">3</span>
              &nbsp;+&nbsp;댓글 × <span class="text-emerald-600 dark:text-emerald-400">1</span>
              &nbsp;+&nbsp;추천 × <span class="text-emerald-600 dark:text-emerald-400">2</span>
              &nbsp;−&nbsp;비추천 × <span class="text-red-500">0.5</span>
              &nbsp;−&nbsp;신고처리 × <span class="text-red-500">5</span>
              <br>
              <span class="text-violet-600 dark:text-violet-400 font-bold">영향력 점수</span>
              &nbsp;=&nbsp;(원점수 ÷ 활성 사용자 수) × 100
            </div>
          </div>

          <!-- 항목별 가중치 -->
          <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-2">항목별 가중치</p>
          <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 mb-4">
            <div v-for="item in [
              { icon: '📝', label: '게시글 작성', value: '+3', color: 'text-emerald-600 dark:text-emerald-400' },
              { icon: '💬', label: '댓글 작성',   value: '+1', color: 'text-emerald-600 dark:text-emerald-400' },
              { icon: '👍', label: '추천 획득',   value: '+2', color: 'text-emerald-600 dark:text-emerald-400' },
              { icon: '👎', label: '비추천 획득', value: '−0.5', color: 'text-red-500' },
              { icon: '🚨', label: '신고 처리',   value: '−5', color: 'text-red-500' },
            ]" :key="item.label"
              class="flex flex-col items-center gap-1 bg-gray-50 dark:bg-slate-800 rounded-xl py-3 px-2 text-center"
            >
              <span class="text-lg">{{ item.icon }}</span>
              <span class="text-xs text-gray-500 dark:text-slate-400 leading-tight">{{ item.label }}</span>
              <span :class="['text-sm font-black', item.color]">{{ item.value }}</span>
            </div>
          </div>

          <!-- 보충 설명 -->
          <div class="text-xs text-gray-400 dark:text-slate-500 space-y-1 border-t border-gray-100 dark:border-slate-700 pt-3">
            <p>• 영향력 점수는 <strong class="text-gray-500 dark:text-slate-400">진영 활성 사용자 수</strong>로 나누어 정규화합니다. 소수 정예 진영도 공정하게 평가됩니다.</p>
            <p>• <strong class="text-gray-500 dark:text-slate-400">놀이터</strong> 게시판의 활동(게시글·댓글·추천 등)은 정치적 성격이 없으므로 집계에서 제외됩니다.</p>
            <p>• 점수는 <strong class="text-gray-500 dark:text-slate-400">매일 00:05</strong>에 전날 활동을 기준으로 집계됩니다.</p>
            <p>• 테스트 계정의 활동은 집계에서 제외됩니다.</p>
            <p>• <strong class="text-gray-500 dark:text-slate-400">매너 점수</strong>는 기본 100점. <strong class="text-gray-500 dark:text-slate-400">다른 진영</strong>으로부터 추천 +1점, <strong class="text-gray-500 dark:text-slate-400">같은 진영</strong>으로부터 비추천 −1점, 신고·삭제 처리 확정 시 −10점. 상한 없음.</p>
          </div>

        </div>
      </transition>
    </div>
  </div>
</template>
