<script setup>
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  postStats:     { type: Object, default: () => ({}) },
  postsByBoard:  { type: Array,  default: () => [] },
  activityByDay: { type: Array,  default: () => [] },
  reportCount:   { type: Number, default: 0 },
  reportedCount: { type: Number, default: 0 },
  user:          { type: Object, required: true },
})

const factionColor = computed(() => props.user.faction_color ?? '#7F77DD')

const mannerColor = computed(() => {
  const s = props.user.manner_score ?? 100
  if (s >= 80) return 'text-emerald-500'
  if (s >= 50) return 'text-amber-500'
  return 'text-rose-500'
})

// 히트맵: 최근 30일 날짜별 활동
const activityMap = computed(() => {
  const map = {}
  props.activityByDay.forEach(r => { map[r.date] = r.count })
  return map
})

const last30Days = computed(() => {
  const days = []
  for (let i = 29; i >= 0; i--) {
    const d = new Date()
    d.setDate(d.getDate() - i)
    const key = d.toISOString().slice(0, 10)
    days.push({ date: key, count: activityMap.value[key] ?? 0 })
  }
  return days
})

const maxActivity = computed(() => Math.max(1, ...last30Days.value.map(d => d.count)))

const heatColor = (count) => {
  if (count === 0) return 'bg-slate-200 dark:bg-slate-800'
  const ratio = count / maxActivity.value
  if (ratio > 0.75) return 'bg-violet-600'
  if (ratio > 0.5)  return 'bg-violet-500'
  if (ratio > 0.25) return 'bg-violet-400'
  return 'bg-violet-300'
}
</script>

<template>
  <div class="max-w-4xl mx-auto px-4 py-8">
    <!-- 헤더 -->
    <div class="mb-8">
      <h1 class="text-2xl font-black text-gray-900 dark:text-white">내 통계</h1>
      <p class="text-gray-500 dark:text-slate-400 text-sm mt-1">나의 활동 기록과 기여도를 확인합니다.</p>
    </div>

    <!-- 내 정보 카드 -->
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-6 mb-5">
      <div class="flex items-center gap-5">
        <div class="w-16 h-16 rounded-full flex items-center justify-center text-2xl font-black text-white"
          :style="{ backgroundColor: factionColor }">
          {{ user.nickname?.[0] }}
        </div>
        <div>
          <h2 class="text-xl font-black text-gray-900 dark:text-white">{{ user.nickname }}</h2>
          <div class="flex items-center gap-3 mt-1">
            <span class="text-sm font-semibold" :style="{ color: factionColor }">{{ user.faction_label }}</span>
            <span class="text-xs text-gray-400 dark:text-slate-500">가입: {{ user.joined_at }}</span>
          </div>
        </div>
        <div class="ml-auto text-center">
          <p class="text-xs text-gray-500 dark:text-slate-500 mb-1">매너 점수</p>
          <p class="text-3xl font-black" :class="mannerColor">{{ user.manner_score }}</p>
        </div>
      </div>
    </div>

    <!-- 활동 요약 카드 -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
      <div v-for="stat in [
        { label: '작성 게시글', value: postStats?.total ?? 0, icon: '📝' },
        { label: '총 조회수',   value: (postStats?.total_views ?? 0).toLocaleString(), icon: '👁' },
        { label: '총 추천',     value: postStats?.total_votes_up ?? 0, icon: '👍' },
        { label: '총 댓글',     value: postStats?.total_comments ?? 0, icon: '💬' },
      ]" :key="stat.label"
        class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-5 text-center">
        <p class="text-2xl mb-1">{{ stat.icon }}</p>
        <p class="text-2xl font-black text-gray-900 dark:text-white">{{ stat.value }}</p>
        <p class="text-xs text-gray-500 dark:text-slate-500 mt-1">{{ stat.label }}</p>
      </div>
    </div>

    <!-- 최근 30일 활동 히트맵 -->
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-6 mb-5">
      <h3 class="text-sm font-bold text-gray-700 dark:text-slate-300 mb-4">최근 30일 활동</h3>
      <div class="flex gap-1 flex-wrap">
        <div
          v-for="day in last30Days"
          :key="day.date"
          :class="['w-7 h-7 rounded-md cursor-default transition-colors', heatColor(day.count)]"
          :title="`${day.date}: ${day.count}건`"
        ></div>
      </div>
      <div class="flex items-center gap-2 mt-3 text-xs text-gray-400 dark:text-slate-600">
        <span>적음</span>
        <div class="w-4 h-4 rounded bg-slate-200 dark:bg-slate-800"></div>
        <div class="w-4 h-4 rounded bg-violet-300"></div>
        <div class="w-4 h-4 rounded bg-violet-400"></div>
        <div class="w-4 h-4 rounded bg-violet-500"></div>
        <div class="w-4 h-4 rounded bg-violet-600"></div>
        <span>많음</span>
      </div>
    </div>

    <!-- 게시판별 활동 -->
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-6 mb-5">
      <h3 class="text-sm font-bold text-gray-700 dark:text-slate-300 mb-4">게시판별 게시글</h3>
      <div v-if="postsByBoard.length === 0" class="text-center py-6 text-gray-400 dark:text-slate-600">
        게시글 없음
      </div>
      <div v-else class="space-y-3">
        <div v-for="board in postsByBoard" :key="board.board_name" class="flex items-center gap-3">
          <span class="text-sm text-gray-600 dark:text-slate-400 w-32 truncate">{{ board.board_name }}</span>
          <div class="flex-1 bg-gray-100 dark:bg-slate-800 rounded-full h-2">
            <div
              class="h-2 rounded-full bg-violet-500 transition-all"
              :style="{ width: `${Math.min(100, (board.count / (postsByBoard[0]?.count || 1)) * 100)}%` }"
            ></div>
          </div>
          <span class="text-sm font-bold text-gray-900 dark:text-white w-8 text-right">{{ board.count }}</span>
        </div>
      </div>
    </div>

    <!-- 신고 통계 -->
    <div class="grid grid-cols-2 gap-4">
      <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-5 text-center">
        <p class="text-3xl font-black text-amber-500">{{ reportCount }}</p>
        <p class="text-xs text-gray-500 dark:text-slate-500 mt-1">내가 한 신고</p>
      </div>
      <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-5 text-center">
        <p class="text-3xl font-black text-rose-500">{{ reportedCount }}</p>
        <p class="text-xs text-gray-500 dark:text-slate-500 mt-1">내가 받은 신고</p>
      </div>
    </div>
  </div>
</template>
