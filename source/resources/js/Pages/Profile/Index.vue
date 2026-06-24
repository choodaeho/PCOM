<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import FactionBadge from '@/Components/FactionBadge.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
    recentPosts:    { type: Array,  default: () => [] },
    recentComments: { type: Array,  default: () => [] },
    stats: {
        type: Object,
        default: () => ({ post_count: 0, comment_count: 0, vote_up_count: 0 }),
    },
    postStats:     { type: Object, default: () => ({}) },
    postsByBoard:  { type: Array,  default: () => [] },
    activityByDay: { type: Array,  default: () => [] },
    reportCount:   { type: Number, default: 0 },
    reportedCount: { type: Number, default: 0 },
    badges:        { type: Array,  default: () => [] },
})

const page = usePage()
const user = computed(() => page.props.auth.user)

const factionConfig = {
    conservative: { color: '#E24B4A', bgGradient: 'from-red-50 to-white dark:from-red-900/30 dark:to-slate-900' },
    moderate:     { color: '#7F77DD', bgGradient: 'from-violet-50 to-white dark:from-violet-900/30 dark:to-slate-900' },
    progressive:  { color: '#378ADD', bgGradient: 'from-blue-50 to-white dark:from-blue-900/30 dark:to-slate-900' },
}

const mannerColor = computed(() => {
    const score = user.value?.manner_score ?? 0
    if (score >= 80) return 'text-emerald-400'
    if (score >= 50) return 'text-yellow-400'
    return 'text-red-400'
})

const mannerLabel = computed(() => {
    const score = user.value?.manner_score ?? 0
    if (score >= 80) return '🌟 매너 유저'
    if (score >= 50) return '😐 보통'
    return '⚠️ 주의 필요'
})

// 히트맵: 최근 30일 날짜별 활동
const activityMap = computed(() => {
    const map = {}
    props.activityByDay.forEach(r => { map[r.date] = r.count })
    return map
})

const last30Days = computed(() => {
    const days = []
    for (let i = 29; i >= 0; i--) {
        const d = new Date()
        d.setDate(d.getDate() - i)
        const key = d.toISOString().slice(0, 10)
        days.push({ date: key, count: activityMap.value[key] ?? 0 })
    }
    return days
})

const maxActivity = computed(() => Math.max(1, ...last30Days.value.map(d => d.count)))

const heatColor = (count) => {
    if (count === 0) return 'bg-gray-100 dark:bg-slate-800'
    const ratio = count / maxActivity.value
    if (ratio > 0.75) return 'bg-violet-600'
    if (ratio > 0.5)  return 'bg-violet-500'
    if (ratio > 0.25) return 'bg-violet-400'
    return 'bg-violet-300'
}

