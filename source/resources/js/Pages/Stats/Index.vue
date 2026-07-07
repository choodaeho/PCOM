<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  factionStats:  { type: Object, default: () => ({}) },
  periodData:    { type: Array,  default: () => [] },
  currentPeriod: { type: String, default: 'daily' },
  searchDate:    { type: String, default: '' },
  medalData:     { type: Object, default: () => ({}) },
  medalPeriod:   { type: String, default: 'weekly' },
})

// ── 진영 설정 ─────────────────────────────────────────────────────
const factionConfig = {
  conservative: { label: '보수', color: '#E24B4A', emoji: '🔴', bg: 'bg-red-50 dark:bg-red-950/30', border: 'border-red-200 dark:border-red-900' },
  moderate:     { label: '중도', color: '#7F77DD', emoji: '🟣', bg: 'bg-purple-50 dark:bg-purple-950/30', border: 'border-purple-200 dark:border-purple-900' },
  progressive:  { label: '진보', color: '#378ADD', emoji: '🔵', bg: 'bg-blue-50 dark:bg-blue-950/30', border: 'border-blue-200 dark:border-blue-900' },
}

// ── 기간 탭 ──────────────────────────────────────────────────────
const periodTabs = [
  { key: 'daily',   label: '일간' },
  { key: 'monthly', label: '월간' },
  { key: 'yearly',  label: '연간' },
]

// ── 메달 기간 탭 ─────────────────────────────────────────────────
const medalPeriodTabs = [
  { key: 'weekly',  label: '이번 주' },
  { key: 'monthly', label: '이번 달' },
  { key: 'yearly',  label: '올해' },
]

// ── 날짜 검색 ─────────────────────────────────────────────────────
const localDate = ref(props.searchDate || '')

// 일간은 항상 실시간 → 날짜 검색 숨김
const showDateSearch = computed(() => props.currentPeriod !== 'daily')

const dateInputType = computed(() => 'number')   // 월간·연간 모두 연도(number) 입력

const dateInputLabel = computed(() =>
  props.currentPeriod === 'monthly' ? '월간 연도 검색' : '연간 연도 검색'
)

const dateInputAttrs = computed(() => ({
  min: '2020',
  max: String(new Date().getFullYear()),
  placeholder: '연도 (예: 2026)',
  step: '1',
}))

// ── 네비게이션 ────────────────────────────────────────────────────
const changePeriod = (period) => {
  localDate.value = ''
  router.visit('/stats', { data: { period, medal_period: props.medalPeriod }, preserveScroll: true })
}

const applyDateSearch = () => {
  if (!localDate.value) return clearDateSearch()
  router.visit('/stats', {
    data: { period: props.currentPeriod, date: localDate.value, medal_period: props.medalPeriod },
    preserveScroll: true,
  })
}

const clearDateSearch = () => {
  localDate.value = ''
  router.visit('/stats', {
    data: { period: props.currentPeriod, medal_period: props.medalPeriod },
    preserveScroll: true,
  })
}

const changeMedalPeriod = (period) => {
  const params = { period: props.currentPeriod, medal_period: period }
  if (props.searchDate) params.date = props.searchDate
  router.visit('/stats', { data: params, preserveScroll: true })
}

// ── 진영 랭킹 카드 ────────────────────────────────────────────────
const rankedFactions = computed(() =>
  Object.entries(props.factionStats)
    .sort((a, b) => (b[1].score ?? 0) - (a[1].score ?? 0))
    .map(([key, val], i) => ({ key, ...val, rank: i + 1, ...factionConfig[key] }))
)

const maxScore  = computed(() => Math.max(1, ...rankedFactions.value.map(f => f.score ?? 0)))
const rankEmoji = (rank) => ['🥇', '🥈', '🥉'][rank - 1] ?? ''

// ── 메달 데이터 ───────────────────────────────────────────────────
const sortedMedals = computed(() => {
  const medals = props.medalData?.medals ?? {}
  return Object.entries(medals)
    .sort(([, a], [, b]) => {
      if (a.gold   !== b.gold)   return b.gold   - a.gold
      if (a.silver !== b.silver) return b.silver - a.silver
      return b.bronze - a.bronze
    })
    .map(([key, val], i) => ({ key, ...val, rank: i + 1, ...factionConfig[key] }))
})

