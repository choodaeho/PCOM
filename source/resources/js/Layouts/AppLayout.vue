<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'
import NotificationDropdown from '@/Components/NotificationDropdown.vue'

const page   = usePage()
const user   = computed(() => page.props.auth?.user)
const scores = computed(() => page.props.realtimeScores ?? [])
const flash  = computed(() => page.props.flash ?? {})

// 플래시 메시지 자동 닫기 (3초)
const showSuccess = ref(false)
const showError   = ref(false)
let successTimer  = null
let errorTimer    = null

watch(() => flash.value.success, (val) => {
  if (val) {
    showSuccess.value = true
    clearTimeout(successTimer)
    successTimer = setTimeout(() => { showSuccess.value = false }, 3000)
  } else {
    showSuccess.value = false
  }
}, { immediate: true })

watch(() => flash.value.error, (val) => {
  if (val) {
    showError.value = true
    clearTimeout(errorTimer)
    errorTimer = setTimeout(() => { showError.value = false }, 3000)
  } else {
    showError.value = false
  }
}, { immediate: true })

const profileOpen    = ref(false)
const communityOpen  = ref(false)
const statsOpen      = ref(false)
const mobileMenuOpen = ref(false)
const notifOpen      = ref(false)

// 테마
const { theme, isDark, setTheme } = useTheme()

const themeOptions = [
  { value: 'light', label: '라이트', icon: 'sun'  },
  { value: 'dark',  label: '다크',   icon: 'moon' },
  { value: 'auto',  label: '자동',   icon: 'auto' },
]

// 데스크탑: 드롭다운 외부 클릭 시 닫기
const handleOutsideClick = () => {
  communityOpen.value = false
  statsOpen.value     = false
  profileOpen.value   = false
  notifOpen.value     = false
}
onMounted(()       => document.addEventListener('click', handleOutsideClick))
onBeforeUnmount(() => document.removeEventListener('click', handleOutsideClick))

// 놀이터 게시판 목록
const playgrounds = [
  ['play-humor',         '유머/짤방'],
  ['play-game',          '게임'],
  ['play-sports',        '스포츠'],
  ['play-entertainment', '방송/연예'],
  ['play-stock',         '주식/코인'],
  ['play-it',            'IT/테크'],
  ['play-food',          '먹방/맛집'],
  ['play-free',          '자유게시판'],
]

const azitSlug = computed(() => {
  const faction = user.value?.political_type
  if (!faction) return null
  return {
    conservative: 'conservative-azit',
    moderate:     'moderate-azit',
    progressive:  'progressive-azit',
  }[faction] ?? null
})

const factionEmoji = (type) =>
  ({ conservative: '🦅', moderate: '⚖️', progressive: '🕊️' }[type] ?? '')

const factionClass = (type) => ({
  conservative: 'text-red-500',
  moderate:     'text-violet-500',
  progressive:  'text-blue-500',
}[type] ?? 'text-slate-400')

