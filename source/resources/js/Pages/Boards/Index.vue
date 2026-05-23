<script setup>
import { computed, ref } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import FactionBadge from '@/Components/FactionBadge.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  azit: Object,           // { id, name, description, post_count }
  battleBoards: Array,    // [{ id, name, description, post_count }]
  activePolls: Array,     // [{ id, question, options: [{ id, label, vote_count, faction_counts }] }]
})

const page = usePage()
const user = computed(() => page.props.auth?.user)

const factionConfig = {
  conservative: { label: '보수', emoji: '🦅', color: 'text-red-400', border: 'border-red-500/50', bg: 'bg-red-500/10', btn: 'bg-red-500 hover:bg-red-400' },
  moderate:     { label: '중도', emoji: '⚖️', color: 'text-violet-400', border: 'border-violet-500/50', bg: 'bg-violet-500/10', btn: 'bg-violet-500 hover:bg-violet-400' },
  progressive:  { label: '진보', emoji: '🕊️', color: 'text-blue-400', border: 'border-blue-500/50', bg: 'bg-blue-500/10', btn: 'bg-blue-500 hover:bg-blue-400' },
}

const myFaction = computed(() => user.value ? (factionConfig[user.value.political_type] ?? null) : null)

// Poll voting
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
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 py-10">
    <div class="flex gap-8">
      <!-- Left: Main content -->
      <div class="flex-1 min-w-0">
        <!-- Azit Section -->
        <section class="mb-10">
          <div class="flex items-center gap-3 mb-5">
            <h2 class="text-xl font-black text-white">🏠 아지트</h2>
            <span class="text-xs text-slate-500">나의 진영 전용 공간</span>
          </div>

          <!-- Not logged in -->
          <div v-if="!user" class="bg-slate-900 border border-slate-800 border-dashed rounded-2xl p-8 text-center">
            <p class="text-slate-400 mb-4">로그인 후 나의 아지트에 입장할 수 있습니다</p>
            <Link href="/login" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-500 text-white font-semibold px-5 py-2.5 rounded-xl text-sm transition-colors">
              로그인하기
            </Link>
          </div>

          <!-- No test taken -->
          <div v-else-if="!azit" class="bg-slate-900 border border-slate-800 border-dashed rounded-2xl p-8 text-center">
            <p class="text-4xl mb-4">🧭</p>
            <p class="text-white font-bold mb-2">아직 진영이 없습니다</p>
            <p class="text-slate-400 text-sm mb-4">성향 테스트를 통해 나의 진영을 확인하세요</p>
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
                  <p class="text-slate-400 text-sm">{{ azit.description }}</p>
                </div>
              </div>
              <span class="text-xs text-slate-500">게시글 {{ azit.post_count ?? 0 }}개</span>
            </div>
            <Link
              :href="`/boards/${azit.id}`"
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
        <section>
          <div class="flex items-center gap-3 mb-5">
            <h2 class="text-xl font-black text-white">⚔️ 전쟁터</h2>
            <span class="text-xs text-slate-500">모든 진영이 참여하는 토론 공간</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Link
              v-for="board in battleBoards"
              :key="board.id"
              :href="`/boards/${board.id}`"
              class="block bg-slate-900 border border-slate-800 hover:border-slate-600 rounded-2xl p-5 transition-all hover:-translate-y-0.5 group"
            >
              <div class="flex items-start justify-between mb-3">
                <h3 class="font-bold text-white group-hover:text-violet-400 transition-colors">{{ board.name }}</h3>
                <svg class="w-4 h-4 text-slate-600 group-hover:text-violet-400 transition-colors flex-shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </div>
              <p class="text-slate-500 text-sm mb-4 line-clamp-2">{{ board.description }}</p>
              <div class="flex items-center gap-4 text-xs text-slate-600">
                <span class="flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  게시글 {{ board.post_count ?? 0 }}
                </span>
                <span class="flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  참여자 {{ board.member_count ?? 0 }}
                </span>
              </div>
            </Link>
          </div>
        </section>
      </div>

      <!-- Right: Sidebar -->
      <aside class="hidden lg:block w-80 flex-shrink-0 space-y-6">
        <!-- Active Polls -->
        <div v-for="poll in activePolls" :key="poll.id" class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-800 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <h3 class="text-sm font-bold text-white">실시간 투표</h3>
          </div>
          <div class="p-5">
            <p class="text-slate-300 text-sm font-medium mb-4 leading-snug">{{ poll.question }}</p>

            <div class="space-y-2.5">
              <div v-for="option in poll.options" :key="option.id">
                <button
                  @click="votePoll(poll.id, option.id)"
                  :disabled="!!votedPolls[poll.id] || pollForm.processing"
                  class="w-full text-left group"
                >
                  <div class="relative h-9 rounded-lg overflow-hidden bg-slate-800">
                    <!-- Progress bar -->
                    <div
                      class="absolute inset-y-0 left-0 bg-violet-500/20 transition-all duration-700 rounded-lg"
                      :style="{ width: votePercent(option, poll.options) + '%' }"
                    ></div>
                    <div class="absolute inset-0 flex items-center justify-between px-3">
                      <span class="text-xs text-slate-300 font-medium group-hover:text-white transition-colors">{{ option.label }}</span>
                      <span class="text-xs text-slate-500 tabular-nums font-bold">{{ votePercent(option, poll.options) }}%</span>
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

            <p class="text-xs text-slate-600 mt-3 text-right">총 {{ totalVotes(poll.options).toLocaleString() }}표</p>
          </div>
        </div>

        <!-- Faction Score Widget -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-800">
            <h3 class="text-sm font-bold text-white">진영 점수 현황</h3>
          </div>
          <div class="p-5 space-y-3">
            <div v-for="f in Object.entries(factionConfig)" :key="f[0]" class="flex items-center gap-3">
              <span class="text-lg flex-shrink-0">{{ f[1].emoji }}</span>
              <div class="flex-1">
                <div class="flex justify-between text-xs mb-1">
                  <span :class="f[1].color">{{ f[1].label }}</span>
                  <span class="text-slate-500">-</span>
                </div>
                <div class="h-1.5 bg-slate-800 rounded-full">
                  <div :class="['h-full rounded-full w-0', f[0] === 'conservative' ? 'bg-red-500' : f[0] === 'moderate' ? 'bg-violet-500' : 'bg-blue-500']"></div>
                </div>
              </div>
            </div>
            <Link href="/stats" class="block text-center text-xs text-violet-400 hover:text-violet-300 pt-2 transition-colors">
              전체 통계 보기 →
            </Link>
          </div>
        </div>
      </aside>
    </div>
  </div>
</template>
