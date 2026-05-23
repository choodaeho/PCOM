<script setup>
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import PostCard from '@/Components/PostCard.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
    board:   { type: Object, required: true },
    // board: { id, name, slug, description, board_type, allowed_faction }
    posts:   { type: Object, default: () => ({ data: [], links: [], meta: {} }) },
    filters: { type: Object, default: () => ({}) },
})

const page = usePage()
const user = computed(() => page.props.auth?.user)

const isBattle = computed(() => props.board?.board_type === 'battle')
const isAzit   = computed(() => props.board?.board_type === 'azit')

/**
 * 글쓰기 가능 여부
 * - 비로그인: 불가
 * - 성향 테스트 미완료: 불가
 * - 아지트: 본인 진영 유저만 가능
 * - 전쟁터: 로그인 + 테스트 완료면 가능
 */
const canWrite = computed(() => {
    if (!user.value || !user.value.test_completed) return false
    if (isAzit.value) return user.value.political_type === props.board?.allowed_faction
    return true // 전쟁터·공지
})

/** 글쓰기 버튼에 표시할 안내 문구 (권한 없을 때 tooltip) */
const writeBlockReason = computed(() => {
    if (!user.value) return '로그인 후 이용하세요'
    if (!user.value.test_completed) return '성향 테스트를 먼저 완료하세요'
    if (isAzit.value && user.value.political_type !== props.board?.allowed_faction) {
        return '본인 진영의 아지트에서만 글을 작성할 수 있습니다'
    }
    return ''
})

const sortOptions = [
    { value: 'latest',  label: '최신순' },
    { value: 'popular', label: '인기순' },
    { value: 'views',   label: '조회순' },
]

const factionFilters = [
    { value: '',             label: '전체', emoji: '📋' },
    { value: 'conservative', label: '보수', emoji: '🦅' },
    { value: 'moderate',     label: '중도', emoji: '⚖️' },
    { value: 'progressive',  label: '진보', emoji: '🕊️' },
]

const currentSort    = ref(props.filters?.sort    ?? 'latest')
const currentFaction = ref(props.filters?.faction ?? '')

const applyFilter = (sort = currentSort.value, faction = currentFaction.value) => {
    router.get(
        `/boards/${props.board.slug}`,
        { sort, faction },
        { preserveScroll: true, preserveState: true },
    )
}

const setSort    = (sort)    => { currentSort.value    = sort;    applyFilter(sort) }
const setFaction = (faction) => { currentFaction.value = faction; applyFilter(undefined, faction) }
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 py-10">
        <!-- Board Header -->
        <div class="mb-8">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <Link href="/boards" class="text-slate-500 hover:text-slate-300 transition-colors text-sm flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            커뮤니티
                        </Link>
                        <span class="text-slate-700">/</span>
                        <span :class="[
                            'text-xs font-semibold px-2.5 py-0.5 rounded-full border',
                            isBattle
                                ? 'text-orange-400 bg-orange-500/10 border-orange-500/30'
                                : 'text-slate-400 bg-slate-800 border-slate-700'
                        ]">
                            {{ isBattle ? '⚔️ 전쟁터' : '🏠 아지트' }}
                        </span>
                    </div>
                    <h1 class="text-3xl font-black text-white">{{ board.name }}</h1>
                    <p class="text-slate-400 text-sm mt-1">{{ board.description }}</p>
                </div>

                <!-- 글쓰기 버튼: 권한 있을 때만 활성 링크, 없으면 비활성 버튼 + tooltip -->
                <div class="flex-shrink-0 mt-1">
                    <Link
                        v-if="canWrite"
                        :href="`/boards/${board.slug}/posts/create`"
                        class="flex items-center gap-2 bg-violet-600 hover:bg-violet-500 text-white font-semibold px-4 py-2.5 rounded-xl text-sm transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        글쓰기
                    </Link>
                    <!-- 비로그인 or 진영 불일치 -->
                    <div v-else class="group relative">
                        <Link
                            :href="!user ? '/login' : (user.test_completed ? '#' : '/political-test')"
                            class="flex items-center gap-2 bg-slate-700 text-slate-400 font-semibold px-4 py-2.5 rounded-xl text-sm cursor-not-allowed opacity-60"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            글쓰기
                        </Link>
                        <div class="absolute bottom-full right-0 mb-2 w-52 bg-slate-800 border border-slate-700 text-slate-300 text-xs rounded-lg px-3 py-2 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10 text-center">
                            {{ writeBlockReason }}
                        </div>
                    </div>
                </div>
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

            <!-- 진영 필터 (전쟁터 전용) -->
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
            <div
                v-if="(posts.data ?? []).length === 0"
                class="bg-slate-900 border border-slate-800 border-dashed rounded-2xl py-16 text-center"
            >
                <p class="text-4xl mb-3">📭</p>
                <p class="text-slate-400">아직 게시글이 없습니다.</p>
                <Link
                    v-if="canWrite"
                    :href="`/boards/${board.slug}/posts/create`"
                    class="inline-flex mt-4 text-violet-400 hover:text-violet-300 text-sm font-medium transition-colors"
                >
                    첫 번째 글을 작성해보세요 →
                </Link>
            </div>

            <PostCard
                v-for="post in posts.data"
                :key="post.id"
                :post="post"
                :board-slug="board.slug"
                :show-faction="isBattle"
            />
        </div>

        <!-- Pagination -->
        <Pagination v-if="(posts.meta?.last_page ?? 1) > 1" :links="posts.links" />
    </div>
</template>
