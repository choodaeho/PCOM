<script setup>
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { getFactionPillClass } from '@/composables/useFactionPill'

defineOptions({ layout: AppLayout })

const props = defineProps({
    profileUser:  { type: Object, required: true },
    posts:        { type: Object, default: () => ({ data: [], meta: {}, links: [] }) },
    comments:     { type: Object, default: () => ({ data: [], meta: {}, links: [] }) },
    stats:        { type: Object, default: () => ({ post_count: 0, comment_count: 0, vote_up_count: 0 }) },
    badges:       { type: Array,  default: () => [] },
    isOwnProfile: { type: Boolean, default: false },
})

const factionConfig = {
    conservative: { label: '보수', emoji: '🔴', gradient: 'from-red-50 to-white dark:from-red-900/20 dark:to-slate-900' },
    moderate:     { label: '중도', emoji: '🟣', gradient: 'from-violet-50 to-white dark:from-violet-900/20 dark:to-slate-900' },
    progressive:  { label: '진보', emoji: '🔵', gradient: 'from-blue-50 to-white dark:from-blue-900/20 dark:to-slate-900' },
}

// ── SEO ──────────────────────────────────────────────────────────
const seoFactionLabel = computed(() => factionConfig[props.profileUser.political_type]?.label ?? '')
const seoTitle = computed(() => `${props.profileUser.nickname}의 프로필`)
const seoDesc  = computed(() => `${props.profileUser.nickname}(${seoFactionLabel.value} ${props.profileUser.level}레벨) — 폴릿 활동 프로필`)

const CATEGORY_META = {
    post:       { label: '게시글',  icon: '📝' },
    comment:    { label: '댓글',    icon: '💬' },
    vote:       { label: '추천',    icon: '👍' },
    hot:        { label: '화제글',  icon: '🔥' },
    battle:     { label: '전쟁터',  icon: '⚔️' },
    azit:       { label: '아지트',  icon: '🏠' },
    playground: { label: '놀이터',  icon: '🎮' },
    view:       { label: '조회수',  icon: '👀' },
    manner:     { label: '매너',    icon: '😊' },
    level:      { label: '레벨',    icon: '⭐' },
    special:    { label: '특별',    icon: '✨' },
}

const faction   = computed(() => props.profileUser?.political_type ?? 'moderate')
const config    = computed(() => factionConfig[faction.value] ?? factionConfig.moderate)
const pillClass = computed(() => getFactionPillClass(faction.value, props.profileUser?.level ?? 1))

const mannerColor = computed(() => {
    const s = props.profileUser?.manner_score ?? 0
    if (s >= 80) return 'text-emerald-400'
    if (s >= 50) return 'text-yellow-400'
    return 'text-red-400'
})

// 뱃지 카테고리별 그룹화 (취득 순서 유지)
const badgeGroups = computed(() => {
    const groups = {}
    for (const badge of props.badges) {
        if (!groups[badge.category]) groups[badge.category] = []
        groups[badge.category].push(badge)
    }
    // CATEGORY_META 순서대로 정렬
    return Object.entries(CATEGORY_META)
        .filter(([key]) => groups[key]?.length)
        .map(([key, meta]) => ({ key, ...meta, badges: groups[key] }))
})

// 툴팁: 호버 중인 뱃지 key
const hoveredBadge = ref(null)

