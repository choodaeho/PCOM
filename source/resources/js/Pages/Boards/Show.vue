<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import PostCard from '@/Components/PostCard.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
    board:   { type: Object, required: true },
    posts:   { type: Object, default: () => ({ data: [], links: [], meta: {} }) },
    filters: { type: Object, default: () => ({}) },
})

const page = usePage()
const user = computed(() => page.props.auth?.user)

const isBattle     = computed(() => props.board?.board_type === 'battle')
const isAzit       = computed(() => props.board?.board_type === 'azit')
const isPlayground = computed(() => props.board?.board_type === 'playground')

// ── 글쓰기 권한 ────────────────────────────────────────────────────
const canWrite = computed(() => {
    if (!user.value || !user.value.test_completed) return false
    if (isAzit.value) return user.value.political_type === props.board?.allowed_faction
    return true
})

const writeBlockReason = computed(() => {
    if (!user.value) return '로그인 후 이용하세요'
    if (!user.value.test_completed) return '성향 테스트를 먼저 완료하세요'
    if (isAzit.value && user.value.political_type !== props.board?.allowed_faction) {
        return '본인 진영의 아지트에서만 글을 작성할 수 있습니다'
    }
    return ''
})

// ── 3단 필터 상태 (computed → props 항상 동기화) ──────────────────
const currentType     = computed(() => props.filters?.type     || 'all')
const currentCategory = computed(() => props.filters?.category || '')
const currentSort     = computed(() => props.filters?.sort     || 'latest')
const currentFaction  = computed(() => props.filters?.faction  || '')

// 게시판에 카테고리가 있는지 여부
const hasCategories = computed(() => Array.isArray(props.board?.categories) && props.board.categories.length > 0)

// 인기글 라벨 (board_type별: 화제글/베스트/인기글)
const hotLabel = computed(() => props.board?.hot_label || '인기글')

const sortOptions = [
    { value: 'latest',  label: '최신',  icon: '🕐' },
    { value: 'popular', label: '추천순', icon: '🔥' },
    { value: 'views',   label: '조회순', icon: '👀' },
]

const factionFilters = [
    { value: '',             label: '전체', emoji: '📋' },
    { value: 'conservative', label: '보수', emoji: '🦅' },
    { value: 'moderate',     label: '중도', emoji: '⚖️' },
    { value: 'progressive',  label: '진보', emoji: '🕊️' },
]

// ── 검색 상태 (ref — input binding 용) ────────────────────────────
// props.filters 기반으로 초기화, 뒤로가기/필터변경 시 watch로 동기화
const searchQuery = ref(props.filters?.q           || '')
const searchType  = ref(props.filters?.search_type || 'title')
// ── SEO ──────────────────────────────────────────────────────────
const seoDesc = computed(() => {
  const desc = props.board?.description
  if (desc) return desc
  const typeLabels = {
    battle:     '전쟁터 — 보수·중도·진보가 격돌하는 토론장',
    playground: '놀이터 — 정치를 떠나 자유롭게 소통하는 공간',
    azit:       '아지트 — 진영 전용 비밀 공간',
  }
  return typeLabels[props.board?.board_type] ?? `${props.board?.name ?? '게시판'}의 최신 글`
})

// props.filters 변화 시 (Inertia 이동 후) 검색 input과 동기화
watch(() => props.filters, (f) => {
    searchQuery.value = f?.q           || ''
    searchType.value  = f?.search_type || 'title'
})

// 현재 URL에 검색어가 적용되어 있는지
const isSearchActive = computed(() => !!(props.filters?.q && props.filters.q.trim() !== ''))

// ── 통합 필터 적용 ─────────────────────────────────────────────────
// 검색 파라미터를 함께 전달해 필터 변경 시에도 검색이 유지됨.
// doSearch / clearSearch 에서 q, st 를 명시적으로 오버라이드해 전달.
const applyFilter = (
    type,
    category,
    sort,
    faction,
    q  = searchQuery.value,
    st = searchType.value,
) => {
    const params = {}
    if (type     && type     !== 'all')    params.type     = type
    if (category && category !== '')       params.category = category
    if (sort     && sort     !== 'latest') params.sort     = sort
    if (faction  && faction  !== '')       params.faction  = faction
    if (q && q.trim() !== '') {
        params.q           = q.trim()
        params.search_type = st || 'title'
    }

    router.get(
        `/boards/${props.board.slug}`,
        params,
        { preserveScroll: true, preserveState: false },
    )
}

