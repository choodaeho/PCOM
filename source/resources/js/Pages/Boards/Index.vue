<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import FactionBadge from '@/Components/FactionBadge.vue'
import { echo } from '@/echo'

defineOptions({ layout: AppLayout })

const props = defineProps({
  azit: Object,              // { id, name, slug, description, post_count } | null
  battleBoards: Array,       // [{ id, name, slug, description, post_count }]
  playgroundBoards: Array,   // [{ id, name, slug, description, post_count }]
  activePolls: Array,        // [{ id, question, options: [{ id, label, vote_count, faction_counts }] }]
  userFaction: String,       // 'conservative' | 'moderate' | 'progressive' | null
})

const page = usePage()
const user = computed(() => page.props.auth?.user)

const factionConfig = {
  conservative: { label: '보수', emoji: '🦅', color: 'text-red-400', border: 'border-red-500/50', bg: 'bg-red-500/10', btn: 'bg-red-500 hover:bg-red-400' },
  moderate:     { label: '중도', emoji: '⚖️', color: 'text-violet-400', border: 'border-violet-500/50', bg: 'bg-violet-500/10', btn: 'bg-violet-500 hover:bg-violet-400' },
  progressive:  { label: '진보', emoji: '🕊️', color: 'text-blue-400', border: 'border-blue-500/50', bg: 'bg-blue-500/10', btn: 'bg-blue-500 hover:bg-blue-400' },
}

const myFaction = computed(() => {
  const faction = props.userFaction ?? user.value?.political_type
  return faction ? (factionConfig[faction] ?? null) : null
})

// Poll voting — activePolls를 로컬 반응형 상태로 복사해 실시간 갱신 가능하게 함
const polls = ref(props.activePolls.map(p => ({ ...p, options: p.options.map(o => ({ ...o })) })))
const pollForm = useForm({ option_id: null })
const votedPolls = ref({})

const votePoll = (pollId, optionId) => {
  if (votedPolls.value[pollId]) return
  pollForm.option_id = optionId
  pollForm.post(`/polls/${pollId}/vote`, {
    onSuccess: () => { votedPolls.value[pollId] = optionId }
  })
}

const totalVotes = (options) => options.reduce((s, o) => s + (o.vote_count ?? 0), 0)
const votePercent = (option, options) => {
  const total = totalVotes(options)
  if (!total) return 0
  return Math.round((option.vote_count / total) * 100)
}

// ── 실시간(WebSocket) 투표 결과 구독 ─────────────────────────
// PollVoteUpdated 브로드캐스트: { poll_id, options: [{id,label,vote_count,faction_counts}], total_vote_count }
const pollChannels = []

function applyPollUpdate(payload) {
  const target = polls.value.find(p => p.id === payload.poll_id)
  if (!target) return
  target.options = payload.options
  target.total_vote_count = payload.total_vote_count
}

onMounted(() => {
  polls.value.forEach(poll => {
    const channel = echo.channel(`polls.${poll.id}`)
    channel.listen('.PollVoteUpdated', applyPollUpdate)
    pollChannels.push(poll.id)
  })
})

onBeforeUnmount(() => {
  pollChannels.forEach(id => echo.leave(`polls.${id}`))
})
</script>

<template>
<Head title="커뮤니티 게시판">
  <meta name="description" content="보수·중도·진보 아지트와 전쟁터, 놀이터를 모두 한곳에서. 지금 바로 참여하세요." />
  <meta property="og:title" content="커뮤니티 게시판 — 폴릿" />
  <meta property="og:description" content="보수·중도·진보 아지트와 전쟁터, 놀이터를 모두 한곳에서." />