const formatDate = (dateStr) => {
    if (!dateStr) return ''
    const d   = new Date(dateStr)
    const pad = (n) => String(n).padStart(2, '0')
    return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`
}
</script>

<template>
<Head :title="seoTitle">
  <meta name="description" :content="seoDesc" />
  <meta property="og:title" :content="`${seoTitle} — 폴릿`" />
  <meta property="og:description" :content="seoDesc" />
</Head>
    <div class="max-w-4xl mx-auto px-4 py-10">

        <!-- Profile Header Card -->
        <div :class="['bg-gradient-to-br rounded-2xl border border-gray-200 dark:border-slate-800 overflow-hidden mb-6', config.gradient]">
            <div class="p-6 sm:p-8">
                <div class="flex flex-col sm:flex-row sm:items-center gap-5">

                    <!-- Level Emoji Box -->
                    <div class="flex-shrink-0">
                        <div :class="['w-20 h-20 rounded-2xl flex items-center justify-center text-4xl font-bold', pillClass]">
                            {{ profileUser.level_emoji }}
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <span :class="['inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-base font-bold', pillClass]">
                                {{ profileUser.level_emoji }}
                                {{ profileUser.nickname }}
                            </span>
                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                Lv.{{ profileUser.level }} · {{ profileUser.level_name }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full border"
                                :style="{ color: profileUser.faction_color, borderColor: profileUser.faction_color + '50', backgroundColor: profileUser.faction_color + '18' }">
                                {{ config.emoji }} {{ profileUser.faction_label }}
                            </span>
                            <span v-if="profileUser.title" class="text-xs bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30 px-2 py-0.5 rounded-full font-semibold">
                                🏅 {{ profileUser.title }}
                            </span>
                            <span :class="['text-xs font-semibold', mannerColor]">
                                매너 {{ profileUser.manner_score ?? 100 }}점
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">가입일 {{ profileUser.joined_at }}</p>
                    </div>

                    <!-- Own Profile Button -->
                    <div v-if="isOwnProfile" class="flex-shrink-0">
                        <Link
                            href="/profile"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-sm font-semibold transition-colors"
                        >
                            내 전체 프로필 →
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="grid grid-cols-3 border-t border-gray-200 dark:border-slate-800">
                <div class="py-4 text-center">
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.post_count.toLocaleString() }}</div>
                    <div class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">게시글</div>
                </div>
                <div class="py-4 text-center border-x border-gray-200 dark:border-slate-800">
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.comment_count.toLocaleString() }}</div>
                    <div class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">댓글</div>
                </div>
                <div class="py-4 text-center">
                    <div class="text-2xl font-bold text-emerald-500">{{ stats.vote_up_count.toLocaleString() }}</div>
                    <div class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">받은 추천</div>
                </div>
            </div>
        </div>

        <!-- Posts List -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800">
                <h2 class="text-slate-900 dark:text-white font-semibold flex items-center gap-2">
                    📝 작성 게시글
                    <span class="text-xs text-slate-400 dark:text-slate-500 font-normal">총 {{ stats.post_count.toLocaleString() }}개</span>
                </h2>
            </div>
            <div v-if="(posts.data ?? []).length === 0" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500 text-sm">
                작성한 게시글이 없습니다.
            </div>
            <div v-else class="divide-y divide-gray-100 dark:divide-slate-800">
                <Link
                    v-for="post in posts.data"
                    :key="post.id"
                    :href="`/boards/${post.board?.slug}/posts/${post.id}`"
                    class="flex items-center justify-between px-6 py-3 hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors group"
                >
                    <div class="min-w-0">
                        <p class="text-sm text-slate-700 dark:text-slate-200 group-hover:text-slate-900 dark:group-hover:text-white line-clamp-1 transition-colors">{{ post.title }}</p>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ post.board?.name }}</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-slate-400 dark:text-slate-500 shrink-0 ml-4">
                        <span class="text-emerald-500">👍 {{ post.vote_up_count }}</span>
                        <span>💬 {{ post.comment_count }}</span>
                        <span class="hidden sm:inline">{{ formatDate(post.created_at) }}</span>
                    </div>
                </Link>
            </div>
            <div v-if="(posts.meta?.last_page ?? posts.last_page ?? 1) > 1" class="px-6 py-4 border-t border-gray-100 dark:border-slate-800">
                <Pagination :links="posts.links" />
            </div>
        </div>

        <!-- Comments List -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800">
                <h2 class="text-slate-900 dark:text-white font-semibold flex items-center gap-2">
                    💬 작성 댓글
                    <span class="text-xs text-slate-400 dark:text-slate-500 font-normal">총 {{ stats.comment_count.toLocaleString() }}개</span>
                </h2>
            </div>
            <div v-if="(comments.data ?? []).length === 0" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500 text-sm">
                작성한 댓글이 없습니다.
            </div>
            <div v-else class="divide-y divide-gray-100 dark:divide-slate-800">
                <Link
                    v-for="comment in comments.data"
                    :key="comment.id"
                    :href="`/boards/${comment.post?.board?.slug}/posts/${comment.post_id}`"
                    class="block px-6 py-3 hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors group"
                >
                    <p class="text-sm text-slate-600 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white line-clamp-2 transition-colors">{{ comment.content }}</p>
                    <div class="flex items-center gap-2 mt-1 text-xs text-slate-400 dark:text-slate-500">
                        <span class="line-clamp-1 min-w-0">{{ comment.post?.title }}</span>
                        <span class="shrink-0">· {{ formatDate(comment.created_at) }}</span>
                    </div>
                </Link>
            </div>
            <div v-if="(comments.meta?.last_page ?? comments.last_page ?? 1) > 1" class="px-6 py-4 border-t border-gray-100 dark:border-slate-800">
                <Pagination :links="comments.links" />
            </div>
        </div>

        <!-- ── 획득 뱃지 ────────────────────────────────────────────── -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 mt-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 flex items-center justify-between">
                <h2 class="text-slate-900 dark:text-white font-semibold flex items-center gap-2">
                    🏅 획득 뱃지
                    <span class="text-xs text-slate-400 dark:text-slate-500 font-normal">{{ badges.length }}개</span>
                </h2>
            </div>

            <!-- 뱃지 없음 -->
            <div v-if="badges.length === 0" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500 text-sm">
                아직 획득한 뱃지가 없습니다.
            </div>

            <!-- 카테고리별 뱃지 목록 -->
            <div v-else class="divide-y divide-gray-100 dark:divide-slate-800">
                <div v-for="group in badgeGroups" :key="group.key" class="px-6 py-4">
                    <!-- 카테고리 헤더 -->
                    <p class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">
                        {{ group.icon }} {{ group.label }}
                    </p>
                    <!-- 뱃지 그리드 -->
                    <div class="flex flex-wrap gap-2">
                        <div
                            v-for="badge in group.badges"
                            :key="badge.key"
                            class="relative"
                            @mouseenter="hoveredBadge = badge.key"
                            @mouseleave="hoveredBadge = null"
                        >
                            <!-- 뱃지 칩 -->
                            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-gray-100 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 cursor-default select-none transition-colors hover:border-violet-400 dark:hover:border-violet-500">
                                <span class="text-base leading-none">{{ badge.emoji }}</span>
                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ badge.name }}</span>
                            </div>

                            <!-- 툴팁 -->
                            <Transition
                                enter-active-class="transition-all duration-150 ease-out"
                                enter-from-class="opacity-0 scale-95 translate-y-1"
                                enter-to-class="opacity-100 scale-100 translate-y-0"
                                leave-active-class="transition-all duration-100 ease-in"
                                leave-from-class="opacity-100"
                                leave-to-class="opacity-0"
                            >
                                <div
                                    v-if="hoveredBadge === badge.key"
                                    class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 z-50 w-44 bg-slate-900 dark:bg-slate-700 text-white rounded-xl px-3 py-2 shadow-xl pointer-events-none"
                                >
                                    <div class="absolute top-full left-1/2 -translate-x-1/2 w-0 h-0 border-x-4 border-x-transparent border-t-4 border-t-slate-900 dark:border-t-slate-700"></div>
                                    <p class="text-xs font-bold mb-0.5">{{ badge.emoji }} {{ badge.name }}</p>
                                    <p class="text-[11px] text-slate-300 leading-snug">{{ badge.desc }}</p>
                                    <p class="text-[10px] text-slate-400 mt-1">{{ badge.awarded_at }} 획득</p>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