const factionBg = (type) => ({
  conservative: 'bg-red-500',
  moderate:     'bg-violet-500',
  progressive:  'bg-blue-500',
}[type] ?? 'bg-slate-500')
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100" style="font-family: 'Noto Sans KR', sans-serif;">

    <!-- ScoreTicker -->
    <div class="bg-white dark:bg-slate-950 border-b border-gray-200 dark:border-slate-800 py-1.5">
      <div class="max-w-7xl mx-auto px-4 flex items-center justify-center gap-3 sm:gap-5 flex-wrap">
        <span class="flex items-center gap-1.5 text-xs">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
          <span class="text-emerald-500 dark:text-emerald-400 font-semibold">LIVE</span>
        </span>
        <div v-for="s in scores" :key="s.faction_type" class="flex items-center gap-1.5 sm:gap-2">
          <span :class="['w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full', factionBg(s.faction_type)]"></span>
          <span class="text-slate-500 dark:text-slate-400 text-xs">{{ s.label }}</span>
          <span :class="['text-xs sm:text-sm font-bold tabular-nums', factionClass(s.faction_type)]">
            {{ s.normalized_score != null ? Math.round(s.normalized_score * 100) : '-' }}
          </span>
        </div>
        <div v-if="scores.length === 0" class="text-slate-400 dark:text-slate-600 text-xs">로딩 중...</div>
      </div>
    </div>

    <!-- ── Navbar ────────────────────────────────────────────── -->
    <nav class="bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800 sticky top-0 z-40 shadow-sm dark:shadow-lg">
      <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-between">

        <!-- Logo -->
        <Link href="/" class="flex items-center gap-2 flex-shrink-0 group">
          <img src="/favicon.svg" alt="폴릿" class="h-7 w-7">
          <span class="text-xl font-black tracking-tight text-slate-900 dark:text-white group-hover:text-violet-500 dark:group-hover:text-violet-400 transition-colors">
            POLIT<span class="text-violet-500 dark:text-violet-400">.</span>
          </span>
        </Link>

        <!-- ── 데스크탑 Nav (lg 이상) ─────────────────────────── -->
        <div class="hidden lg:flex items-center gap-6">

          <!-- 커뮤니티 메가 드롭다운 -->
          <div
            class="relative"
            @mouseenter="communityOpen = true"
            @mouseleave="communityOpen = false"
            @click.stop
          >
            <button
              @click="communityOpen = !communityOpen"
              :class="[
                'flex items-center gap-1 text-sm font-medium transition-colors',
                communityOpen
                  ? 'text-slate-900 dark:text-white'
                  : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white'
              ]"
            >
              커뮤니티
              <svg class="w-3 h-3 transition-transform duration-200" :class="{ 'rotate-180': communityOpen }"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>

            <transition name="dropdown">
              <div
                v-if="communityOpen"
                class="absolute left-0 top-full mt-1 w-[480px] bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden z-50"
              >
                <div class="grid grid-cols-2 divide-x divide-gray-200 dark:divide-slate-800">
                  <!-- Left: 아지트 + 전쟁터 -->
                  <div class="p-5">
                    <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-3">🏠 아지트</p>
                    <Link v-if="azitSlug" :href="`/boards/${azitSlug}`" @click="communityOpen = false"
                      class="flex items-center gap-2 px-3 py-2.5 rounded-xl text-sm font-semibold bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 text-slate-900 dark:text-white mb-5 transition-colors">
                      <span>{{ factionEmoji(user.political_type) }}</span>
                      나의 아지트 입장하기
                    </Link>
                    <div v-else-if="!user" class="mb-5 px-1 text-xs text-slate-400 dark:text-slate-500">
                      <Link href="/login" @click="communityOpen = false" class="text-violet-500 dark:text-violet-400 hover:text-violet-600 dark:hover:text-violet-300">로그인</Link> 후 이용 가능
                    </div>
                    <div v-else class="mb-5 px-1 text-xs text-slate-400 dark:text-slate-500">
                      <Link href="/political-test" @click="communityOpen = false" class="text-violet-500 dark:text-violet-400 hover:text-violet-600 dark:hover:text-violet-300">성향 테스트</Link> 후 이용 가능
                    </div>

                    <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2.5">⚔️ 전쟁터</p>
                    <div class="space-y-0.5">
                      <Link href="/boards/battle-politics" @click="communityOpen = false"
                        class="flex items-center px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">정치 전쟁터</Link>
                      <Link href="/boards/battle-economy" @click="communityOpen = false"
                        class="flex items-center px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">경제 전쟁터</Link>
                      <Link href="/boards/battle-society" @click="communityOpen = false"
                        class="flex items-center px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">사회 전쟁터</Link>
                    </div>
                  </div>

                  <!-- Right: 놀이터 + 툴박스 -->
                  <div class="p-5">
                    <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-3">🎡 놀이터</p>
                    <div class="grid grid-cols-2 gap-0.5 mb-5">
                      <Link v-for="[slug, label] in playgrounds" :key="slug"
                        :href="`/boards/${slug}`" @click="communityOpen = false"
                        class="px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors truncate">
                        {{ label }}
                      </Link>
                    </div>
                    <div class="pt-4 border-t border-gray-200 dark:border-slate-800">
                      <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2.5">🧰 툴박스</p>
                      <div class="space-y-0.5">
                        <Link href="/tools#lotto" @click="communityOpen = false"
                          class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
                          🎰 로또번호생성기
                        </Link>
                        <Link href="/tools#fortune" @click="communityOpen = false"
                          class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
                          🔮 오늘의 운세
                        </Link>
                        <Link href="/tools#worldcup" @click="communityOpen = false"
                          class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
                          💘 이상형 월드컵
                        </Link>
                        <Link href="/tools#quiz" @click="communityOpen = false"
                          class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
                          📰 시사 퀴즈
                        </Link>
                        <Link href="/tools#politician" @click="communityOpen = false"
                          class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
                          🧬 닮은꼴 정치인
                        </Link>
                        <Link href="/tools#card" @click="communityOpen = false"
                          class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
                          📊 성향 공유카드
                        </Link>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </transition>
          </div>

          <!-- 통계 드롭다운 -->
          <div
            class="relative"
            @mouseenter="statsOpen = true"
            @mouseleave="statsOpen = false"
            @click.stop
          >
            <button
              @click="statsOpen = !statsOpen"
              :class="[
                'flex items-center gap-1 text-sm font-medium transition-colors',
                statsOpen
                  ? 'text-slate-900 dark:text-white'
                  : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white'
              ]"
            >
              통계
              <svg class="w-3 h-3 transition-transform duration-200" :class="{ 'rotate-180': statsOpen }"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>

            <transition name="dropdown">
              <div
                v-if="statsOpen"
                class="absolute left-0 top-full mt-1 w-44 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden z-50"
              >
                <div class="p-2 space-y-0.5">
                  <Link href="/stats" @click="statsOpen = false"
                    class="flex items-center gap-2 px-3 py-2.5 rounded-xl text-sm text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
                    📊 진영 통계
                  </Link>
                  <Link v-if="user" href="/stats/ranking" @click="statsOpen = false"
                    class="flex items-center gap-2 px-3 py-2.5 rounded-xl text-sm text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
                    🏆 사용자 랭킹
                  </Link>
                </div>
              </div>
            </transition>
          </div>

          <!-- 🔔 알림 벨 (데스크탑, 로그인 유저만) -->
          <div v-if="user" class="relative" @click.stop>
            <NotificationDropdown
              :open="notifOpen"
              @update:open="notifOpen = $event"
            />
          </div>

          <!-- 🌙 테마 토글 (데스크탑) -->
          <div class="flex items-center gap-0.5 bg-gray-100 dark:bg-slate-800 rounded-lg p-0.5">
            <button
              v-for="opt in themeOptions"
              :key="opt.value"
              @click="setTheme(opt.value)"
              :title="opt.label + ' 모드'"
              :class="[
                'w-7 h-7 flex items-center justify-center rounded-md text-xs transition-all duration-200',
                theme === opt.value
                  ? 'bg-white dark:bg-slate-600 shadow text-slate-900 dark:text-white'
                  : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'
              ]"
            >
              <!-- 라이트: 태양 -->
              <svg v-if="opt.icon === 'sun'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
              </svg>
              <!-- 다크: 달 -->
              <svg v-else-if="opt.icon === 'moon'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
              </svg>
              <!-- 자동: A 텍스트 -->
              <span v-else class="font-bold text-[11px] leading-none">A</span>
            </button>
          </div>

          <!-- 로그인 유저 -->
          <template v-if="user">
            <div class="relative">
              <button @click.stop="profileOpen = !profileOpen"
                class="flex items-center gap-2 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white text-sm transition-colors">
                <span :class="['w-6 h-6 rounded-full flex items-center justify-center text-sm flex-shrink-0', factionBg(user.political_type)]">
                  {{ user.level_emoji ?? '🌱' }}
                </span>
                <span class="font-medium max-w-[120px] truncate">{{ user.nickname }}</span>
                <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
              </button>
              <div v-if="profileOpen"
                class="absolute right-0 mt-2 w-60 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl shadow-xl overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-slate-700">
                  <div class="flex items-center gap-2 mb-1.5">
                    <span class="text-lg">{{ user.level_emoji ?? '🌱' }}</span>
                    <div class="min-w-0">
                      <p class="text-sm font-bold text-slate-900 dark:text-white truncate leading-tight">{{ user.nickname }}</p>
                      <p class="text-xs text-slate-500 dark:text-slate-400">Lv.{{ user.level ?? 1 }} {{ user.level_name }}</p>
                    </div>
                  </div>
                  <span :class="['inline-flex items-center gap-1 text-xs px-2.5 py-0.5 rounded-full font-semibold text-white mb-2', factionBg(user.political_type)]">
                    {{ factionEmoji(user.political_type) }} {{ user.faction_label }}
                  </span>
                  <!-- XP 진행 바 -->
                  <div v-if="user.next_level_xp != null">
                    <div class="flex justify-between text-[10px] text-slate-400 dark:text-slate-500 mb-1">
                      <span>{{ user.experience_points ?? 0 }} XP</span>
                      <span>{{ user.next_level_xp }} XP</span>
                    </div>
                    <div class="w-full h-1.5 bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden">
                      <div
                        class="h-full bg-violet-500 rounded-full transition-all duration-300"
                        :style="{
                          width: `${Math.min(100, Math.round(
                            ((user.experience_points - user.current_level_xp) /
                            (user.next_level_xp - user.current_level_xp)) * 100
                          ))}%`
                        }"
                      />
                    </div>
                  </div>
                  <div v-else class="text-[10px] font-bold text-amber-500 text-center mt-1">
                    👑 MAX LEVEL
                  </div>
                </div>
                <Link href="/profile" class="block px-4 py-2.5 text-sm text-slate-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white transition-colors">
                  프로필 설정
                </Link>
                <Link href="/logout" method="post" as="button"
                  class="w-full text-left px-4 py-2.5 text-sm text-red-500 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
                  로그아웃
                </Link>
              </div>
            </div>
          </template>

          <!-- 게스트 -->
          <template v-else>
            <Link href="/login" class="text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white text-sm font-medium transition-colors">로그인</Link>
            <Link href="/register" class="bg-violet-600 hover:bg-violet-500 text-white text-sm px-4 py-1.5 rounded-lg font-medium transition-colors">
              회원가입
            </Link>
          </template>
        </div>

        <!-- ── 모바일 우측 (lg 미만) ─────────────────────────── -->
        <div class="flex lg:hidden items-center gap-3">
          <!-- 로그인 상태 표시: 레벨 이모지 + 닉네임 -->
          <template v-if="user">
            <span class="flex items-center gap-1 text-sm text-slate-700 dark:text-slate-200 font-medium min-w-0">
              <span class="text-base leading-none flex-shrink-0">{{ user.level_emoji ?? '🌱' }}</span>
              <span class="max-w-[80px] truncate">{{ user.nickname }}</span>
            </span>
          </template>
          <template v-else>
            <Link href="/login" class="text-slate-600 dark:text-slate-300 text-sm font-medium">로그인</Link>
          </template>

          <!-- 🔔 알림 벨 (모바일, 로그인 유저만) -->
          <div v-if="user" class="relative" @click.stop>
            <NotificationDropdown
              :open="notifOpen"
              @update:open="notifOpen = $event"
            />
          </div>

          <!-- 햄버거 버튼 -->
          <button
            @click.stop="mobileMenuOpen = true"
            class="w-9 h-9 rounded-lg flex items-center justify-center text-slate-500 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors"
            aria-label="메뉴 열기"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>
        </div>

      </div>
    </nav>

    <!-- ── 모바일 사이드 드로어 ──────────────────────────────── -->
    <transition name="drawer-fade">
      <div v-if="mobileMenuOpen" class="fixed inset-0 z-50 lg:hidden flex">
        <!-- 백드롭 -->
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="mobileMenuOpen = false" />

        <!-- 드로어 본체 -->
        <div class="relative w-72 max-w-[85vw] h-full bg-white dark:bg-slate-900 border-r border-gray-200 dark:border-slate-800 flex flex-col shadow-2xl overflow-hidden">

          <!-- 드로어 헤더 -->
          <div class="flex items-center justify-between px-5 h-14 border-b border-gray-200 dark:border-slate-800 flex-shrink-0">
            <Link href="/" @click="mobileMenuOpen = false" class="flex items-center gap-2">
              <img src="/favicon.svg" alt="폴릿" class="h-6 w-6">
              <span class="text-lg font-black text-slate-900 dark:text-white">
                POLIT<span class="text-violet-500 dark:text-violet-400">.</span>
              </span>
            </Link>
            <button @click="mobileMenuOpen = false"
              class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- 로그인 유저 정보 -->
          <div v-if="user" class="px-5 py-3.5 border-b border-gray-200 dark:border-slate-800 flex-shrink-0 bg-gray-50 dark:bg-slate-800/40">
            <div class="flex items-center gap-3 mb-2">
              <span :class="['w-9 h-9 rounded-full flex items-center justify-center text-xl flex-shrink-0', factionBg(user.political_type)]">
                {{ user.level_emoji ?? '🌱' }}
              </span>
              <div class="min-w-0">
                <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ user.nickname }}</p>
                <div class="flex items-center gap-1.5">
                  <span :class="['text-xs px-2 py-0.5 rounded-full font-semibold text-white', factionBg(user.political_type)]">
                    {{ user.faction_emoji }} {{ user.faction_label }}
                  </span>
                  <span class="text-xs text-slate-500 dark:text-slate-400">Lv.{{ user.level ?? 1 }}</span>
                </div>
              </div>
            </div>
            <!-- XP 진행 바 (모바일) -->
            <div v-if="user.next_level_xp != null">
              <div class="flex justify-between text-[10px] text-slate-400 dark:text-slate-500 mb-1">
                <span>{{ user.level_name }}</span>
                <span>{{ user.experience_points ?? 0 }} / {{ user.next_level_xp }} XP</span>
              </div>
              <div class="w-full h-1.5 bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden">
                <div
                  class="h-full bg-violet-500 rounded-full transition-all duration-300"
                  :style="{
                    width: `${Math.min(100, Math.round(
                      ((user.experience_points - user.current_level_xp) /
                      (user.next_level_xp - user.current_level_xp)) * 100
                    ))}%`
                  }"
                />
              </div>
            </div>
            <div v-else class="text-[10px] font-bold text-amber-500 text-center mt-1">
              👑 MAX LEVEL
            </div>
          </div>

          <!-- 스크롤 가능한 메뉴 본문 -->
          <div class="flex-1 overflow-y-auto py-2">

            <!-- 🏠 아지트 -->
            <div class="px-5 pt-4 pb-1">
              <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">🏠 아지트</p>
            </div>
            <Link v-if="azitSlug" :href="`/boards/${azitSlug}`" @click="mobileMenuOpen = false"
              class="flex items-center gap-3 px-5 py-3 text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors text-sm font-medium">
              <span class="text-base">{{ factionEmoji(user.political_type) }}</span>
              나의 아지트
            </Link>
            <div v-else-if="!user" class="px-5 py-3 text-sm text-slate-400 dark:text-slate-500">
              <Link href="/login" @click="mobileMenuOpen = false" class="text-violet-500 dark:text-violet-400 hover:text-violet-600 dark:hover:text-violet-300">로그인</Link> 후 이용 가능
            </div>
            <div v-else class="px-5 py-3 text-sm text-slate-400 dark:text-slate-500">
              <Link href="/political-test" @click="mobileMenuOpen = false" class="text-violet-500 dark:text-violet-400 hover:text-violet-600 dark:hover:text-violet-300">성향 테스트</Link> 후 이용 가능
            </div>

            <!-- ⚔️ 전쟁터 -->
            <div class="px-5 pt-5 pb-1">
              <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">⚔️ 전쟁터</p>
            </div>
            <Link href="/boards/battle-politics" @click="mobileMenuOpen = false"
              class="flex items-center px-5 py-3 text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors text-sm">정치 전쟁터</Link>
            <Link href="/boards/battle-economy" @click="mobileMenuOpen = false"
              class="flex items-center px-5 py-3 text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors text-sm">경제 전쟁터</Link>
            <Link href="/boards/battle-society" @click="mobileMenuOpen = false"
              class="flex items-center px-5 py-3 text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors text-sm">사회 전쟁터</Link>

            <!-- 🎡 놀이터 -->
            <div class="px-5 pt-5 pb-1">
              <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">🎡 놀이터</p>
            </div>
            <Link v-for="[slug, label] in playgrounds" :key="slug"
              :href="`/boards/${slug}`" @click="mobileMenuOpen = false"
              class="flex items-center px-5 py-3 text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors text-sm">
              {{ label }}
            </Link>

            <!-- 🧰 툴박스 -->
            <div class="px-5 pt-5 pb-1">
              <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">🧰 툴박스</p>
            </div>
            <Link href="/tools#lotto" @click="mobileMenuOpen = false"
              class="flex items-center gap-2 px-5 py-3 text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors text-sm">
              🎰 로또번호생성기
            </Link>
            <Link href="/tools#fortune" @click="mobileMenuOpen = false"
              class="flex items-center gap-2 px-5 py-3 text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors text-sm">
              🔮 오늘의 운세
            </Link>
            <Link href="/tools#worldcup" @click="mobileMenuOpen = false"
              class="flex items-center gap-2 px-5 py-3 text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors text-sm">
              💘 이상형 월드컵
            </Link>
            <Link href="/tools#quiz" @click="mobileMenuOpen = false"
              class="flex items-center gap-2 px-5 py-3 text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors text-sm">
              📰 시사 퀴즈
            </Link>
            <Link href="/tools#politician" @click="mobileMenuOpen = false"
              class="flex items-center gap-2 px-5 py-3 text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors text-sm">
              🧬 닮은꼴 정치인
            </Link>
            <Link href="/tools#card" @click="mobileMenuOpen = false"
              class="flex items-center gap-2 px-5 py-3 text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors text-sm">
              📊 성향 공유카드
            </Link>

            <div class="mx-5 my-4 border-t border-gray-200 dark:border-slate-800"></div>

            <!-- 통계 -->
            <div class="px-5 pt-5 pb-1">
              <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">📊 통계</p>
            </div>
            <Link href="/stats" @click="mobileMenuOpen = false"
              class="flex items-center gap-2 px-5 py-3 text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors text-sm">
              📈 진영 통계
            </Link>
            <Link v-if="user" href="/stats/ranking" @click="mobileMenuOpen = false"
              class="flex items-center gap-2 px-5 py-3 text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors text-sm">
              🏆 사용자 랭킹
            </Link>
          </div>

          <!-- 드로어 푸터: 테마 토글 + 인증 버튼 -->
          <div class="flex-shrink-0 border-t border-gray-200 dark:border-slate-800 p-4 space-y-3">

            <!-- 🌙 테마 토글 (모바일) -->
            <div class="flex items-center gap-2">
              <span class="text-xs text-slate-500 dark:text-slate-400 font-medium flex-shrink-0">화면 모드</span>
              <div class="flex items-center gap-0.5 bg-gray-100 dark:bg-slate-800 rounded-lg p-0.5 ml-auto">
                <button
                  v-for="opt in themeOptions"
                  :key="opt.value"
                  @click="setTheme(opt.value)"
                  :title="opt.label"
                  :class="[
                    'h-7 px-2 flex items-center justify-center rounded-md text-xs font-medium transition-all duration-200',
                    theme === opt.value
                      ? 'bg-white dark:bg-slate-600 shadow text-slate-900 dark:text-white'
                      : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'
                  ]"
                >
                  <svg v-if="opt.icon === 'sun'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                  </svg>
                  <svg v-else-if="opt.icon === 'moon'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                  </svg>
                  <span v-else class="font-bold text-[11px] leading-none">A</span>
                </button>
              </div>
            </div>

            <template v-if="user">
              <Link href="/profile" @click="mobileMenuOpen = false"
                class="flex items-center gap-2 w-full px-4 py-2.5 rounded-xl text-sm text-slate-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
                ⚙️ 프로필 설정
              </Link>
              <Link href="/logout" method="post" as="button" @click="mobileMenuOpen = false"
                class="flex items-center gap-2 w-full px-4 py-2.5 rounded-xl text-sm text-red-500 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
                로그아웃
              </Link>
            </template>
            <template v-else>
              <Link href="/login" @click="mobileMenuOpen = false"
                class="flex items-center justify-center w-full px-4 py-2.5 rounded-xl text-sm bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 text-slate-900 dark:text-white font-medium transition-colors">
                로그인
              </Link>
              <Link href="/register" @click="mobileMenuOpen = false"
                class="flex items-center justify-center w-full px-4 py-2.5 rounded-xl text-sm bg-violet-600 hover:bg-violet-500 text-white font-semibold transition-colors">
                회원가입
              </Link>
            </template>
          </div>
        </div>
      </div>
    </transition>

    <!-- Flash Messages -->
    <transition name="flash">
      <div v-if="showSuccess" class="bg-emerald-50 dark:bg-emerald-900/50 border-b border-emerald-200 dark:border-emerald-700 text-emerald-700 dark:text-emerald-300 text-sm px-4 py-3 text-center">
        <span class="mr-2">✓</span>{{ flash.success }}
      </div>
    </transition>
    <transition name="flash">
      <div v-if="showError" class="bg-red-50 dark:bg-red-900/50 border-b border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 text-sm px-4 py-3 text-center">
        <span class="mr-2">✕</span>{{ flash.error }}
      </div>
    </transition>

    <!-- Main Content -->
    <main>
      <slot />
    </main>

    <!-- Footer -->
    <footer class="border-t border-gray-200 dark:border-slate-800 mt-16 py-10">
      <div class="max-w-7xl mx-auto px-4">
        <!-- 로고 -->
        <div class="flex items-center justify-center gap-3 mb-5">
          <img src="/favicon.svg" alt="" class="h-10 w-10 opacity-60 dark:opacity-50">
          <span class="font-black text-slate-600 dark:text-slate-400 text-3xl tracking-tight">
            POLIT<span class="text-violet-500">.</span>
          </span>
        </div>

        <!-- 법적 링크 1행 -->
        <div class="flex items-center justify-center flex-wrap gap-x-4 gap-y-1.5 mb-2 text-xs">
          <Link
            href="/legal/terms"
            class="text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 transition-colors"
          >
            이용약관
          </Link>
          <span class="text-gray-300 dark:text-slate-700">|</span>
          <Link
            href="/legal/privacy"
            class="font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors"
          >
            개인정보처리방침
          </Link>
          <span class="text-gray-300 dark:text-slate-700">|</span>
          <Link
            href="/legal/youth-protection"
            class="text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 transition-colors"
          >
            청소년보호정책
          </Link>
          <span class="text-gray-300 dark:text-slate-700">|</span>
          <Link
            href="/legal/deletion-request"
            class="text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 transition-colors"
          >
            삭제요청
          </Link>
        </div>

        <!-- 법적 링크 2행: 담당자 이메일 -->
        <div class="flex items-center justify-center gap-4 mb-4 text-[11px] text-slate-300 dark:text-slate-600">
          <span>개인정보보호팀 <a href="mailto:privacy@polit.kr" class="hover:text-slate-500 dark:hover:text-slate-400 transition-colors">privacy@polit.kr</a></span>
          <span>|</span>
          <span>청소년보호 <a href="mailto:youth@polit.kr" class="hover:text-slate-500 dark:hover:text-slate-400 transition-colors">youth@polit.kr</a></span>
        </div>

        <!-- 카피라이트 -->
        <p class="text-center text-[11px] text-slate-300 dark:text-slate-700">
          © 2026 Polit. All rights reserved.
        </p>
      </div>
    </footer>
  </div>
</template>

<style>
.dropdown-enter-active,
.dropdown-leave-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.dropdown-enter-from,
.dropdown-leave-to   { opacity: 0; transform: translateY(-6px); }

.drawer-fade-enter-active,
.drawer-fade-leave-active { transition: opacity 0.25s ease; }
.drawer-fade-enter-from,
.drawer-fade-leave-to   { opacity: 0; }

.flash-enter-active,
.flash-leave-active { transition: opacity 0.3s ease; }
.flash-enter-from,
.flash-leave-to   { opacity: 0; }
</style>
