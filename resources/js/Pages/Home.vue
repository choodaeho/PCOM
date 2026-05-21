<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const page = usePage()
const scores = computed(() => page.props.realtimeScores ?? [])
const user = computed(() => page.props.auth?.user)

const factions = [
  {
    type: 'conservative',
    emoji: '🦅',
    label: '보수',
    color: '#E24B4A',
    bgClass: 'bg-red-500/10',
    borderClass: 'border-red-500/40',
    textClass: 'text-red-400',
    btnClass: 'bg-red-500 hover:bg-red-400',
    description: '전통과 질서를 수호하고, 자유 시장과 강한 안보를 중시합니다. 검증된 가치와 원칙을 바탕으로 사회 안정을 추구합니다.',
    keywords: ['#자유시장', '#강한안보', '#전통가치', '#작은정부'],
  },
  {
    type: 'moderate',
    emoji: '⚖️',
    label: '중도',
    color: '#7F77DD',
    bgClass: 'bg-violet-500/10',
    borderClass: 'border-violet-500/40',
    textClass: 'text-violet-400',
    btnClass: 'bg-violet-500 hover:bg-violet-400',
    description: '좌우의 극단을 지양하고 실용적 해법을 추구합니다. 다양한 관점을 수용하며 합리적 타협을 통해 사회를 발전시킵니다.',
    keywords: ['#실용주의', '#균형', '#합리적타협', '#중립'],
  },
  {
    type: 'progressive',
    emoji: '🕊️',
    label: '진보',
    color: '#378ADD',
    bgClass: 'bg-blue-500/10',
    borderClass: 'border-blue-500/40',
    textClass: 'text-blue-400',
    btnClass: 'bg-blue-500 hover:bg-blue-400',
    description: '평등과 사회 정의를 추구하고, 적극적 복지와 환경 보호를 지향합니다. 변화와 혁신을 통해 더 나은 사회를 만듭니다.',
    keywords: ['#사회정의', '#복지확대', '#환경보호', '#평등'],
  },
]

const scoreMap = computed(() => {
  const m = {}
  scores.value.forEach(s => { m[s.faction_type] = s })
  return m
})
</script>