const medalRangeLabel = computed(() => {
  const from = props.medalData?.from ?? ''
  const to   = props.medalData?.to ?? ''
  if (!from || !to) return ''
  const fmt = (d) => {
    const [, m, day] = d.split('-')
    return `${parseInt(m)}/${parseInt(day)}`
  }
  return `${fmt(from)} ~ ${fmt(to)}`
})

// ── 차트 ──────────────────────────────────────────────────────────
const canvasRef   = ref(null)
const showDetail  = ref(false)
const showFormula = ref(false)

// X축 레이블 포맷 (bar chart 용)
const formatXLabel = (dateStr) => {
  const s = String(dateStr ?? '')
  if (s === '연간합계') return '연간합계'
  // "2026-07" → "7월"
  const parts = s.split('-')
  return parts.length >= 2 ? parseInt(parts[1]) + '월' : s
}

const drawChart = () => {
  const canvas = canvasRef.value
  if (!canvas || !props.periodData.length) return

  // CSS w-full 이 정한 content 폭을 읽음 (JS 스타일 먼저 초기화)
  canvas.style.width  = ''
  canvas.style.height = ''
  const dpr      = window.devicePixelRatio || 1
  const displayW = canvas.clientWidth || canvas.parentElement?.clientWidth || 900
  const isMob    = displayW < 480
  const displayH = isMob ? 200 : 260

  canvas.width        = displayW * dpr
  canvas.height       = displayH * dpr
  canvas.style.width  = displayW + 'px'
  canvas.style.height = displayH + 'px'

  const ctx    = canvas.getContext('2d')
  ctx.scale(dpr, dpr)

  const W      = displayW
  const H      = displayH
  const isDark = document.documentElement.classList.contains('dark')
  const pad    = {
    top:    isMob ? 28 : 36,
    right:  isMob ? 10 : 18,
    bottom: isMob ? 36 : 46,
    left:   isMob ? 36 : 46,
  }

  ctx.clearRect(0, 0, W, H)
  ctx.fillStyle = isDark ? '#0f172a' : '#ffffff'
  ctx.fillRect(0, 0, W, H)

  const data      = props.periodData
  const factions  = ['conservative', 'moderate', 'progressive']
  const colors    = { conservative: '#E24B4A', moderate: '#7F77DD', progressive: '#378ADD' }
  const factionKo = { conservative: '보수', moderate: '중도', progressive: '진보' }
  const chartW    = W - pad.left - pad.right
  const chartH    = H - pad.top  - pad.bottom
  const gridCount = isMob ? 3 : 4
  const fontSize  = isMob ? 9 : 11
  const textColor = isDark ? '#64748b' : '#94a3b8'
  const gridColor = isDark ? '#1e293b' : '#f1f5f9'
  const isDailyMode = props.currentPeriod === 'daily'

  // ── 스케일 ──────────────────────────────────────────────────
  const d0       = data[0] || {}
  const allVals  = isDailyMode
    ? factions.map(f => d0[f] ?? 0)
    : data.flatMap(d => factions.map(f => d[f] ?? 0))
  const maxVal    = Math.max(1, ...allVals)
  const displayMax = maxVal * 1.18
  const yScale    = chartH / displayMax

  // ── 그리드 & Y 레이블 ───────────────────────────────────────
  ctx.font = `${fontSize}px sans-serif`
  for (let i = 0; i <= gridCount; i++) {
    const y = pad.top + chartH * (i / gridCount)
    ctx.strokeStyle = gridColor; ctx.lineWidth = 1
    ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(W - pad.right, y); ctx.stroke()
    ctx.fillStyle = textColor; ctx.textAlign = 'right'
    const val = displayMax * (1 - i / gridCount)
    ctx.fillText(val >= 10 ? Math.round(val) : val.toFixed(1), pad.left - 4, y + 3)
  }

  // ── 일간: 진영별 단독 막대 3개 ────────────────────────────────
  if (isDailyMode) {
    const totalArea = chartW * 0.72
    const barW      = totalArea / 3
    const startX    = pad.left + (chartW - totalArea) / 2

    factions.forEach((faction, i) => {
      const val  = d0[faction] ?? 0
      const barH = val * yScale
      const x    = startX + i * barW
      const bx   = x + barW * 0.12
      const bw   = barW * 0.76
      const by   = H - pad.bottom - barH

      // 막대
      ctx.fillStyle = colors[faction]
      ctx.fillRect(bx, by, bw, Math.max(barH, 2))

      // 막대 위 수치
      ctx.fillStyle = isDark ? '#e2e8f0' : '#374151'
      ctx.font      = `bold ${fontSize + 1}px sans-serif`
      ctx.textAlign = 'center'
      ctx.fillText(val >= 10 ? Math.round(val) : val.toFixed(2), x + barW / 2, by - 7)

      // X 레이블 (진영명 — 진영 색상으로)
      ctx.fillStyle = colors[faction]
      ctx.font      = `bold ${fontSize}px sans-serif`
      ctx.fillText(factionKo[faction], x + barW / 2, H - pad.bottom + (isMob ? 13 : 16))
    })
    return
  }

  // ── 월간/연간: 그룹 막대 차트 ────────────────────────────────
  const groupCount  = data.length
  const groupW      = chartW / groupCount
  const gapFraction = groupCount <= 4 ? 0.28 : 0.18
  const rawBarW     = (groupW * (1 - gapFraction)) / 3
  const maxBarW     = isMob ? 16 : 24
  const barW        = Math.min(rawBarW, maxBarW)
  const groupInner  = barW * 3

  data.forEach((d, gi) => {
    const isTotal    = d.date === '연간합계'
    const groupCX    = pad.left + gi * groupW + groupW / 2
    const barsStartX = groupCX - groupInner / 2

    // 연간합계 앞 구분 점선
    if (isTotal) {
      ctx.save()
      ctx.strokeStyle = isDark ? '#475569' : '#cbd5e1'
      ctx.lineWidth   = 1
      ctx.setLineDash([3, 3])
      ctx.beginPath()
      ctx.moveTo(groupCX - groupW / 2 + 2, pad.top)
      ctx.lineTo(groupCX - groupW / 2 + 2, H - pad.bottom)
      ctx.stroke()
      ctx.restore()
    }

    factions.forEach((faction, fi) => {
      const val  = d[faction] ?? 0
      const barH = val * yScale
      const x    = barsStartX + fi * barW
      const by   = H - pad.bottom - barH

      ctx.fillStyle = isTotal ? colors[faction] + 'cc' : colors[faction]
      ctx.fillRect(x, by, barW - 1, Math.max(barH, 1))
    })

    // X 레이블
    const label = isTotal
      ? (isMob ? '합계' : '연간합계')
      : formatXLabel(d.date)
    ctx.fillStyle = isTotal ? (isDark ? '#94a3b8' : '#475569') : textColor
    ctx.font      = isTotal ? `bold ${fontSize}px sans-serif` : `${fontSize}px sans-serif`
    ctx.textAlign = 'center'
    ctx.fillText(label, groupCX, H - pad.bottom + (isMob ? 13 : 16))
  })
}

