<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps<{
  /**
   * { political_type, faction_label, faction_emoji, faction_color, score, description }
   * 로그인 유저: DB에서 읽은 값
   * 비로그인:    세션에서 읽은 계산 결과
   */
  result: {
    political_type: string
    faction_label: string
    faction_emoji: string
    faction_color: string
    score: number
    description?: string | null
    breakdown?: Record<string, number>
  }
  /**
   * 'register' → 회원가입 페이지에서 테스트로 진입한 경우
   * null/undefined → 메뉴에서 직접 테스트한 경우
   */
  source?: string | null
}>()

// ── SEO ──────────────────────────────────────────────────────────
const seoTitle = computed(() => `나는 ${props.result.faction_emoji} ${props.result.faction_label} 성향`)
const seoDesc  = computed(() => props.result.description ?? `정치 성향 테스트 결과: ${props.result.faction_label}. 폴릿에서 나의 성향을 확인해보세요.`)

const page = usePage()
const isLoggedIn = computed(() => !!(page.props as any).auth?.user)

// ── 진영별 스타일 설정 ────────────────────────────────────────────────────
const factionConfig: Record<string, {
  bgClass: string; borderClass: string; textClass: string
  gradientFrom: string; gradientTo: string; scoreBarClass: string
  summary: string
}> = {
  conservative: {
    bgClass: 'bg-red-500/10',
    borderClass: 'border-red-500/40',
    textClass: 'text-red-400',
    gradientFrom: 'from-red-600',
    gradientTo: 'to-red-400',
    scoreBarClass: 'bg-red-500',
    summary: '당신은 전통과 질서를 중시하는 보수주의자입니다.',
  },
  moderate: {
    bgClass: 'bg-violet-500/10',
    borderClass: 'border-violet-500/40',
    textClass: 'text-violet-400',
    gradientFrom: 'from-violet-600',
    gradientTo: 'to-violet-400',
    scoreBarClass: 'bg-violet-500',
    summary: '당신은 균형 잡힌 시각을 가진 중도주의자입니다.',
  },
  progressive: {
    bgClass: 'bg-blue-500/10',
    borderClass: 'border-blue-500/40',
    textClass: 'text-blue-400',
    gradientFrom: 'from-blue-600',
    gradientTo: 'to-blue-400',
    scoreBarClass: 'bg-blue-500',
    summary: '당신은 변화와 평등을 추구하는 진보주의자입니다.',
  },
}

const config = computed(
  () => factionConfig[props.result?.political_type] ?? factionConfig.moderate
)

// 스코어 -100 ~ +100 → 미터 위치 0 ~ 100%
const score = computed(() => props.result?.score ?? 0)
const meterPosition = computed(() =>
  Math.min(100, Math.max(0, (score.value + 100) / 2))
)

// 회원가입으로 돌아갈 때 쓸 URL
const applyUrl = computed(
  () => `/register?faction=${props.result?.political_type}`
)
</script>

<template>
<Head :title="seoTitle">
  <meta name="description" :content="seoDesc" />
  <meta property="og:title" :content="`${seoTitle} — 폴릿`" />
  <meta property="og:description" :content="seoDesc" />