// 배지 전체 목록 (순서 고정, 30개)
const ALL_BADGES = [
    // 게시글
    { key: 'first_post',    emoji: '🌱', name: '첫 걸음',       desc: '게시글 1개 이상 작성' },
    { key: 'writer_10',     emoji: '📝', name: '활발한 작가',   desc: '게시글 10개 이상 작성' },
    { key: 'writer_50',     emoji: '✍️', name: '다작가',        desc: '게시글 50개 이상 작성' },
    { key: 'writer_200',    emoji: '📚', name: '대작가',        desc: '게시글 200개 이상 작성' },
    { key: 'writer_500',    emoji: '🏛️', name: '전설적 작가',   desc: '게시글 500개 이상 작성' },
    // 댓글
    { key: 'first_comment', emoji: '🗨️', name: '첫 댓글',       desc: '댓글 1개 이상 작성' },
    { key: 'commenter_10',  emoji: '💭', name: '수다 시작',     desc: '댓글 10개 이상 작성' },
    { key: 'commenter_100', emoji: '💬', name: '수다쟁이',      desc: '댓글 100개 이상 작성' },
    { key: 'commenter_500', emoji: '🗣️', name: '댓글 마스터',   desc: '댓글 500개 이상 작성' },
    // 추천
    { key: 'popular_100',   emoji: '👍', name: '인기인',        desc: '받은 추천 합계 100개 이상' },
    { key: 'popular_500',   emoji: '⭐', name: '스타',          desc: '받은 추천 합계 500개 이상' },
    { key: 'popular_1000',  emoji: '🌟', name: '슈퍼스타',      desc: '받은 추천 합계 1,000개 이상' },
    { key: 'hot_post',      emoji: '🔥', name: '화제의 글',     desc: '단건 게시글 추천 30개 이상' },
    { key: 'hot_post_50',   emoji: '💥', name: '대박 글',       desc: '단건 게시글 추천 50개 이상' },
    // 전쟁터
    { key: 'warrior',       emoji: '⚔️', name: '전사',          desc: '전쟁터 게시글 20개 이상' },
    { key: 'warrior_50',    emoji: '🗡️', name: '베테랑 전사',   desc: '전쟁터 게시글 50개 이상' },
    { key: 'warrior_100',   emoji: '🏹', name: '전쟁의 신',     desc: '전쟁터 게시글 100개 이상' },
    // 아지트
    { key: 'azit_10',       emoji: '🏠', name: '아지트 단골',   desc: '아지트 게시글 10개 이상' },
    { key: 'azit_50',       emoji: '🛡️', name: '아지트 수호자', desc: '아지트 게시글 50개 이상' },
    // 놀이터
    { key: 'playground_10', emoji: '🎮', name: '놀이터 단골',   desc: '놀이터 게시글 10개 이상' },
    { key: 'playground_50', emoji: '🎡', name: '놀이터 챔피언', desc: '놀이터 게시글 50개 이상' },
    // 매너
    { key: 'manner_130',    emoji: '😇', name: '매너리스트',    desc: '매너 점수 130점 이상' },
    { key: 'manner_150',    emoji: '🕊️', name: '매너왕',        desc: '매너 점수 150점 이상' },
    { key: 'manner_200',    emoji: '🙏', name: '성인군자',      desc: '매너 점수 200점 이상' },
    // 레벨
    { key: 'level3',        emoji: '📰', name: '논객 입문',     desc: '레벨 3 달성' },
    { key: 'level5',        emoji: '⚡', name: '중견 논객',     desc: '레벨 5 달성' },
    { key: 'level7',        emoji: '📣', name: '시니어 논객',   desc: '레벨 7 달성' },
    { key: 'legend',        emoji: '👑', name: '전설',          desc: '레벨 10 달성' },
    // 특수
    { key: 'view_star',     emoji: '👁️', name: '조회 스타',     desc: '총 조회수 10,000 이상' },
    { key: 'all_rounder',   emoji: '🌈', name: '올라운더',      desc: '아지트·전쟁터·놀이터 모두 게시글 1개 이상' },
]

// 획득한 배지 맵: key → awarded_at
const awardedBadgeMap = computed(() => {
    const map = {}
    props.badges.forEach(b => { map[b.badge_key] = b.awarded_at })
    return map
})

const badgesWithStatus = computed(() =>
    ALL_BADGES.map(b => ({
        ...b,
        awarded: Object.prototype.hasOwnProperty.call(awardedBadgeMap.value, b.key),
        awarded_at: awardedBadgeMap.value[b.key] ?? null,
    }))
)

const awardedCount = computed(() => props.badges.length)
</script>