// ── ResizeObserver ────────────────────────────────────────────────
let ro = null
onMounted(() => {
  setTimeout(drawChart, 60)
  ro = new ResizeObserver(() => drawChart())
  const container = canvasRef.value?.parentElement
  if (container) ro.observe(container)
})
onUnmounted(() => ro?.disconnect())
watch(() => [props.periodData, props.currentPeriod], () => setTimeout(drawChart, 60))

// ── 상세 테이블 레이블 ────────────────────────────────────────────
const detailDateLabel = (item) => {
  const d = String(item.date ?? '')
  if (d === '연간합계') return '연간합계'
  switch (props.currentPeriod) {
    case 'monthly':
    case 'yearly': {
      // "2026-06" → "2026년 6월"
      const [yr, mo] = d.split('-')
      return mo ? `${yr}년 ${parseInt(mo)}월` : `${d}년`
    }
    default:
      return '오늘'
  }
}
</script>

<template>
<Head title="진영 점수 현황">
  <meta name="description" content="보수·중도·진보 진영의 일간·월간·연간 점수 현황과 금은동 메달 순위를 확인하세요." />
  <meta property="og:title" content="진영 점수 현황 — 폴릿" />
  <meta property="og:description" content="보수·중도·진보 진영의 점수 현황과 메달 순위" />