</Head>
  <div class="max-w-7xl mx-auto px-4 py-10">
    <div class="flex gap-8">
      <!-- Left: Main content -->
      <div class="flex-1 min-w-0">
        <!-- Azit Section -->
        <section class="mb-10">
          <div class="flex items-center gap-3 mb-5">
            <h2 class="text-xl font-black text-slate-900 dark:text-white">🏠 아지트</h2>
            <span class="text-xs text-slate-400 dark:text-slate-500">나의 진영 전용 공간</span>
          </div>

          <!-- Not logged in -->
          <div v-if="!user" class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 border-dashed rounded-2xl p-8 text-center">
            <p class="text-slate-500 dark:text-slate-400 mb-4">로그인 후 나의 아지트에 입장할 수 있습니다</p>
            <Link href="/login" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-500 text-white font-semibold px-5 py-2.5 rounded-xl text-sm transition-colors">
              로그인하기
            </Link>
          </div>

          <!-- No test taken -->
          <div v-else-if="!azit" class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 border-dashed rounded-2xl p-8 text-center">
            <p class="text-4xl mb-4">🧭</p>
            <p class="text-slate-900 dark:text-white font-bold mb-2">아직 진영이 없습니다</p>
            <p class="text-slate-500 dark:text-slate-400 text-sm mb-4">성향 테스트를 통해 나의 진영을 확인하세요</p>
            <Link href="/political-test" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-500 text-white font-semibold px-5 py-2.5 rounded-xl text-sm transition-colors">
              테스트 시작하기
            </Link>
          </div>

          <!-- Azit card -->
          <div
            v-else
            :class="['rounded-2xl border-2 p-6 transition-all hover:-translate-y-0.5', myFaction?.border, myFaction?.bg]"
          >
            <div class="flex items-start justify-between mb-4">
              <div class="flex items-center gap-3">
                <span class="text-3xl">{{ myFaction?.emoji }}</span>
                <div>
                  <h3 :class="['text-xl font-black', myFaction?.color]">{{ azit.name }}</h3>
                  <p class="text-slate-600 dark:text-slate-400 text-sm">{{ azit.description }}</p>
                </div>
              </div>
              <span class="text-xs text-slate-400 dark:text-slate-500">게시글 {{ azit.post_count ?? 0 }}개</span>
            </div>
            <Link
              :href="`/boards/${azit.slug}`"
              :class="['inline-flex items-center gap-2 text-white font-bold px-5 py-2.5 rounded-xl text-sm transition-colors', myFaction?.btn]"
            >
              아지트 입장하기
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
            </Link>
          </div>
        </section>

        <!-- Battleground Section -->
        <section class="mb-10">
          <div class="flex items-center gap-3 mb-5">
            <h2 class="text-xl font-black text-slate-900 dark:text-white">⚔️ 전쟁터</h2>
            <span class="text-xs text-slate-400 dark:text-slate-500">모든 진영이 참여하는 토론 공간</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Link
              v-for="board in battleBoards"
              :key="board.id"
              :href="`/boards/${board.slug}`"
              class="block bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 hover:border-gray-300 dark:hover:border-slate-600 rounded-2xl p-5 transition-all hover:-translate-y-0.5 group"
            >
              <div class="flex items-start justify-between mb-3">
                <h3 class="font-bold text-slate-900 dark:text-white group-hover:text-violet-400 transition-colors">{{ board.name }}</h3>
                <svg class="w-4 h-4 text-slate-400 dark:text-slate-600 group-hover:text-violet-400 transition-colors flex-shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </div>
              <p class="text-slate-500 text-sm mb-4 line-clamp-2">{{ board.description }}</p>
              <div class="flex items-center gap-4 text-xs text-slate-400 dark:text-slate-600">
                <span class="flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  게시글 {{ board.post_count ?? 0 }}
                </span>
              </div>
            </Link>
          </div>
        </section>

        <!-- Playground Section -->
        <section>
          <div class="flex items-center gap-3 mb-5">
            <h2 class="text-xl font-black text-slate-900 dark:text-white">🎡 놀이터</h2>
            <span class="text-xs text-slate-400 dark:text-slate-500">정치 무관 — 누구나 자유롭게</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Link
              v-for="board in playgroundBoards"
              :key="board.id"
              :href="`/boards/${board.slug}`"
              class="block bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 hover:border-emerald-700/40 rounded-2xl p-5 transition-all hover:-translate-y-0.5 group"
            >
              <div class="flex items-start justify-between mb-3">
                <h3 class="font-bold text-slate-900 dark:text-white group-hover:text-emerald-400 transition-colors">{{ board.name }}</h3>
                <svg class="w-4 h-4 text-slate-400 dark:text-slate-600 group-hover:text-emerald-400 transition-colors flex-shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </div>
              <p class="text-slate-500 text-sm mb-4 line-clamp-2">{{ board.description }}</p>
              <div class="text-xs text-slate-400 dark:text-slate-600 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                게시글 {{ board.post_count ?? 0 }}
              </div>
            </Link>
          </div>

        </section>

        <!-- 🧰 툴박스 -->
        <section class="mt-10">
          <div class="flex items-center gap-3 mb-5">
            <h2 class="text-xl font-black text-slate-900 dark:text-white">🧰 툴박스</h2>
            <span class="text-xs text-slate-400 dark:text-slate-500">누구나 자유롭게 이용</span>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <!-- 로또번호생성기 - 운영중 -->
            <Link href="/tools"
              class="group flex items-center gap-4 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 hover:border-emerald-600/50 hover:bg-emerald-900/10 rounded-2xl p-5 transition-all">
              <div class="w-12 h-12 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-xl flex-shrink-0 group-hover:scale-110 transition-transform">
                🎰
              </div>
              <div class="min-w-0">
                <p class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-emerald-400 transition-colors">로또번호생성기</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">행운의 번호 뽑기</p>
                <span class="mt-1.5 inline-block text-xs bg-emerald-500/20 text-emerald-500 dark:text-emerald-400 px-2 py-0.5 rounded-full">운영중</span>
              </div>
            </Link>

            <!-- 오늘의 운세 - 운영중 -->
            <Link href="/tools"
              class="group flex items-center gap-4 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 hover:border-emerald-600/50 hover:bg-emerald-900/10 rounded-2xl p-5 transition-all">
              <div class="w-12 h-12 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-xl flex-shrink-0 group-hover:scale-110 transition-transform">
                🔮
              </div>
              <div class="min-w-0">
                <p class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-emerald-400 transition-colors">오늘의 운세</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">띠별 오늘 운세 확인</p>
                <span class="mt-1.5 inline-block text-xs bg-emerald-500/20 text-emerald-500 dark:text-emerald-400 px-2 py-0.5 rounded-full">운영중</span>
              </div>
            </Link>

            <!-- 이상형 월드컵 - 준비중 -->
            <div class="flex items-center gap-4 bg-gray-50 dark:bg-slate-900/50 border border-gray-200/50 dark:border-slate-800/50 rounded-2xl p-5 opacity-60 cursor-not-allowed select-none">
              <div class="w-12 h-12 rounded-xl bg-gray-200 dark:bg-slate-800 border border-gray-300 dark:border-slate-700 flex items-center justify-center text-xl flex-shrink-0">
                🏆
              </div>
              <div class="min-w-0">
                <p class="text-sm font-bold text-slate-500 dark:text-slate-400">이상형 월드컵</p>
                <p class="text-xs text-slate-400 dark:text-slate-600 mt-0.5">나의 이상형 정치인은?</p>
                <span class="mt-1.5 inline-block text-xs bg-gray-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500 px-2 py-0.5 rounded-full">준비중</span>
              </div>
            </div>

            <!-- 시사 퀴즈 - 준비중 -->
            <div class="flex items-center gap-4 bg-gray-50 dark:bg-slate-900/50 border border-gray-200/50 dark:border-slate-800/50 rounded-2xl p-5 opacity-60 cursor-not-allowed select-none">
              <div class="w-12 h-12 rounded-xl bg-gray-200 dark:bg-slate-800 border border-gray-300 dark:border-slate-700 flex items-center justify-center text-xl flex-shrink-0">
                🧩
              </div>
              <div class="min-w-0">
                <p class="text-sm font-bold text-slate-500 dark:text-slate-400">시사 퀴즈</p>
                <p class="text-xs text-slate-400 dark:text-slate-600 mt-0.5">오늘의 시사 상식 도전</p>
                <span class="mt-1.5 inline-block text-xs bg-gray-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500 px-2 py-0.5 rounded-full">준비중</span>
              </div>
            </div>
          </div>
        </section>
      </div>

      <!-- Right: Sidebar -->
      <aside class="hidden lg:block w-80 flex-shrink-0 space-y-6">
        <!-- Active Polls -->
        <div v-for="poll in polls" :key="poll.id" class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-200 dark:border-slate-800 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">실시간 투표</h3>
          </div>
          <div class="p-5">
            <p class="text-slate-600 dark:text-slate-300 text-sm font-medium mb-4 leading-snug">{{ poll.question }}</p>

            <div class="space-y-2.5">
              <div v-for="option in poll.options" :key="option.id">
                <button
                  @click="votePoll(poll.id, option.id)"
                  :disabled="!!votedPolls[poll.id] || pollForm.processing"
                  class="w-full text-left group"
                >
                  <div class="relative h-9 rounded-lg overflow-hidden bg-gray-100 dark:bg-slate-800">
                    <!-- Progress bar -->
                    <div
                      class="absolute inset-y-0 left-0 bg-violet-500/20 transition-all duration-700 rounded-lg"
                      :style="{ width: votePercent(option, poll.options) + '%' }"
                    ></div>
                    <div class="absolute inset-0 flex items-center justify-between px-3">
                      <span class="text-xs text-slate-600 dark:text-slate-300 font-medium group-hover:text-slate-900 dark:group-hover:text-white transition-colors">{{ option.label }}</span>
                      <span class="text-xs text-slate-400 dark:text-slate-500 tabular-nums font-bold">{{ votePercent(option, poll.options) }}%</span>
                    </div>
                  </div>
                  <!-- Faction breakdown -->
                  <div v-if="option.faction_counts" class="flex gap-1 mt-1 px-1">
                    <div
                      v-for="(cnt, faction) in option.faction_counts"
                      :key="faction"
                      :class="['h-0.5 rounded-full transition-all', faction === 'conservative' ? 'bg-red-500' : faction === 'moderate' ? 'bg-violet-500' : 'bg-blue-500']"
                      :style="{ width: cnt + 'px', maxWidth: '40px' }"
                    ></div>
                  </div>
                </button>
              </div>
            </div>

            <p class="text-xs text-slate-400 dark:text-slate-600 mt-3 text-right">총 {{ totalVotes(poll.options).toLocaleString() }}표</p>
          </div>
        </div>

        <!-- Faction Score Widget -->
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-200 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">진영 점수 현황</h3>
          </div>
          <div class="p-5 space-y-3">
            <div v-for="f in Object.entries(factionConfig)" :key="f[0]" class="flex items-center gap-3">
              <span class="text-lg flex-shrink-0">{{ f[1].emoji }}</span>
              <div class="flex-1">
                <div class="flex justify-between text-xs mb-1">
                  <span :class="f[1].color">{{ f[1].label }}</span>
                  <span class="text-slate-400 dark:text-slate-500">-</span>
                </div>
                <div class="h-1.5 bg-gray-200 dark:bg-slate-800 rounded-full">
                  <div :class="['h-full rounded-full w-0', f[0] === 'conservative' ? 'bg-red-500' : f[0] === 'moderate' ? 'bg-violet-500' : 'bg-blue-500']"></div>
                </div>
              </div>
            </div>
            <Link href="/stats" class="block text-center text-xs text-violet-500 dark:text-violet-400 hover:text-violet-300 pt-2 transition-colors">
              전체 통계 보기 →
            </Link>
          </div>
        </div>
      </aside>
    </div>
  </div>
</template>
