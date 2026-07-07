<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const page = usePage()
const user            = computed(() => page.props.auth?.user)
const realtimeScores  = computed(() => (page.props.realtimeScores as any[]) ?? [])

const props = defineProps({
  hotPosts:    { type: Array as () => any[], default: () => [] },
  battlePosts: { type: Array as () => any[], default: () => [] },
  playPosts:   { type: Array as () => any[], default: () => [] },
  notices:     { type: Array as () => any[], default: () => [] },
  boards:      { type: Array as () => any[], default: () => [] },
})

// ── 탭 상태 ─────────────────────────────────────────────────────
const activeTab = ref<'battle' | 'play'>('battle')
const tabPosts  = computed(() => activeTab.value === 'battle' ? props.battlePosts : props.playPosts)

// ── 진영 설정 ─────────────────────────────────────────────────────
const factionConfig: Record<string, { label: string; color: string; emoji: string }> = {
  conservative: { label: '보수', color: '#E24B4A', emoji: '🔴' },
  moderate:     { label: '중도', color: '#7F77DD', emoji: '🟣' },
  progressive:  { label: '진보', color: '#378ADD', emoji: '🔵' },
}

// ── 게시판 타입 설정 ──────────────────────────────────────────────
const boardConfig: Record<string, { emoji: string; label: string }> = {
  battle:     { emoji: '⚔️', label: '전쟁터' },
  playground: { emoji: '🎡', label: '놀이터' },
  notice:     { emoji: '📢', label: '공지' },
}

// ── 빠른 바로가기 분류 ────────────────────────────────────────────
const battleBoards    = computed(() => props.boards.filter((b: any) => b.board_type === 'battle'))
const playgroundBoards = computed(() => props.boards.filter((b: any) => b.board_type === 'playground'))

// ── 로그인 유저 진영 정보 ─────────────────────────────────────────
const userFaction  = computed(() => user.value?.political_type ? factionConfig[user.value.political_type] : null)
const azitSlug     = computed(() => user.value?.political_type ? `${user.value.political_type}-azit` : null)

// ── 상대시간 ──────────────────────────────────────────────────────
const timeAgo = (dateStr: string): string => {
  if (!dateStr) return ''
  const diff = Date.now() - new Date(dateStr).getTime()
  const mins = Math.floor(diff / 60_000)
  if (mins < 1)   return '방금'
  if (mins < 60)  return `${mins}분 전`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `${hours}시간 전`
  const days = Math.floor(hours / 24)
  if (days < 7)   return `${days}일 전`
  const d = new Date(dateStr)
  const pad = (n: number) => String(n).padStart(2, '0')
  return `${d.getMonth() + 1}.${pad(d.getDate())}`
}

// ── 조회수 표기 (1000 → 1k) ──────────────────────────────────────
const fmtView = (n: number): string =>
  n >= 10_000 ? (n / 10_000).toFixed(1) + '만'
  : n >= 1_000 ? (n / 1_000).toFixed(1) + 'k'
  : String(n)

// ── 인기글 순위 색상 ──────────────────────────────────────────────
const rankColor = (i: number): string =>
  i === 0 ? 'text-yellow-500 dark:text-yellow-400'
  : i === 1 ? 'text-slate-400 dark:text-slate-500'
  : i === 2 ? 'text-amber-600 dark:text-amber-500'
  : 'text-gray-200 dark:text-slate-800'
</script>

<template>
<Head>
  <title>폴릿 — 보수·중도·진보 정치 커뮤니티</title>
  <meta name="description" content="나의 정치 성향을 진단하고, 보수·중도·진보 진영 아지트와 전쟁터에서 자유롭게 토론하는 정치 커뮤니티" />
  <meta property="og:title" content="폴릿 — 보수·중도·진보 정치 커뮤니티" />
  <meta property="og:description" content="나의 정치 성향을 진단하고, 보수·중도·진보 진영 아지트와 전쟁터에서 자유롭게 토론하는 정치 커뮤니티" />
  <meta property="og:type" content="website" />
