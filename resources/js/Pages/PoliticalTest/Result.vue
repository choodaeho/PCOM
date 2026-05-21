<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  result: Object,
  // result: { political_type, faction_label, faction_emoji, score, description }
})

const factionConfig = {
  conservative: {
    color: '#E24B4A',
    bgClass: 'bg-red-500/10',
    borderClass: 'border-red-500/40',
    textClass: 'text-red-400',
    gradientFrom: 'from-red-600',
    gradientTo: 'to-red-400',
    scoreBarClass: 'bg-red-500',
    summary: '당신은 전통과 질서를 중시하는 보수주의자입니다.',
  },
  moderate: {
    color: '#7F77DD',
    bgClass: 'bg-violet-500/10',
    borderClass: 'border-violet-500/40',
    textClass: 'text-violet-400',
    gradientFrom: 'from-violet-600',
    gradientTo: 'to-violet-400',
    scoreBarClass: 'bg-violet-500',
    summary: '당신은 균형 잡힌 시각을 가진 중도주의자입니다.',
  },
  progressive: {
    color: '#378ADD',
    bgClass: 'bg-blue-500/10',
    borderClass: 'border-blue-500/40',
    textClass: 'text-blue-400',
    gradientFrom: 'from-blue-600',
    gradientTo: 'to-blue-400',
    scoreBarClass: 'bg-blue-500',
    summary: '당신은 변화와 평등을 추구하는 진보주의자입니다.',
  },
}

const config = computed(() => factionConfig[props.result?.political_type] ?? factionConfig.moderate)

// Score -100 ~ +100, meter center = 0
// Bar position: 0 = far left (progressive), 100 = far right (conservative)
const score = computed(() => props.result?.score ?? 0)
const meterPosition = computed(() => {
  // score -100 ~ +100 mapped to 0 ~ 100%
  return Math.min(100, Math.max(0, (score.value + 100) / 2))
})
</script>

<template>
  <div class="min-h-[calc(100vh-10rem)] flex items-center justify-center px-4 py-16">
    <div class="w-full max-w-2xl">
      <!-- Confetti-like header -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center gap-2 bg-slate-800 border border-slate-700 rounded-full px-4 py-1.5 text-slate-400 text-xs mb-6">
          🧭 성향 테스트 결과
        </div>

        <!-- Faction badge -->
        <div :class="['inline-flex flex-col items-center gap-4 rounded-3xl border-2 px-12 py-8 mb-6', config.bgClass, config.borderClass]">
          <span class="text-7xl">{{ result?.faction_emoji }}</span>
          <div>
            <p class="text-slate-400 text-sm mb-1">당신의 정치 성향은</p>
            <h1 :class="['text-5xl font-black', config.textClass]">{{ result?.faction_label }}</h1>
          </div>
        </div>

        <p class="text-slate-300 font-medium text-lg mb-2">{{ config.summary }}</p>
        <p class="text-slate-500 text-sm leading-relaxed max-w-lg mx-auto">{{ result?.description }}</p>
      </div>

      <!-- Score Meter -->
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-6">
        <h3 class="text-sm font-semibold text-slate-400 mb-4 text-center">성향 점수 스펙트럼</h3>

        <!-- Labels -->
        <div class="flex justify-between text-xs text-slate-500 mb-2">
          <span class="text-blue-400 font-medium">← 진보</span>
          <span class="text-slate-500">중도</span>
          <span class="text-red-400 font-medium">보수 →</span>
        </div>

        <!-- Meter bar -->
        <div class="relative h-4 bg-gradient-to-r from-blue-800 via-slate-700 to-red-800 rounded-full">
          <!-- Center line -->
          <div class="absolute left-1/2 top-0 bottom-0 w-px bg-slate-500 -translate-x-1/2"></div>
          <!-- Indicator -->
          <div
            class="absolute top-1/2 -translate-y-1/2 w-5 h-5 rounded-full border-2 border-white shadow-lg transition-all duration-700"
            :class="config.scoreBarClass"
            :style="{ left: `calc(${meterPosition}% - 10px)` }"
          ></div>
        </div>

        <!-- Score value -->
        <div class="text-center mt-4">
          <span :class="['text-3xl font-black tabular-nums', config.textClass]">
            {{ score > 0 ? '+' : '' }}{{ score }}
          </span>
          <span class="text-slate-500 text-sm ml-1">/ ±100</span>
        </div>
      </div>

      <!-- Score breakdown if available -->
      <div v-if="result?.breakdown" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-6">
        <h3 class="text-sm font-semibold text-slate-400 mb-4">카테고리별 분석</h3>
        <div class="space-y-3">
          <div v-for="(val, key) in result.breakdown" :key="key">
            <div class="flex justify-between text-xs text-slate-400 mb-1">
              <span>{{ key }}</span>
              <span :class="config.textClass">{{ val > 0 ? '+' : '' }}{{ val }}</span>
            </div>
            <div class="h-1.5 bg-slate-800 rounded-full overflow-hidden">
              <div
                :class="['h-full rounded-full', config.scoreBarClass]"
                :style="{ width: Math.min(100, Math.abs(val)) + '%', marginLeft: val < 0 ? 'auto' : '0' }"
              ></div>
            </div>
          </div>
        </div>
      </div>

      <!-- CTA -->
      <div class="space-y-3">
        <Link
          href="/boards"
          :class="['flex items-center justify-center gap-3 w-full py-4 rounded-xl font-bold text-white text-lg transition-all shadow-lg hover:-translate-y-0.5 bg-gradient-to-r', config.gradientFrom, config.gradientTo]"
        >
          <span>{{ result?.faction_emoji }}</span>
          커뮤니티 입장하기
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
          </svg>
        </Link>
        <Link
          href="/political-test"
          class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-400 text-sm font-medium transition-colors"
        >
          테스트 다시 하기
        </Link>
      </div>
    </div>
  </div>
</template>