const setType     = (type)     => applyFilter(type,              currentCategory.value, currentSort.value, currentFaction.value)
const setCategory = (category) => applyFilter(currentType.value, category,              currentSort.value, currentFaction.value)
const setSort     = (sort)     => applyFilter(currentType.value, currentCategory.value, sort,              currentFaction.value)
const setFaction  = (faction)  => applyFilter(currentType.value, currentCategory.value, currentSort.value, faction)

// ── 검색 실행 / 초기화 ────────────────────────────────────────────
const doSearch = () => {
    if (!searchQuery.value.trim()) {
        clearSearch()
        return
    }
    applyFilter(currentType.value, currentCategory.value, currentSort.value, currentFaction.value)
}

const clearSearch = () => {
    searchQuery.value = ''
    searchType.value  = 'title'
    // q, st 를 빈값으로 명시 전달 → params에 포함 안 됨
    applyFilter(currentType.value, currentCategory.value, currentSort.value, currentFaction.value, '', 'title')
}

// ── 빈 목록 상태 ──────────────────────────────────────────────────
const isEmpty      = computed(() => (props.posts?.data ?? []).length === 0)
const isHotEmpty   = computed(() => currentType.value === 'hot' && isEmpty.value && !isSearchActive.value)
const isBaseEmpty  = computed(() => currentType.value !== 'hot' && isEmpty.value && !isSearchActive.value)
const isSearchEmpty = computed(() => isEmpty.value && isSearchActive.value)
</script>

<template>
<Head :title="board.name">
  <meta name="description" :content="seoDesc" />
  <meta property="og:title" :content="`${board.name} — 폴릿`" />
  <meta property="og:description" :content="seoDesc" />