<template>
    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- 프로필 헤더 -->
        <div
            :class="[
                'rounded-2xl border border-gray-200 dark:border-slate-800 p-8 mb-6 bg-gradient-to-br',
                user?.political_type ? factionConfig[user.political_type]?.bgGradient : 'from-gray-100 to-white dark:from-slate-800 dark:to-slate-900'
            ]"
        >
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-5">
                    <!-- 아바타 (레벨 이모지) -->
                    <div
                        class="w-20 h-20 rounded-2xl flex items-center justify-center text-4xl"
                        :style="{ backgroundColor: user?.political_type ? factionConfig[user.political_type]?.color : '#475569' }"
                    >
                        {{ user?.level_emoji ?? '🌱' }}
                    </div>

                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ user?.nickname }}</h1>
                            <FactionBadge
                                v-if="user?.political_type"
                                :type="user.political_type"
                                :label="user.faction_label"
                                :emoji="user.faction_emoji"
                            />
                        </div>
                        <p class="text-slate-500 dark:text-slate-400 text-sm mb-2">{{ user?.email }}</p>
                        <div class="flex items-center gap-2 mb-3">
                            <span class="text-xs text-slate-400 dark:text-slate-500">매너 점수</span>
                            <span :class="['text-sm font-bold', mannerColor]">{{ user?.manner_score ?? 0 }}점</span>
                            <span class="text-xs text-slate-400 dark:text-slate-500">{{ mannerLabel }}</span>
                        </div>

                        <!-- 레벨 & XP 진행 바 -->
                        <div class="pt-3 border-t border-gray-200/70 dark:border-slate-700/70">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-base leading-none">{{ user?.level_emoji ?? '🌱' }}</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">
                                    Lv.{{ user?.level ?? 1 }} {{ user?.level_name }}
                                </span>
                                <span v-if="user?.next_level_xp == null"
                                    class="text-xs font-bold text-amber-500 ml-1">👑 MAX</span>
                            </div>
                            <template v-if="user?.next_level_xp != null">
                                <div class="flex justify-between text-[11px] text-slate-400 dark:text-slate-500 mb-1.5">
                                    <span>{{ (user?.experience_points ?? 0).toLocaleString() }} XP</span>
                                    <span>다음 레벨 {{ user?.next_level_xp?.toLocaleString() }} XP</span>
                                </div>
                                <div class="w-full h-2 bg-gray-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-violet-500 rounded-full transition-all duration-700"
                                        :style="{
                                            width: `${Math.min(100, Math.max(0, Math.round(
                                                ((user.experience_points - user.current_level_xp) /
                                                (user.next_level_xp - user.current_level_xp)) * 100
                                            )))}%`
                                        }"
                                    />
                                </div>
                            </template>
                            <div v-else class="text-xs text-amber-500 font-semibold">
                                최고 레벨 달성 — {{ (user?.experience_points ?? 0).toLocaleString() }} XP
                            </div>
                        </div>
                    </div>
                </div>

                <Link
                    href="/profile/edit"
                    class="bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white text-sm px-4 py-2 rounded-lg transition-colors border border-gray-300 dark:border-slate-700"
                >
                    프로필 수정
                </Link>
            </div>
        </div>

        <!-- 활동 통계 -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 p-4 text-center">
                <div class="text-2xl font-bold text-slate-900 dark:text-white mb-1">{{ stats.post_count }}</div>
                <div class="text-xs text-slate-500 dark:text-slate-400">작성한 게시글</div>
            </div>
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 p-4 text-center">
                <div class="text-2xl font-bold text-slate-900 dark:text-white mb-1">{{ stats.comment_count }}</div>
                <div class="text-xs text-slate-500 dark:text-slate-400">작성한 댓글</div>
            </div>
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 p-4 text-center">
                <div class="text-2xl font-bold text-slate-900 dark:text-white mb-1">{{ stats.vote_up_count }}</div>
                <div class="text-xs text-slate-500 dark:text-slate-400">받은 추천</div>
            </div>
        </div>

        <!-- 최근 게시글 -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 flex items-center justify-between">
                <h2 class="text-slate-900 dark:text-white font-semibold">최근 게시글</h2>
            </div>
            <div v-if="recentPosts.length === 0" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500 text-sm">
                작성한 게시글이 없습니다.
            </div>
            <div v-else class="divide-y divide-gray-200 dark:divide-slate-800">
                <div v-for="post in recentPosts" :key="post.id" class="px-6 py-3 hover:bg-gray-100/30 dark:hover:bg-slate-800/30 transition-colors">
                    <Link
                        :href="`/boards/${post.board?.slug}/posts/${post.id}`"
                        class="flex items-center justify-between group"
                    >
                        <div>
                            <span class="text-slate-700 dark:text-slate-200 group-hover:text-slate-900 dark:group-hover:text-white text-sm transition-colors line-clamp-1">
                                {{ post.title }}
                            </span>
                            <span class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ post.board?.name }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-slate-400 dark:text-slate-500 shrink-0 ml-4">
                            <span>👍 {{ post.vote_up_count }}</span>
                            <span>💬 {{ post.comment_count }}</span>
                            <span>{{ new Date(post.created_at).toLocaleDateString('ko') }}</span>
                        </div>
                    </Link>
                </div>
            </div>
        </div>

        <!-- 최근 댓글 -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800">
                <h2 class="text-slate-900 dark:text-white font-semibold">최근 댓글</h2>
            </div>
            <div v-if="recentComments.length === 0" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500 text-sm">
                작성한 댓글이 없습니다.
            </div>
            <div v-else class="divide-y divide-gray-200 dark:divide-slate-800">
                <div v-for="comment in recentComments" :key="comment.id" class="px-6 py-3">
                    <p class="text-slate-600 dark:text-slate-300 text-sm line-clamp-2">{{ comment.content }}</p>
                    <div class="flex items-center gap-2 mt-1 text-xs text-slate-400 dark:text-slate-500">
                        <span>{{ comment.post?.title }}</span>
                        <span>·</span>
                        <span>{{ new Date(comment.created_at).toLocaleDateString('ko') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 내 배지 -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 flex items-center justify-between">
                <h2 class="text-slate-900 dark:text-white font-semibold">내 배지</h2>
                <span class="text-xs text-slate-400 dark:text-slate-500">{{ awardedCount }} / {{ ALL_BADGES.length }}개 획득</span>
            </div>
            <div class="p-5 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                <div
                    v-for="badge in badgesWithStatus"
                    :key="badge.key"
                    :class="[
                        'flex flex-col items-center gap-1.5 p-3 rounded-xl border transition-all',
                        badge.awarded
                            ? 'bg-violet-50 dark:bg-violet-900/20 border-violet-200 dark:border-violet-700'
                            : 'bg-gray-50 dark:bg-slate-800/50 border-gray-200 dark:border-slate-700 opacity-40 grayscale'
                    ]"
                    :title="badge.desc"
                >
                    <span class="text-2xl leading-none">{{ badge.emoji }}</span>
                    <span :class="[
                        'text-xs font-semibold text-center leading-tight',
                        badge.awarded ? 'text-slate-800 dark:text-slate-100' : 'text-slate-500 dark:text-slate-400'
                    ]">{{ badge.name }}</span>
                    <span class="text-[10px] text-slate-400 dark:text-slate-500 text-center leading-tight">{{ badge.desc }}</span>
                    <span v-if="badge.awarded && badge.awarded_at" class="text-[10px] text-violet-500 dark:text-violet-400 font-medium mt-0.5">
                        {{ new Date(badge.awarded_at).toLocaleDateString('ko') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- 활동 상세 통계 -->
        <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-3">
            <div v-for="item in [
                { label: '총 게시글', value: postStats?.total ?? 0, icon: '📝' },
                { label: '총 조회수', value: Number(postStats?.total_views ?? 0).toLocaleString(), icon: '👁' },
                { label: '총 추천',   value: postStats?.total_votes_up ?? 0, icon: '👍' },
                { label: '총 댓글',   value: postStats?.total_comments ?? 0, icon: '💬' },
            ]" :key="item.label"
                class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-4 text-center">
                <p class="text-xl mb-1">{{ item.icon }}</p>
                <p class="text-xl font-black text-gray-900 dark:text-white">{{ item.value }}</p>
                <p class="text-xs text-gray-500 dark:text-slate-500 mt-1">{{ item.label }}</p>
            </div>
        </div>

        <!-- 30일 히트맵 -->
        <div class="mt-4 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-5">
            <h3 class="text-sm font-bold text-gray-700 dark:text-slate-300 mb-3">최근 30일 활동</h3>
            <div class="flex gap-1 flex-wrap">
                <div v-for="day in last30Days" :key="day.date"
                    :class="['w-7 h-7 rounded cursor-default', heatColor(day.count)]"
                    :title="`${day.date}: ${day.count}건`">
                </div>
            </div>
        </div>

        <!-- 게시판별 활동 -->
        <div v-if="postsByBoard.length" class="mt-4 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-5">
            <h3 class="text-sm font-bold text-gray-700 dark:text-slate-300 mb-3">게시판별 게시글</h3>
            <div class="space-y-2">
                <div v-for="board in postsByBoard" :key="board.board_name" class="flex items-center gap-3">
                    <span class="text-xs text-gray-500 dark:text-slate-500 w-28 truncate">{{ board.board_name }}</span>
                    <div class="flex-1 bg-gray-100 dark:bg-slate-800 rounded-full h-2">
                        <div class="h-2 rounded-full bg-violet-500"
                            :style="{ width: `${Math.min(100, (board.count / (postsByBoard[0]?.count || 1)) * 100)}%` }">
                        </div>
                    </div>
                    <span class="text-xs font-bold text-gray-700 dark:text-slate-300 w-6 text-right">{{ board.count }}</span>
                </div>
            </div>
        </div>

        <!-- 신고 통계 -->
        <div class="mt-4 grid grid-cols-2 gap-3">
            <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-4 text-center">
                <p class="text-2xl font-black text-amber-500">{{ reportCount }}</p>
                <p class="text-xs text-gray-500 dark:text-slate-500 mt-1">내가 한 신고</p>
            </div>
            <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-4 text-center">
                <p class="text-2xl font-black text-rose-500">{{ reportedCount }}</p>
                <p class="text-xs text-gray-500 dark:text-slate-500 mt-1">받은 신고</p>
            </div>
        </div>
    </div>
</template>