<template>
  <div>
    <!-- Hero Section -->
    <section class="relative overflow-hidden">
      <!-- Background gradient -->
      <div class="absolute inset-0 bg-gradient-to-b from-slate-900 via-slate-950 to-slate-950 pointer-events-none" />
      <div class="absolute inset-0 opacity-20 pointer-events-none"
        style="background: radial-gradient(ellipse at 20% 50%, #E24B4A22 0%, transparent 60%), radial-gradient(ellipse at 80% 50%, #378ADD22 0%, transparent 60%), radial-gradient(ellipse at 50% 50%, #7F77DD22 0%, transparent 60%);" />

      <div class="relative max-w-7xl mx-auto px-4 py-24 text-center">
        <div class="inline-flex items-center gap-2 bg-slate-800/60 border border-slate-700 rounded-full px-4 py-1.5 text-xs text-slate-400 mb-8">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
          실시간 정치 성향 커뮤니티
        </div>

        <h1 class="text-5xl md:text-7xl font-black text-white mb-6 leading-tight tracking-tight">
          진영을<br />
          <span class="bg-gradient-to-r from-red-400 via-violet-400 to-blue-400 bg-clip-text text-transparent">
            선택하라
          </span>
        </h1>

        <p class="text-slate-400 text-lg md:text-xl max-w-2xl mx-auto mb-10 leading-relaxed">
          당신의 정치 성향을 진단하고, 같은 생각을 가진 사람들과 소통하세요.<br />
          전쟁터에서 다른 진영과 치열하게 토론하세요.
        </p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <template v-if="!user">
            <Link href="/political-test" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-500 text-white font-bold px-8 py-4 rounded-xl text-lg transition-all shadow-lg shadow-violet-500/20 hover:shadow-violet-500/40 hover:-translate-y-0.5">
              <span>🧭</span> 성향 테스트 시작
            </Link>
            <Link href="/register" class="inline-flex items-center gap-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white font-semibold px-8 py-4 rounded-xl text-lg transition-all">
              <span>👤</span> 회원가입
            </Link>
          </template>
          <template v-else>
            <Link href="/boards" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-500 text-white font-bold px-8 py-4 rounded-xl text-lg transition-all shadow-lg shadow-violet-500/20 hover:-translate-y-0.5">
              <span>🏠</span> 커뮤니티 입장
            </Link>
          </template>
        </div>
      </div>
    </section>

    <!-- Faction Cards -->
    <section class="max-w-7xl mx-auto px-4 pb-20">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-16">
        <div
          v-for="f in factions"
          :key="f.type"
          :class="['rounded-2xl p-6 border transition-all hover:-translate-y-1 hover:shadow-lg', f.bgClass, f.borderClass]"
          :style="{ boxShadow: `0 0 0 0 ${f.color}` }"
        >
          <div class="text-4xl mb-4">{{ f.emoji }}</div>
          <h3 :class="['text-2xl font-black mb-2', f.textClass]">{{ f.label }}</h3>
          <p class="text-slate-400 text-sm leading-relaxed mb-4">{{ f.description }}</p>
          <div class="flex flex-wrap gap-2 mb-5">
            <span
              v-for="kw in f.keywords"
              :key="kw"
              :class="['text-xs px-2 py-0.5 rounded-full border', f.textClass, f.borderClass, 'bg-slate-900/50']"
            >{{ kw }}</span>
          </div>
          <!-- Live score -->
          <div v-if="scoreMap[f.type]" class="pt-4 border-t border-slate-700/50">
            <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
              <span>실시간 영향력 점수</span>
              <span :class="['font-bold text-sm', f.textClass]">{{ scoreMap[f.type].normalized_score?.toFixed(1) ?? '-' }}</span>
            </div>
            <div class="h-1.5 bg-slate-800 rounded-full overflow-hidden">
              <div
                :class="['h-full rounded-full transition-all', f.type === 'conservative' ? 'bg-red-500' : f.type === 'moderate' ? 'bg-violet-500' : 'bg-blue-500']"
                :style="{ width: Math.min(100, Math.max(0, scoreMap[f.type].normalized_score ?? 0)) + '%' }"
              ></div>
            </div>
          </div>
        </div>
      </div>

      <!-- How it works -->
      <div class="text-center mb-12">
        <h2 class="text-3xl font-black text-white mb-12">어떻게 시작하나요?</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="flex flex-col items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-violet-500/20 border border-violet-500/30 flex items-center justify-center text-2xl">🧭</div>
            <h4 class="font-bold text-white">1. 성향 테스트</h4>
            <p class="text-slate-400 text-sm">간단한 설문으로 당신의 정치 성향을 진단합니다.</p>
          </div>
          <div class="flex flex-col items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-violet-500/20 border border-violet-500/30 flex items-center justify-center text-2xl">🏠</div>
            <h4 class="font-bold text-white">2. 아지트 입장</h4>
            <p class="text-slate-400 text-sm">같은 진영의 사람들과 자유롭게 소통합니다.</p>
          </div>
          <div class="flex flex-col items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-violet-500/20 border border-violet-500/30 flex items-center justify-center text-2xl">⚔️</div>
            <h4 class="font-bold text-white">3. 전쟁터 토론</h4>
            <p class="text-slate-400 text-sm">다른 진영과 치열한 토론으로 영향력을 키웁니다.</p>
          </div>
        </div>
      </div>

      <!-- CTA -->
      <div class="text-center bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 border border-slate-700 rounded-2xl p-12">
        <h2 class="text-3xl font-black text-white mb-4">지금 시작하세요</h2>
        <p class="text-slate-400 mb-8">성향 테스트로 나의 진영을 확인하고 커뮤니티에 참여하세요.</p>
        <Link href="/register" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-500 text-white font-bold px-10 py-4 rounded-xl text-lg transition-all shadow-lg shadow-violet-500/20 hover:-translate-y-0.5">
          무료로 시작하기 →
        </Link>
      </div>
    </section>
  </div>
</template>