</Head>
  <div class="max-w-5xl mx-auto px-4 py-8">

    <!-- ── 헤더 ─────────────────────────────────────────────── -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-black text-gray-900 dark:text-white flex items-center gap-2">
          📊 진영 통계
        </h1>
        <p class="text-gray-500 dark:text-slate-400 text-sm mt-1">진영별 실시간 영향력 점수와 활동 현황</p>
      </div>
    </div>

    <!-- ── 진영 랭킹 카드 ────────────────────────────────────── -->
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

    <!-- ── 차트 영역 ─────────────────────────────────────────── -->
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-5 mb-4">

      <!-- 기간 탭 + 날짜 검색 -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <h2 class="font-bold text-gray-800 dark:text-slate-200 flex-shrink-0">점수 추세</h2>

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-3 flex-1 sm:justify-end">
          <!-- 기간 탭 -->
          <div class="flex bg-gray-100 dark:bg-slate-800 rounded-xl p-1 gap-1">
            <button
              v-for="tab in periodTabs"
              :key="tab.key"
              @click="changePeriod(tab.key)"
              :class="[
                'px-3 py-1.5 rounded-lg text-xs sm:text-sm font-medium transition-colors',
                currentPeriod === tab.key
                  ? 'bg-white dark:bg-slate-700 text-gray-900 dark:text-white shadow-sm'
                  : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200',
              ]"
            >{{ tab.label }}</button>
          </div>

          <!-- 날짜 검색 — 일간은 항상 실시간이라 숨김 -->
          <div v-if="showDateSearch" class="flex items-center gap-1.5 w-full sm:w-auto">
            <input
              v-model="localDate"
              :type="dateInputType"
              v-bind="dateInputAttrs"
              :title="dateInputLabel"
              class="flex-1 sm:w-28 text-xs border border-gray-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-violet-400"
              @keydown.enter="applyDateSearch"
            />
            <button
              @click="applyDateSearch"
              class="flex-shrink-0 px-2.5 py-1.5 text-xs font-semibold bg-violet-600 hover:bg-violet-500 text-white rounded-lg transition-colors"
            >검색</button>
            <button
              v-if="searchDate"
              @click="clearDateSearch"
              class="flex-shrink-0 px-2.5 py-1.5 text-xs font-semibold bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-600 dark:text-slate-300 rounded-lg transition-colors"
            >초기화</button>
          </div>
        </div>
      </div>

      <!-- 범례 — 월간/연간 그룹 막대 차트에서만 표시 (일간은 막대 아래에 진영명이 직접 표시됨) -->
      <div v-if="currentPeriod !== 'daily'" class="flex gap-4 mb-3">
        <div v-for="(cfg, key) in factionConfig" :key="key" class="flex items-center gap-1.5">
          <span class="w-2.5 h-2.5 rounded-full inline-block flex-shrink-0" :style="{ backgroundColor: cfg.color }"></span>
          <span class="text-xs text-gray-500 dark:text-slate-400">{{ cfg.label }}</span>
        </div>
      </div>
      <!-- 일간 안내 문구 -->
      <p v-else class="text-xs text-gray-400 dark:text-slate-500 mb-3">
        실시간 오늘의 진영 점수
      </p>

      <!-- 캔버스 -->
      <div v-if="!periodData.length" class="flex items-center justify-center h-40 text-gray-400 dark:text-slate-600 text-sm">
        집계된 데이터가 없습니다.
      </div>
      <canvas
        v-else
        ref="canvasRef"
        class="w-full rounded-xl block"
      ></canvas>

      <!-- 상세보기 아코디언 -->
      <div v-if="periodData.length" class="mt-4 border-t border-gray-100 dark:border-slate-800 pt-3">
        <button
          @click="showDetail = !showDetail"
          class="flex items-center gap-2 text-xs font-medium text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200 transition-colors"
        >
          <svg
            :class="['w-3.5 h-3.5 transition-transform duration-200', showDetail ? 'rotate-90' : '']"
            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
          </svg>
          {{ showDetail ? '상세 접기' : '상세보기' }}
        </button>

        <transition
          enter-active-class="transition-all duration-200 ease-out"
          enter-from-class="opacity-0"
          enter-to-class="opacity-100"
          leave-active-class="transition-all duration-150 ease-in"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div v-if="showDetail" class="mt-3 overflow-x-auto">
            <table class="w-full text-xs border-collapse">
              <thead>
                <tr class="text-left">
                  <th class="pb-2 pr-4 font-semibold text-gray-400 dark:text-slate-500 whitespace-nowrap">기간</th>
                  <th v-for="(cfg, key) in factionConfig" :key="key" class="pb-2 pr-3 font-semibold whitespace-nowrap" :style="{ color: cfg.color }">
                    {{ cfg.emoji }} {{ cfg.label }}
                  </th>
                  <th class="pb-2 font-semibold text-gray-400 dark:text-slate-500 whitespace-nowrap">1위</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="row in [...periodData].reverse()"
                  :key="row.date"
                  class="border-t border-gray-50 dark:border-slate-800/60 hover:bg-gray-50 dark:hover:bg-slate-800/30"
                >
                  <td class="py-1.5 pr-4 font-medium text-gray-600 dark:text-slate-400 whitespace-nowrap">
                    {{ detailDateLabel(row) }}
                  </td>
                  <td v-for="(cfg, key) in factionConfig" :key="key" class="py-1.5 pr-3 font-mono whitespace-nowrap" :style="{ color: cfg.color }">
                    {{ (row[key] ?? 0).toFixed(2) }}
                  </td>
                  <td class="py-1.5 whitespace-nowrap">
                    <span
                      v-if="Math.max(row.conservative ?? 0, row.moderate ?? 0, row.progressive ?? 0) > 0"
                      class="font-semibold"
                      :style="{ color: factionConfig[Object.entries({ conservative: row.conservative ?? 0, moderate: row.moderate ?? 0, progressive: row.progressive ?? 0 }).sort((a,b)=>b[1]-a[1])[0][0]].color }"
                    >
                      {{ factionConfig[Object.entries({ conservative: row.conservative ?? 0, moderate: row.moderate ?? 0, progressive: row.progressive ?? 0 }).sort((a,b)=>b[1]-a[1])[0][0]].emoji }}
                      {{ factionConfig[Object.entries({ conservative: row.conservative ?? 0, moderate: row.moderate ?? 0, progressive: row.progressive ?? 0 }).sort((a,b)=>b[1]-a[1])[0][0]].label }}
                    </span>
                    <span v-else class="text-gray-300 dark:text-slate-700">—</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </transition>
      </div>
    </div>

    <!-- ── 메달 랭킹 ─────────────────────────────────────────── -->
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-5 mb-4">
      <!-- 헤더 -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
          <h2 class="font-bold text-gray-800 dark:text-slate-200">🏅 진영 메달 랭킹</h2>
          <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">
            {{ medalRangeLabel }}
            <span v-if="medalData?.total_days" class="ml-1">({{ medalData.total_days }}일 집계)</span>
          </p>
        </div>
        <!-- 메달 기간 탭 -->
        <div class="flex bg-gray-100 dark:bg-slate-800 rounded-xl p-1 gap-1">
          <button
            v-for="tab in medalPeriodTabs"
            :key="tab.key"
            @click="changeMedalPeriod(tab.key)"
            :class="[
              'px-3 py-1.5 rounded-lg text-xs font-medium transition-colors whitespace-nowrap',
              medalPeriod === tab.key
                ? 'bg-white dark:bg-slate-700 text-gray-900 dark:text-white shadow-sm'
                : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200',
            ]"
          >{{ tab.label }}</button>
        </div>
      </div>

      <!-- 데이터 없을 때 -->
      <div v-if="!sortedMedals.length || medalData?.total_days === 0"
        class="flex items-center justify-center h-24 text-sm text-gray-400 dark:text-slate-600"
      >
        아직 집계된 메달이 없습니다.
      </div>

      <!-- 메달 테이블 -->
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
          <thead>
            <tr>
              <th class="pb-2.5 text-left font-semibold text-gray-400 dark:text-slate-500 text-xs w-8">순위</th>
              <th class="pb-2.5 text-left font-semibold text-gray-400 dark:text-slate-500 text-xs">진영</th>
              <th class="pb-2.5 text-center font-semibold text-yellow-500 text-xs w-16">🥇</th>
              <th class="pb-2.5 text-center font-semibold text-slate-400 text-xs w-16">🥈</th>
              <th class="pb-2.5 text-center font-semibold text-amber-600 text-xs w-16">🥉</th>
              <th class="pb-2.5 text-center font-semibold text-gray-400 dark:text-slate-500 text-xs w-16">합계</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="m in sortedMedals"
              :key="m.key"
              class="border-t border-gray-100 dark:border-slate-800"
            >
              <td class="py-3 text-sm font-bold text-gray-400 dark:text-slate-500">
                {{ rankEmoji(m.rank) || m.rank }}
              </td>
              <td class="py-3">
                <div class="flex items-center gap-2">
                  <div
                    class="w-8 h-8 rounded-xl flex items-center justify-center text-base flex-shrink-0"
                    :style="{ backgroundColor: m.color + '22' }"
                  >{{ m.emoji }}</div>
                  <span class="font-bold text-gray-800 dark:text-slate-200">{{ m.label }}</span>
                </div>
              </td>
              <!-- 금 -->
              <td class="py-3 text-center">
                <span
                  :class="[
                    'inline-flex items-center justify-center w-9 h-7 rounded-lg text-sm font-black',
                    m.gold > 0 ? 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-600 dark:text-yellow-400' : 'text-gray-200 dark:text-slate-700',
                  ]"
                >{{ m.gold }}</span>
              </td>
              <!-- 은 -->
              <td class="py-3 text-center">
                <span
                  :class="[
                    'inline-flex items-center justify-center w-9 h-7 rounded-lg text-sm font-black',
                    m.silver > 0 ? 'bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-300' : 'text-gray-200 dark:text-slate-700',
                  ]"
                >{{ m.silver }}</span>
              </td>
              <!-- 동 -->
              <td class="py-3 text-center">
                <span
                  :class="[
                    'inline-flex items-center justify-center w-9 h-7 rounded-lg text-sm font-black',
                    m.bronze > 0 ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-500' : 'text-gray-200 dark:text-slate-700',
                  ]"
                >{{ m.bronze }}</span>
              </td>
              <!-- 합계 -->
              <td class="py-3 text-center">
                <span class="text-sm font-black text-gray-700 dark:text-slate-300">{{ m.total }}</span>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- 메달 안내 -->
        <p class="mt-3 text-[11px] text-gray-400 dark:text-slate-600">
          * 매일 00:05 집계 기준 / 1위=금🥇 2위=은🥈 3위=동🥉 / 동점 시 진영 순서 유지
        </p>
      </div>
    </div>

    <!-- ── 점수 산정 방식 안내 ────────────────────────────────── -->
    <div class="rounded-2xl border border-gray-200 dark:border-slate-800 overflow-hidden">
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
        enter-to-class="opacity-100 max-h-[600px]"
        leave-active-class="transition-all duration-150 ease-in"
        leave-from-class="opacity-100 max-h-[600px]"
        leave-to-class="opacity-0 max-h-0"
      >
        <div v-if="showFormula" class="px-5 py-4 bg-white dark:bg-slate-900 border-t border-gray-100 dark:border-slate-800 overflow-hidden">
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

          <div class="text-xs text-gray-400 dark:text-slate-500 space-y-1 border-t border-gray-100 dark:border-slate-700 pt-3">
            <p>• 영향력 점수는 <strong class="text-gray-500 dark:text-slate-400">진영 활성 사용자 수</strong>로 나누어 정규화합니다.</p>
            <p>• <strong class="text-gray-500 dark:text-slate-400">놀이터</strong> 게시판 활동은 집계에서 제외됩니다.</p>
            <p>• 점수는 <strong class="text-gray-500 dark:text-slate-400">매일 00:05</strong>에 전날 활동 기준으로 집계됩니다.</p>
            <p>• 테스트 계정의 활동은 집계에서 제외됩니다.</p>
            <p>• 매너 점수 기본 100점. 타 진영 추천 +1 / 같은 진영 비추천 −1 / 신고처리 −10.</p>
          </div>
        </div>
      </transition>
    </div>

  </div>
</template>