</Head>
  <div>
    <!-- ════════════════════════════════════════════════════════
         환영 배너 (비로그인 / 로그인 분기)
         ════════════════════════════════════════════════════════ -->

    <!-- 비로그인 배너 -->
    <div v-if="!user"
      class="bg-gradient-to-r from-violet-600 via-indigo-600 to-violet-600 text-white"
    >
      <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
          <span class="text-2xl flex-shrink-0">🗳️</span>
          <div class="min-w-0">
            <p class="font-bold text-sm sm:text-base leading-snug">정치 성향 커뮤니티 <strong>폴릿</strong>에 오신 것을 환영합니다</p>
            <p class="text-violet-200 text-xs mt-0.5 hidden sm:block">성향 테스트로 나의 진영을 확인하고 커뮤니티에 참여하세요</p>
          </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
          <Link href="/political-test"
            class="text-xs sm:text-sm font-bold bg-white/20 hover:bg-white/30 px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap">
            🧭 테스트
          </Link>
          <Link href="/register"
            class="text-xs sm:text-sm font-bold bg-white text-violet-700 hover:bg-violet-50 px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap">
            회원가입
          </Link>
        </div>
      </div>
    </div>

    <!-- 로그인 유저 퀵 배너 -->
    <div v-else
      class="bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800"
    >
      <div class="max-w-7xl mx-auto px-4 py-2 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2 min-w-0">
          <span v-if="userFaction" class="text-base flex-shrink-0">{{ userFaction.emoji }}</span>
          <span class="text-sm font-medium text-gray-700 dark:text-slate-300 truncate">
            <span v-if="userFaction" :style="{ color: userFaction.color }" class="font-bold">{{ userFaction.label }} </span>
            <span class="font-bold">{{ user.nickname }}</span>님, 오늘도 논쟁해봅시다!
          </span>
        </div>
        <div class="flex items-center gap-1.5 flex-shrink-0">
          <Link v-if="azitSlug" :href="`/boards/${azitSlug}`"
            class="text-xs font-semibold px-2.5 py-1.5 rounded-lg bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 text-gray-600 dark:text-slate-300 transition-colors whitespace-nowrap">
            🏠 아지트
          </Link>
          <Link href="/boards/battle-politics"
            class="text-xs font-semibold px-2.5 py-1.5 rounded-lg bg-violet-100 dark:bg-violet-900/30 hover:bg-violet-200 dark:hover:bg-violet-900/50 text-violet-700 dark:text-violet-300 transition-colors whitespace-nowrap">
            ⚔️ 전쟁터
          </Link>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════════
         메인 컨텐츠
         ════════════════════════════════════════════════════════ -->
    <div class="max-w-7xl mx-auto px-4 py-5">

      <!-- 공지사항 -->
      <div v-if="notices.length" class="mb-4 space-y-1.5">
        <Link
          v-for="notice in notices"
          :key="notice.id"
          :href="`/boards/${notice.board_slug}/posts/${notice.id}`"
          class="flex items-center gap-2 px-3 py-2 bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800/40 rounded-xl hover:bg-violet-100 dark:hover:bg-violet-900/30 transition-colors group"
        >
          <span class="flex-shrink-0 text-[10px] font-bold text-violet-600 dark:text-violet-400 bg-violet-100 dark:bg-violet-900/40 px-1.5 py-0.5 rounded">📢 공지</span>
          <span class="text-sm font-medium text-gray-700 dark:text-slate-300 group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors truncate">
            {{ notice.title }}
          </span>
          <span class="text-xs text-gray-400 dark:text-slate-500 flex-shrink-0 ml-auto">{{ timeAgo(notice.created_at) }}</span>
        </Link>
      </div>

      <!-- ── 2-Column Grid ── -->
      <div class="grid grid-cols-1 lg:grid-cols-[1fr_288px] gap-5">

        <!-- ══════════════════════════════════════════════════
             LEFT: 메인 피드
             ══════════════════════════════════════════════════ -->
        <div class="min-w-0 space-y-4">

          <!-- 🔥 실시간 인기글 ──────────────────────────────── -->
          <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <!-- 헤더 -->
            <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-100 dark:border-slate-800">
              <span class="text-base">🔥</span>
              <h2 class="font-bold text-sm text-gray-800 dark:text-slate-200">실시간 인기글</h2>
              <span class="text-[11px] text-gray-400 dark:text-slate-500 ml-auto">최근 7일 · 추천 순</span>
            </div>

            <!-- 빈 상태 -->
            <div v-if="!hotPosts.length"
              class="py-12 text-center text-sm text-gray-400 dark:text-slate-600">
              아직 인기글이 없습니다.
            </div>

            <!-- 인기글 목록 (순위형 리스트) -->
            <div v-else>
              <Link
                v-for="(post, i) in hotPosts"
                :key="post.id"
                :href="`/boards/${post.board_slug}/posts/${post.id}`"
                class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-800/40 transition-colors border-b border-gray-50 dark:border-slate-800/50 last:border-0 group"
              >
                <!-- 순위 번호 -->
                <span :class="['text-sm font-black w-5 flex-shrink-0 text-center mt-0.5 tabular-nums', rankColor(i)]">
                  {{ String(i + 1).padStart(2, '0') }}
                </span>

                <!-- 본문 -->
                <div class="flex-1 min-w-0">
                  <!-- 제목 행 -->
                  <div class="flex items-start gap-1.5 mb-1">
                    <span
                      v-if="post.board_type && boardConfig[post.board_type]"
                      class="flex-shrink-0 text-[10px] font-semibold px-1.5 py-0.5 rounded bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 leading-none mt-0.5 whitespace-nowrap"
                    >{{ boardConfig[post.board_type].emoji }} {{ post.board_name }}</span>
                    <span class="text-[13px] sm:text-sm font-medium text-gray-800 dark:text-slate-200 group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors leading-snug line-clamp-1">
                      {{ post.title }}
                    </span>
                    <span
                      v-if="post.comment_count > 0"
                      class="flex-shrink-0 text-violet-500 dark:text-violet-400 font-bold text-[13px] sm:text-sm leading-snug"
                    >[{{ post.comment_count }}]</span>
                  </div>

                  <!-- 메타 행 -->
                  <div class="flex items-center gap-1.5 text-[11px] text-gray-400 dark:text-slate-500 flex-wrap">
                    <!-- 진영 + 작성자 -->
                    <span
                      v-if="post.faction && factionConfig[post.faction]"
                      :style="{ color: factionConfig[post.faction].color }"
                      class="font-semibold"
                    >{{ factionConfig[post.faction].emoji }} {{ post.is_anonymous ? '익명' : (post.user?.nickname ?? '?') }}</span>
                    <span v-else>{{ post.is_anonymous ? '익명' : (post.user?.nickname ?? '?') }}</span>

                    <span class="text-gray-200 dark:text-slate-700">·</span>
                    <span>{{ timeAgo(post.created_at) }}</span>

                    <!-- 추천 / 조회 (우측) -->
                    <span class="ml-auto flex items-center gap-2">
                      <span
                        :class="['flex items-center gap-0.5 font-semibold', post.vote_up_count >= 5 ? 'text-orange-500 dark:text-orange-400' : '']"
                      >👍 {{ post.vote_up_count }}</span>
                      <span class="hidden sm:flex items-center gap-0.5">👁 {{ fmtView(post.view_count) }}</span>
                    </span>
                  </div>
                </div>
              </Link>
            </div>
          </div>

          <!-- ⚔️ 전쟁터 / 🎡 놀이터 탭 ─────────────────────── -->
          <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <!-- 탭 헤더 -->
            <div class="flex border-b border-gray-100 dark:border-slate-800">
              <button
                @click="activeTab = 'battle'"
                :class="[
                  'flex items-center gap-1.5 px-4 py-3 text-sm font-bold transition-colors border-b-2 -mb-px',
                  activeTab === 'battle'
                    ? 'border-violet-600 text-violet-600 dark:text-violet-400'
                    : 'border-transparent text-gray-400 dark:text-slate-500 hover:text-gray-600 dark:hover:text-slate-300',
                ]"
              >⚔️ 전쟁터</button>

              <button
                @click="activeTab = 'play'"
                :class="[
                  'flex items-center gap-1.5 px-4 py-3 text-sm font-bold transition-colors border-b-2 -mb-px',
                  activeTab === 'play'
                    ? 'border-violet-600 text-violet-600 dark:text-violet-400'
                    : 'border-transparent text-gray-400 dark:text-slate-500 hover:text-gray-600 dark:hover:text-slate-300',
                ]"
              >🎡 놀이터</button>

              <Link
                :href="activeTab === 'battle' ? '/boards/battle-politics' : '/boards/play-humor'"
                class="ml-auto px-4 py-3 text-xs text-gray-400 dark:text-slate-500 hover:text-violet-500 dark:hover:text-violet-400 flex items-center gap-0.5 transition-colors"
              >더보기 →</Link>
            </div>

            <!-- 빈 상태 -->
            <div v-if="!tabPosts.length"
              class="py-12 text-center text-sm text-gray-400 dark:text-slate-600">
              게시글이 없습니다.
            </div>

            <!-- 최신글 목록 -->
            <div v-else>
              <Link
                v-for="post in tabPosts"
                :key="post.id"
                :href="`/boards/${post.board_slug}/posts/${post.id}`"
                class="flex items-stretch gap-0 border-b border-gray-50 dark:border-slate-800/50 last:border-0 hover:bg-gray-50 dark:hover:bg-slate-800/40 transition-colors group"
              >
                <!-- 진영 색상 세로선 -->
                <div
                  class="w-[3px] flex-shrink-0 rounded-l"
                  :style="{
                    backgroundColor: post.faction && factionConfig[post.faction]
                      ? factionConfig[post.faction].color + '60'
                      : 'transparent',
                  }"
                ></div>

                <!-- 본문 -->
                <div class="flex-1 min-w-0 px-4 py-3">
                  <!-- 제목 행 -->
                  <div class="flex items-start gap-1.5 mb-1">
                    <span
                      class="flex-shrink-0 text-[10px] font-semibold px-1.5 py-0.5 rounded bg-gray-100 dark:bg-slate-800 text-gray-400 dark:text-slate-500 leading-none mt-0.5 whitespace-nowrap"
                    >{{ post.board_name }}</span>
                    <span class="text-[13px] sm:text-sm font-medium text-gray-800 dark:text-slate-200 group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors leading-snug line-clamp-1">
                      {{ post.title }}
                    </span>
                    <span
                      v-if="post.comment_count > 0"
                      class="flex-shrink-0 text-violet-500 dark:text-violet-400 font-bold text-[13px] sm:text-sm leading-snug"
                    >[{{ post.comment_count }}]</span>
                  </div>

                  <!-- 메타 행 -->
                  <div class="flex items-center gap-1.5 text-[11px] text-gray-400 dark:text-slate-500">
                    <span
                      v-if="post.faction && factionConfig[post.faction]"
                      :style="{ color: factionConfig[post.faction].color }"
                      class="font-semibold"
                    >{{ factionConfig[post.faction].emoji }} {{ post.is_anonymous ? '익명' : (post.user?.nickname ?? '?') }}</span>
                    <span v-else>{{ post.is_anonymous ? '익명' : (post.user?.nickname ?? '?') }}</span>

                    <span class="text-gray-200 dark:text-slate-700">·</span>
                    <span>{{ timeAgo(post.created_at) }}</span>

                    <span v-if="post.vote_up_count > 0" class="ml-auto flex items-center gap-0.5">
                      👍 <span class="font-semibold">{{ post.vote_up_count }}</span>
                    </span>
                  </div>
                </div>
              </Link>
            </div>
          </div>

        </div><!-- /LEFT -->

        <!-- ══════════════════════════════════════════════════
             RIGHT: 사이드바 (데스크탑 우측 / 모바일 하단)
             ══════════════════════════════════════════════════ -->
        <div class="space-y-4 lg:sticky lg:top-20 lg:self-start">

          <!-- 📊 진영 점수 ──────────────────────────────────── -->
          <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-slate-800">
              <h3 class="font-bold text-sm text-gray-800 dark:text-slate-200 flex items-center gap-1.5">
                <span>📊</span> 진영 점수
              </h3>
              <Link href="/stats" class="text-xs text-violet-500 dark:text-violet-400 hover:underline font-medium">
                통계 →
              </Link>
            </div>
            <div class="p-3 space-y-2">
              <div
                v-for="(item, i) in realtimeScores"
                :key="(item as any).faction_type"
                class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl transition-colors"
                :style="{ backgroundColor: (item as any).color + '18' }"
              >
                <span class="text-base flex-shrink-0">{{ ['🥇', '🥈', '🥉'][i] ?? '' }}</span>
                <span class="text-sm font-bold" :style="{ color: (item as any).color }">
                  {{ (item as any).emoji }} {{ (item as any).label }}
                </span>
                <span class="ml-auto text-base font-black tabular-nums" :style="{ color: (item as any).color }">
                  {{ Math.round(((item as any).normalized_score ?? 0) * 100) }}
                </span>
              </div>
              <div v-if="!realtimeScores.length" class="py-4 text-center text-xs text-gray-400 dark:text-slate-600">
                집계 데이터 없음
              </div>
            </div>
          </div>

          <!-- ⚡ 게시판 바로가기 ────────────────────────────── -->
          <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-800">
              <h3 class="font-bold text-sm text-gray-800 dark:text-slate-200 flex items-center gap-1.5">
                <span>⚡</span> 게시판 바로가기
              </h3>
            </div>
            <div class="p-3 space-y-3">
              <!-- 전쟁터 -->
              <div v-if="battleBoards.length">
                <p class="text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-1.5 px-0.5">
                  ⚔️ 전쟁터
                </p>
                <div class="grid grid-cols-2 gap-1.5">
                  <Link
                    v-for="board in battleBoards"
                    :key="board.id"
                    :href="`/boards/${board.slug}`"
                    class="text-xs px-2 py-2 rounded-lg bg-gray-50 dark:bg-slate-800 hover:bg-violet-50 dark:hover:bg-violet-900/30 text-gray-600 dark:text-slate-400 hover:text-violet-700 dark:hover:text-violet-300 transition-colors font-medium text-center truncate leading-snug"
                  >{{ board.name }}</Link>
                </div>
              </div>

              <!-- 놀이터 -->
              <div v-if="playgroundBoards.length">
                <p class="text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-1.5 px-0.5">
                  🎡 놀이터
                </p>
                <div class="grid grid-cols-2 gap-1.5">
                  <Link
                    v-for="board in playgroundBoards"
                    :key="board.id"
                    :href="`/boards/${board.slug}`"
                    class="text-xs px-2 py-2 rounded-lg bg-gray-50 dark:bg-slate-800 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 text-gray-600 dark:text-slate-400 hover:text-emerald-700 dark:hover:text-emerald-300 transition-colors font-medium text-center truncate leading-snug"
                  >{{ board.name }}</Link>
                </div>
              </div>
            </div>
          </div>

          <!-- 비로그인 CTA 카드 ──────────────────────────────── -->
          <div v-if="!user"
            class="bg-gradient-to-br from-violet-50 to-indigo-50 dark:from-violet-900/20 dark:to-indigo-900/20 border border-violet-200 dark:border-violet-800/40 rounded-2xl p-5 text-center"
          >
            <div class="text-3xl mb-2">🧭</div>
            <p class="text-sm font-bold text-gray-800 dark:text-slate-200 mb-1">나의 정치 성향은?</p>
            <p class="text-xs text-gray-500 dark:text-slate-400 mb-4 leading-relaxed">
              10문항으로 진영을 진단하고<br>커뮤니티에 참여하세요
            </p>
            <Link href="/political-test"
              class="block text-sm font-bold bg-violet-600 hover:bg-violet-500 text-white px-4 py-2.5 rounded-xl transition-colors mb-2">
              성향 테스트 시작 →
            </Link>
            <Link href="/register"
              class="block text-xs font-medium text-gray-500 dark:text-slate-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
              이미 계정이 있으신가요?
              <span class="underline">로그인</span>
            </Link>
          </div>

          <!-- 로그인 유저 — 아지트 바로가기 ──────────────────── -->
          <div v-else-if="userFaction"
            class="rounded-2xl p-4 border"
            :style="{
              backgroundColor: userFaction.color + '12',
              borderColor: userFaction.color + '40',
            }"
          >
            <div class="flex items-center gap-2 mb-3">
              <span class="text-xl">{{ userFaction.emoji }}</span>
              <div>
                <p class="text-sm font-bold text-gray-800 dark:text-slate-200">
                  <span :style="{ color: userFaction.color }">{{ userFaction.label }}</span> 아지트
                </p>
                <p class="text-xs text-gray-500 dark:text-slate-400">우리 진영 전용 공간</p>
              </div>
            </div>
            <Link v-if="azitSlug" :href="`/boards/${azitSlug}`"
              class="block text-xs font-bold text-center py-2 rounded-xl text-white transition-opacity hover:opacity-90"
              :style="{ backgroundColor: userFaction.color }"
            >아지트 입장 →</Link>
          </div>

        </div><!-- /RIGHT sidebar -->

      </div><!-- /2-col grid -->
    </div><!-- /max-w container -->
  </div>
</template>
