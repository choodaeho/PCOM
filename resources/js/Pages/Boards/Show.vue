<script setup>
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import FactionBadge from '@/Components/FactionBadge.vue'
import Pagination from '@/Components/Pagination.vue'
import PostCard from '@/Components/PostCard.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  board: Object,      // { id, name, description, category }
  posts: Object,      // Laravel paginator { data, links, meta }
  filters: Object,    // { sort, faction }
})

const page = usePage()
const user = computed(() => page.props.auth?.user)

const isBattle = computed(() => props.board?.category === 'battle')

const sortOptions = [
  { value: 'latest', label: '최신순' },
  { value: 'popular', label: '인기순' },
  { value: 'views', label: '조회순' },
]

const factionFilters = [
  { value: '', label: '전체', emoji: '📋' },
  { value: 'conservative', label: '보수', emoji: '🦅' },
  { value: 'moderate', label: '중도', emoji: '⚖️' },
  { value: 'progressive', label: '진보', emoji: '🕊️' },
]

const currentSort = ref(props.filters?.sort ?? 'latest')
const currentFaction = ref(props.filters?.faction ?? '')

const applyFilter = (sort = currentSort.value, faction = currentFaction.value) => {
  router.get(`/boards/${props.board.id}`, { sort, faction }, { preserveScroll: true, preserveState: true })
}

const setSort = (sort) => {
  currentSort.value = sort
  applyFilter(sort)
}

const setFaction = (faction) => {
  currentFaction.value = faction
  applyFilter(undefined, faction)
}

const factionBorderClass = {
  conservative: 'border-red-500/60',
  moderate: 'border-violet-500/60',
  progressive: 'border-blue-500/60',
}
</script>

<template>
  <div class="max-w-5xl mx-auto px-4 py-10">
    <!-- Board Header -->
    <div class="mb-8">
      <div class="flex items-start justify-between">
        <div>
          <div class="flex items-center gap-3 mb-2">
            <Link href="/boards" class="text-slate-500 hover:text-slate-300 transition-colors text-sm flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
              커뮤니티
            </Link>
            <span class="text-slate-700">/</span>
            <span :class="['text-xs font-semibold px-2.5 py-0.5 rounded-full border', isBattle ? 'text-orange-400 bg-orange-500/10 border-orange-500/30' : 'text-slate-400 bg-slate-800 border-slate-700']">
              {{ isBattle ? '⚔️ 전쟁터' : '🏠 아지트' }}
            </span>
          </div>
          <h1 class="text-3xl font-black text-white">{{ board.name }}</h1>
          <p class="text-slate-400 text-sm mt-1">{{ board.description }}</p>
        </div>

        <Link
          v-if="user"
          :href="`/boards/${board.id}/posts/create`"
          class="flex items-center gap-2 bg-violet-600 hover:bg-violet-500 text-white font-semibold px-4 py-2.5 rounded-xl text-sm transition-colors flex-shrink-0 mt-1"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          글쓰기
        </Link>
      </div>
    </div>

    <!-- Filters Bar -->
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
      <!-- Sort Tabs -->
      <div class="flex bg-slate-900 border border-slate-800 rounded-xl p-1 gap-0.5">
        <button
          v-for="opt in sortOptions"
          :key="opt.value"
          @click="setSort(opt.value)"
          :class="[
            'px-4 py-1.5 rounded-lg text-sm font-medium transition-colors',
            currentSort === opt.value
              ? 'bg-violet-600 text-white'
              : 'text-slate-400 hover:text-white'
          ]"
        >
          {{ opt.label }}
        </button>
      </div>

      <!-- Faction filter (battle only) -->
      <div v-if="isBattle" class="flex bg-slate-900 border border-slate-800 rounded-xl p-1 gap-0.5">
        <button
          v-for="f in factionFilters"
          :key="f.value"
          @click="setFaction(f.value)"
          :class="[
            'flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
            currentFaction === f.value
              ? 'bg-slate-700 text-white'
              : 'text-slate-400 hover:text-white'
          ]"
        >
          <span>{{ f.emoji }}</span>
          <span>{{ f.label }}</span>
        </button>
      </div>
    </div>

    <!-- Post Count -->
    <div class="flex items-center gap-2 mb-4 text-xs text-slate-500">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      총 {{ posts.meta?.total ?? 0 }}개의 게시글
    </div>

    <!-- Posts List -->
    <div class="space-y-2 mb-8">
      <div v-if="posts.data?.length === 0" class="bg-slate-900 border border-slate-800 border-dashed rounded-2xl py-16 text-center">
        <p class="text-4xl mb-3">📭</p>
        <p class="text-slate-400">아직 게시글이 없습니다.</p>
        <Link v-if="user" :href="`/boards/${board.id}/posts/create`" class="inline-flex mt-4 text-violet-400 hover:text-violet-300 text-sm font-medium transition-colors">
          첫 번째 글을 작성해보세요 →
        </Link>
      </div>

      <PostCard
        v-for="post in posts.data"
        :key="post.id"
        :post="post"
        :board-id="board.id"
        :show-faction="isBattle"
      />
    </div>

    <!-- Pagination -->
    <Pagination v-if="posts.meta?.last_page > 1" :links="posts.links" />
  </div>
</template>