</Head>
  <div class="min-h-[calc(100vh-10rem)] flex items-center justify-center px-4 py-16">
    <div class="w-full max-w-2xl">

      <!-- 상단 라벨 -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center gap-2 bg-gray-100 dark:bg-slate-800 border border-gray-300 dark:border-slate-700 rounded-full px-4 py-1.5 text-slate-500 dark:text-slate-400 text-xs mb-6">
          🧭 성향 테스트 결과
        </div>

        <!-- 진영 배지 -->
        <div :class="['inline-flex flex-col items-center gap-4 rounded-3xl border-2 px-12 py-8 mb-6', config.bgClass, config.borderClass]">
          <span class="text-7xl">{{ result?.faction_emoji }}</span>
          <div>
            <p class="text-slate-500 dark:text-slate-400 text-sm mb-1">당신의 정치 성향은</p>
            <h1 :class="['text-5xl font-black', config.textClass]">{{ result?.faction_label }}</h1>
          </div>
        </div>

        <p class="text-slate-600 dark:text-slate-300 font-medium text-lg mb-2">{{ config.summary }}</p>
        <p v-if="result?.description" class="text-slate-400 dark:text-slate-500 text-sm leading-relaxed max-w-lg mx-auto">
          {{ result.description }}
        </p>
      </div>

      <!-- 성향 점수 스펙트럼 -->
      <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-6 mb-6">
        <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 mb-4 text-center">성향 점수 스펙트럼</h3>
        <div class="flex justify-between text-xs mb-2">
          <span class="text-blue-400 font-medium">← 진보</span>
          <span class="text-slate-400 dark:text-slate-500">중도</span>
          <span class="text-red-400 font-medium">보수 →</span>
        </div>
        <div class="relative h-4 bg-gradient-to-r from-blue-600 via-gray-300 to-red-600 dark:from-blue-800 dark:via-slate-700 dark:to-red-800 rounded-full">
          <div class="absolute left-1/2 top-0 bottom-0 w-px bg-gray-400 dark:bg-slate-500 -translate-x-1/2"></div>
          <div
            class="absolute top-1/2 -translate-y-1/2 w-5 h-5 rounded-full border-2 border-white shadow-lg transition-all duration-700"
            :class="config.scoreBarClass"
            :style="{ left: `calc(${meterPosition}% - 10px)` }"
          ></div>
        </div>
        <div class="text-center mt-4">
          <span :class="['text-3xl font-black tabular-nums', config.textClass]">
            {{ score > 0 ? '+' : '' }}{{ score }}
          </span>
          <span class="text-slate-400 dark:text-slate-500 text-sm ml-1">/ ±100</span>
        </div>
      </div>

      <!-- 카테고리별 분석 (있을 때만) -->
      <div v-if="result?.breakdown" class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-6 mb-6">
        <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 mb-4">카테고리별 분석</h3>
        <div class="space-y-3">
          <div v-for="(val, key) in result.breakdown" :key="key">
            <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400 mb-1">
              <span>{{ key }}</span>
              <span :class="config.textClass">{{ val > 0 ? '+' : '' }}{{ val }}</span>
            </div>
            <div class="h-1.5 bg-gray-200 dark:bg-slate-800 rounded-full overflow-hidden">
              <div
                :class="['h-full rounded-full', config.scoreBarClass]"
                :style="{ width: Math.min(100, Math.abs(val)) + '%' }"
              ></div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── CTA 버튼 ────────────────────────────────────────────────────── -->

      <!-- 케이스 A: 회원가입에서 테스트로 진입한 비로그인 유저 -->
      <div v-if="source === 'register' && !isLoggedIn" class="space-y-3">
        <!-- 결과 적용 → 회원가입 복귀 -->
        <Link
          :href="applyUrl"
          :class="['flex items-center justify-center gap-3 w-full py-4 rounded-xl font-bold text-white text-lg transition-all shadow-lg hover:-translate-y-0.5 bg-gradient-to-r', config.gradientFrom, config.gradientTo]"
        >
          <span>{{ result?.faction_emoji }}</span>
          이 결과 적용하기 (회원가입으로)
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
          </svg>
        </Link>
        <!-- 다시 테스트 -->
        <Link
          href="/political-test?source=register"
          class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 border border-gray-300 dark:border-slate-700 text-slate-500 dark:text-slate-400 text-sm font-medium transition-colors"
        >
          🔄 다시 테스트하기
        </Link>
        <!-- 회원가입 직접 이동 -->
        <Link
          href="/register"
          class="flex items-center justify-center gap-2 w-full py-2.5 text-slate-400 dark:text-slate-600 hover:text-slate-500 dark:hover:text-slate-500 text-xs transition-colors"
        >
          진영을 직접 선택하고 싶어요 →
        </Link>
      </div>

      <!-- 케이스 B: 로그인한 유저가 테스트한 경우 (source 무관) -->
      <div v-else-if="isLoggedIn" class="space-y-3">
        <Link
          href="/boards"
          :class="['flex items-center justify-center gap-3 w-full py-4 rounded-xl font-bold text-white text-lg transition-all shadow-lg hover:-translate-y-0.5 bg-gradient-to-r', config.gradientFrom, config.gradientTo]"
        >
          <span>{{ result?.faction_emoji }}</span>
          커뮤니티 입장하기
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
          </svg>
        </Link>
        <Link
          href="/political-test"
          class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 border border-gray-300 dark:border-slate-700 text-slate-500 dark:text-slate-400 text-sm font-medium transition-colors"
        >
          🔄 테스트 다시 하기
        </Link>
      </div>

      <!-- 케이스 C: 비로그인 유저가 메뉴에서 테스트한 경우 -->
      <div v-else class="space-y-3">
        <!-- 해당 진영으로 바로 회원가입 -->
        <Link
          :href="applyUrl"
          :class="['flex items-center justify-center gap-3 w-full py-4 rounded-xl font-bold text-white text-lg transition-all shadow-lg hover:-translate-y-0.5 bg-gradient-to-r', config.gradientFrom, config.gradientTo]"
        >
          <span>{{ result?.faction_emoji }}</span>
          {{ result?.faction_label }} 진영으로 가입하기
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
          </svg>
        </Link>
        <Link
          href="/political-test"
          class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 border border-gray-300 dark:border-slate-700 text-slate-500 dark:text-slate-400 text-sm font-medium transition-colors"
        >
          🔄 테스트 다시 하기
        </Link>
        <Link
          href="/register"
          class="flex items-center justify-center gap-2 w-full py-2.5 text-slate-400 dark:text-slate-600 hover:text-slate-500 dark:hover:text-slate-500 text-xs transition-colors"
        >
          진영을 직접 선택하고 싶어요 →
        </Link>
      </div>

    </div>
  </div>
</template>
