<script setup>
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const user = computed(() => page.props.auth?.user)
const scores = computed(() => page.props.realtimeScores ?? [])
const flash = computed(() => page.props.flash ?? {})

const profileOpen = ref(false)

const factionClass = (type) => ({
  conservative: 'text-red-400',
  moderate: 'text-violet-400',
  progressive: 'text-blue-400',
}[type] ?? 'text-slate-400')

const factionBg = (type) => ({
  conservative: 'bg-red-500',
  moderate: 'bg-violet-500',
  progressive: 'bg-blue-500',
}[type] ?? 'bg-slate-500')
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-slate-100" style="font-family: 'Noto Sans KR', sans-serif;">
    <!-- ScoreTicker -->
    <div class="bg-slate-950 border-b border-slate-800 py-1.5">
      <div class="max-w-7xl mx-auto px-4 flex items-center justify-center gap-8 flex-wrap">
        <span class="flex items-center gap-1.5 text-xs">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
          <span class="text-emerald-400 font-semibold tracking-widest">LIVE</span>
        </span>
        <div v-for="s in scores" :key="s.faction_type" class="flex items-center gap-2">
          <span :class="['w-2 h-2 rounded-full', factionBg(s.faction_type)]"></span>
          <span class="text-slate-400 text-xs">{{ s.label }}</span>
          <span :class="['text-sm font-bold tabular-nums', factionClass(s.faction_type)]">
            {{ s.normalized_score?.toFixed(1) ?? '-' }}
          </span>
        </div>
        <div v-if="scores.length === 0" class="text-slate-600 text-xs">점수 데이터 로딩 중...</div>
      </div>
    </div>

    <!-- Navbar -->
    <nav class="bg-slate-900 border-b border-slate-800 sticky top-0 z-40 shadow-lg">
      <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-between">
        <!-- Logo -->
        <Link href="/" class="text-xl font-black tracking-tight text-white hover:text-violet-400 transition-colors">
          POLIT<span class="text-violet-400">.</span>
        </Link>

        <!-- Nav Links -->
        <div class="flex items-center gap-6">
          <Link href="/boards" class="text-slate-300 hover:text-white text-sm transition-colors font-medium">
            커뮤니티
          </Link>
          <Link href="/stats" class="text-slate-300 hover:text-white text-sm transition-colors font-medium">
            통계
          </Link>

          <!-- Authenticated -->
          <template v-if="user">
            <span :class="['text-xs px-2.5 py-1 rounded-full font-semibold text-white', factionBg(user.political_type)]">
              {{ user.faction_emoji }} {{ user.faction_label }}
            </span>
            <div class="relative">
              <button
                @click="profileOpen = !profileOpen"
                class="flex items-center gap-2 text-slate-300 hover:text-white text-sm transition-colors"
              >
                <span class="w-7 h-7 rounded-full bg-slate-700 flex items-center justify-center text-xs font-bold">
                  {{ user.name?.[0]?.toUpperCase() ?? 'U' }}
                </span>
                <span>{{ user.name }}</span>
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
              <div
                v-if="profileOpen"
                class="absolute right-0 mt-2 w-48 bg-slate-800 border border-slate-700 rounded-xl shadow-xl overflow-hidden"
              >
                <div class="px-4 py-3 border-b border-slate-700">
                  <p class="text-xs text-slate-400">로그인 중</p>
                  <p class="text-sm font-semibold text-white truncate">{{ user.email }}</p>
                </div>
                <Link href="/profile" class="block px-4 py-2.5 text-sm text-slate-300 hover:bg-slate-700 hover:text-white transition-colors">
                  프로필 설정
                </Link>
                <Link href="/logout" method="post" as="button" class="w-full text-left px-4 py-2.5 text-sm text-red-400 hover:bg-slate-700 transition-colors">
                  로그아웃
                </Link>
              </div>
            </div>
          </template>

          <!-- Guest -->
          <template v-else>
            <Link href="/login" class="text-slate-300 hover:text-white text-sm transition-colors font-medium">
              로그인
            </Link>
            <Link href="/register" class="bg-violet-600 hover:bg-violet-500 text-white text-sm px-4 py-1.5 rounded-lg transition-colors font-medium">
              회원가입
            </Link>
          </template>
        </div>
      </div>
    </nav>

    <!-- Flash Messages -->
    <transition name="flash">
      <div v-if="flash.success" class="bg-emerald-900/50 border-b border-emerald-700 text-emerald-300 text-sm px-4 py-3 text-center">
        <span class="mr-2">✓</span>{{ flash.success }}
      </div>
    </transition>
    <transition name="flash">
      <div v-if="flash.error" class="bg-red-900/50 border-b border-red-700 text-red-300 text-sm px-4 py-3 text-center">
        <span class="mr-2">✕</span>{{ flash.error }}
      </div>
    </transition>

    <!-- Main Content -->
    <main>
      <slot />
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800 mt-16 py-8">
      <div class="max-w-7xl mx-auto px-4 text-center text-slate-600 text-xs">
        <p class="font-black text-slate-500 mb-1 tracking-tight">POLIT<span class="text-violet-500">.</span></p>
        <p>© 2026 Polit. 정치 성향 커뮤니티 플랫폼.</p>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.flash-enter-active, .flash-leave-active {
  transition: all 0.3s ease;
}
.flash-enter-from, .flash-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