</Head>
    <div class="max-w-5xl mx-auto px-4 py-8">

        <!-- ── Board Header ─────────────────────────────────────────── -->
        <div class="mb-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <!-- 브레드크럼 -->
                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                        <Link href="/boards" class="text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 transition-colors text-sm flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            커뮤니티
                        </Link>
                        <span class="text-gray-300 dark:text-slate-700">/</span>
                        <span :class="[
                            'text-xs font-semibold px-2.5 py-0.5 rounded-full border',
                            isBattle
                                ? 'text-orange-400 bg-orange-500/10 border-orange-500/30'
                                : isPlayground
                                    ? 'text-emerald-400 bg-emerald-500/10 border-emerald-500/30'
                                    : 'text-slate-500 dark:text-slate-400 bg-gray-100 dark:bg-slate-800 border-gray-300 dark:border-slate-700'
                        ]">
                            {{ isBattle ? '⚔️ 전쟁터' : isPlayground ? '🎡 놀이터' : '🏠 아지트' }}
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white">{{ board.name }}</h1>
                    <p v-if="board.description" class="text-slate-500 dark:text-slate-400 text-sm mt-1">{{ board.description }}</p>
                </div>

                <!-- 글쓰기 버튼 -->
                <div class="flex-shrink-0 mt-1">
                    <Link
                        v-if="canWrite"
                        :href="`/boards/${board.slug}/posts/create`"
                        class="flex items-center gap-2 bg-violet-600 hover:bg-violet-500 active:bg-violet-700 text-white font-semibold px-4 py-2.5 rounded-xl text-sm transition-colors shadow-sm"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        글쓰기
                    </Link>
                    <div v-else class="group relative">
                        <button class="flex items-center gap-2 bg-gray-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 font-semibold px-4 py-2.5 rounded-xl text-sm cursor-not-allowed opacity-70">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            글쓰기
                        </button>
                        <div class="absolute bottom-full right-0 mb-2 w-52 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-xs rounded-xl px-3 py-2 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10 text-center shadow-lg">
                            {{ writeBlockReason }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ 필터 영역 ══════════════════════════════════════════════ -->
        <div class="mb-4 space-y-2">

            <!-- ① 1단: 전체글 / 인기글(화제글/베스트) 탭 -->
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div class="flex bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700 rounded-xl p-1 gap-0.5">
                    <button
                        @click="setType('all')"
                        :class="[
                            'px-4 py-1.5 rounded-lg text-sm font-semibold transition-all',
                            currentType === 'all'
                                ? 'bg-violet-600 text-white shadow-sm'
                                : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-white dark:hover:bg-slate-700'
                        ]"
                    >
                        전체글
                    </button>
                    <button
                        @click="setType('hot')"
                        :class="[
                            'flex items-center gap-1 px-4 py-1.5 rounded-lg text-sm font-semibold transition-all',
                            currentType === 'hot'
                                ? isBattle
                                    ? 'bg-red-500 text-white shadow-sm'
                                    : isAzit
                                        ? 'bg-amber-500 text-white shadow-sm'
                                        : 'bg-orange-500 text-white shadow-sm'
                                : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-white dark:hover:bg-slate-700'
                        ]"
                    >
                        <span>{{ isBattle ? '⚡' : isAzit ? '👑' : '🔥' }}</span>
                        <span>{{ hotLabel }}</span>
                    </button>
                </div>

                <!-- 진영 필터 (전쟁터 전용) -->
                <div v-if="isBattle" class="flex bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700 rounded-xl p-1 gap-0.5">
                    <button
                        v-for="f in factionFilters"
                        :key="f.value"
                        @click="setFaction(f.value)"
                        :class="[
                            'flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-semibold transition-all',
                            currentFaction === f.value
                                ? 'bg-slate-700 dark:bg-slate-600 text-white shadow-sm'
                                : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-white dark:hover:bg-slate-700'
                        ]"
                    >
                        <span>{{ f.emoji }}</span>
                        <span>{{ f.label }}</span>
                    </button>
                </div>
            </div>

            <!-- ② 2단: 카테고리 (말머리) 필터 — 게시판에 categories가 있을 때만 -->
            <div v-if="hasCategories" class="flex flex-wrap gap-1.5">
                <button
                    @click="setCategory('')"
                    :class="[
                        'px-3 py-1 rounded-full text-xs font-semibold border transition-all',
                        currentCategory === ''
                            ? 'bg-slate-700 dark:bg-slate-500 text-white border-transparent'
                            : 'bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 border-gray-200 dark:border-slate-700 hover:border-slate-400 dark:hover:border-slate-500'
                    ]"
                >
                    전체
                </button>
                <button
                    v-for="cat in board.categories"
                    :key="cat"
                    @click="setCategory(cat)"
                    :class="[
                        'px-3 py-1 rounded-full text-xs font-semibold border transition-all',
                        currentCategory === cat
                            ? 'bg-violet-600 text-white border-transparent'
                            : 'bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 border-gray-200 dark:border-slate-700 hover:border-violet-400 dark:hover:border-violet-500 hover:text-violet-600 dark:hover:text-violet-400'
                    ]"
                >
                    {{ cat }}
                </button>
            </div>

            <!-- ③ 3단: 정렬 (최신 / 추천순 / 조회순) -->
            <div class="flex items-center gap-3">
                <div class="flex bg-gray-100 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700 rounded-xl p-1 gap-0.5">
                    <button
                        v-for="opt in sortOptions"
                        :key="opt.value"
                        @click="setSort(opt.value)"
                        :class="[
                            'flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all',
                            currentSort === opt.value
                                ? opt.value === 'popular'
                                    ? 'bg-orange-500 text-white shadow-sm'
                                    : opt.value === 'views'
                                        ? 'bg-sky-500 text-white shadow-sm'
                                        : 'bg-violet-600 text-white shadow-sm'
                                : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-white dark:hover:bg-slate-700'
                        ]"
                    >
                        <span>{{ opt.icon }}</span>
                        <span>{{ opt.label }}</span>
                    </button>
                </div>

                <!-- 게시글 수 -->
                <span class="ml-auto text-xs text-slate-400 dark:text-slate-500">
                    총 {{ (posts.meta?.total ?? posts.total ?? 0).toLocaleString() }}개
                </span>
            </div>
        </div>

        <!-- ── 게시글 목록 ────────────────────────────────────────────── -->
        <div class="space-y-2 mb-8">

            <!-- 검색 활성 배너 -->
            <div
                v-if="isSearchActive"
                class="flex items-center justify-between gap-3 px-4 py-2.5 bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800/50 rounded-xl text-sm"
            >
                <span class="text-violet-700 dark:text-violet-300 font-medium flex items-center gap-1.5">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span>
                        <strong class="font-bold">"{{ props.filters.q }}"</strong>
                        <span class="text-violet-500 dark:text-violet-400 ml-1">
                            ({{ { title: '제목', content: '내용', both: '제목+내용' }[props.filters.search_type] ?? '제목' }} 검색)
                        </span>
                        — 총 {{ (posts.meta?.total ?? posts.total ?? 0).toLocaleString() }}건
                    </span>
                </span>
                <button
                    @click="clearSearch"
                    class="flex-shrink-0 text-violet-400 hover:text-violet-600 dark:hover:text-violet-200 transition-colors text-xs font-medium flex items-center gap-1"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    검색 해제
                </button>
            </div>

            <!-- 검색 입력 -->
            <form @submit.prevent="doSearch" class="flex items-center gap-2">
                <div class="flex flex-1 items-center bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl overflow-hidden focus-within:border-violet-400 dark:focus-within:border-violet-500 transition-colors">
                    <select
                        v-model="searchType"
                        class="shrink-0 text-xs pl-3 pr-2 py-2.5 border-r border-gray-200 dark:border-slate-700 bg-transparent text-slate-500 dark:text-slate-400 focus:outline-none cursor-pointer"
                    >
                        <option value="title">제목</option>
                        <option value="content">내용</option>
                        <option value="both">제목+내용</option>
                    </select>
                    <input
                        v-model="searchQuery"
                        type="search"
                        placeholder="검색어를 입력하세요"
                        @keyup.enter="doSearch"
                        class="flex-1 px-3 py-2.5 text-sm bg-transparent text-slate-700 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none min-w-0"
                    />
                </div>
                <button
                    type="submit"
                    class="shrink-0 flex items-center gap-1.5 px-4 py-2.5 bg-violet-600 hover:bg-violet-500 active:bg-violet-700 text-white text-sm font-semibold rounded-xl transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    검색
                </button>
            </form>

            <!-- ── 빈 상태 ──────────────────────────────────────────────── -->

            <!-- 인기글 탭 — 글 없음 -->
            <div v-if="isHotEmpty" class="py-16 text-center">
                <p class="text-5xl mb-4">📭</p>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">아직 인기글이 없습니다.</p>
                <p class="text-slate-400 dark:text-slate-500 text-xs mt-1">활발한 활동이 쌓이면 인기글이 등록됩니다.</p>
            </div>

            <!-- 전체글 탭 — 글 없음 (검색 없음) -->
            <div v-else-if="isBaseEmpty" class="py-16 text-center">
                <p class="text-5xl mb-4">📋</p>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">아직 게시글이 없습니다.</p>
                <Link
                    v-if="canWrite"
                    :href="`/boards/${board.slug}/posts/create`"
                    class="mt-4 inline-flex items-center gap-2 px-5 py-2.5 bg-violet-600 hover:bg-violet-500 text-white text-sm font-semibold rounded-xl transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    첫 번째 글 작성하기
                </Link>
            </div>

            <!-- 검색 결과 없음 -->
            <div v-else-if="isSearchEmpty" class="py-16 text-center">
                <p class="text-5xl mb-4">🔍</p>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">
                    "<strong>{{ props.filters.q }}</strong>" 검색 결과가 없습니다.
                </p>
                <button
                    @click="clearSearch"
                    class="mt-4 text-violet-500 hover:text-violet-600 text-sm transition-colors"
                >
                    검색 해제하기
                </button>
            </div>

            <!-- ── 게시글 목록 ─────────────────────────────────────────── -->
            <template v-else>
                <PostCard
                    v-for="post in posts.data"
                    :key="post.id"
                    :post="post"
                    :boardSlug="board.slug"
                    :showFaction="isBattle"
                />
            </template>
        </div>

        <!-- ── 페이지네이션 ───────────────────────────────────────────── -->
        <Pagination
            v-if="(posts.meta?.last_page ?? posts.last_page ?? 1) > 1"
            :links="posts.links"
        />

    </div>
</template>
