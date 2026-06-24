<script setup>
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
    users:    { type: Array,  default: () => [] },
    category: { type: String, default: 'posts' },
    faction:  { type: String, default: 'all' },
})

const categories = [
    {
        key:   'posts',
        label: '📝 게시글왕',
        desc:  '게시글을 가장 많이 작성한 사용자 순위입니다. 게시글을 1개 이상 작성한 사용자만 집계됩니다.',
    },
    {
        key:   'votes',
        label: '👍 인기왕',
        desc:  '작성한 게시글에서 추천을 가장 많이 받은 사용자 순위입니다. 추천을 1회 이상 받은 사용자만 집계됩니다.',
    },
    {
        key:   'manner',
        label: '😇 매너왕',
        desc:  '게시글을 1개 이상 작성한 사용자 중 매너 점수가 높은 순위입니다. 기본 100점에서 시작하며, 다른 진영으로부터 추천을 받으면 +1점, 같은 진영으로부터 비추천을 받으면 −1점, 신고·삭제 처리 확정 시 −10점입니다.',
    },
    {
        key:   'level',
        label: '🏆 레벨왕',
        desc:  '경험치(XP)를 가장 많이 쌓은 사용자 순위입니다. 전쟁터 활동은 XP가 높고(게시글 +20, 댓글 +5), 아지트는 중간(+10/+3), 놀이터는 낮습니다(+5/+2). 동일 레벨은 XP 순으로 정렬됩니다.',
    },
]

const factions = [
    { key: 'all',          label: '전체' },
    { key: 'conservative', label: '🔴 보수' },
    { key: 'moderate',     label: '🟣 중도' },
    { key: 'progressive',  label: '🔵 진보' },
]

const changeFilter = (cat, fac) => {
    router.visit('/stats/ranking', {
        data: { category: cat ?? props.category, faction: fac ?? props.faction },
        preserveScroll: true,
    })
}

const rankEmoji = (rank) => {
    if (rank === 1) return '🥇'
    if (rank === 2) return '🥈'
    if (rank === 3) return '🥉'
    return `${rank}`
}

const statValue = (user) => {
    if (props.category === 'votes')  return user.total_votes.toLocaleString() + ' 추천'
    if (props.category === 'manner') return user.manner_score + '점'
    if (props.category === 'level')  return `Lv.${user.level ?? 1} · ${(user.experience_points ?? 0).toLocaleString()} XP`
    return user.post_count.toLocaleString() + ' 게시글'
}
</script>

<template>
    <div class="max-w-3xl mx-auto px-4 py-8">
        <!-- 헤더 -->
        <div class="mb-6">
            <h1 class="text-2xl font-black text-gray-900 dark:text-white">🏆 사용자 랭킹</h1>
            <p class="text-gray-500 dark:text-slate-400 text-sm mt-1">가장 활발한 폴릿 유저들의 순위입니다.</p>
        </div>

        <!-- 카테고리 탭 -->
        <div class="flex bg-gray-100 dark:bg-slate-800 rounded-xl p-1 gap-1 mb-3">
            <button
                v-for="cat in categories"
                :key="cat.key"
                @click="changeFilter(cat.key, null)"
                :class="[
                    'flex-1 py-2 rounded-lg text-sm font-medium transition-colors',
                    category === cat.key
                        ? 'bg-white dark:bg-slate-700 text-gray-900 dark:text-white shadow-sm'
                        : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-white'
                ]"
            >
                {{ cat.label }}
            </button>
        </div>

        <!-- 현재 탭 설명 -->
        <div class="mb-4 px-1">
            <p class="text-xs text-gray-400 dark:text-slate-500 leading-relaxed">
                {{ categories.find(c => c.key === category)?.desc }}
            </p>
        </div>

        <!-- 진영 필터 -->
        <div class="flex gap-2 mb-5 flex-wrap">
            <button
                v-for="f in factions"
                :key="f.key"
                @click="changeFilter(null, f.key)"
                :class="[
                    'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors border',
                    faction === f.key
                        ? 'bg-violet-600 border-violet-600 text-white'
                        : 'bg-white dark:bg-slate-900 border-gray-200 dark:border-slate-700 text-gray-600 dark:text-slate-400 hover:border-violet-400'
                ]"
            >
                {{ f.label }}
            </button>
        </div>

        <!-- 랭킹 테이블 -->
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">

            <!-- 데이터 없음 -->
            <div v-if="!users.length" class="py-16 text-center text-gray-400 dark:text-slate-600">
                <p class="text-3xl mb-2">🏅</p>
                <p>아직 랭킹 데이터가 없습니다.</p>
            </div>

            <!-- TOP 카드 (1~3명, 유저 수에 따라 컬럼 조정) -->
            <div v-if="users.length > 0"
                :class="[
                    'grid gap-px bg-gray-200 dark:bg-slate-800 border-b border-gray-200 dark:border-slate-800',
                    users.length === 1 ? 'grid-cols-1' :
                    users.length === 2 ? 'grid-cols-2' : 'grid-cols-3'
                ]"
            >
                <div
                    v-for="user in users.slice(0, 3)"
                    :key="user.id"
                    class="bg-white dark:bg-slate-900 p-5 text-center"
                >
                    <p class="text-3xl mb-2">{{ rankEmoji(user.rank) }}</p>
                    <!-- 레벨 탭은 아바타를 더 크게 -->
                    <div
                        :class="[
                            'rounded-full mx-auto flex items-center justify-center mb-2',
                            category === 'level' ? 'w-16 h-16 text-3xl' : 'w-12 h-12 text-xl'
                        ]"
                        :style="{ backgroundColor: user.faction_color }"
                    >
                        {{ user.level_emoji ?? '🌱' }}
                    </div>
                    <p class="font-bold text-sm text-gray-900 dark:text-white truncate">{{ user.nickname }}</p>
                    <p class="text-xs mt-0.5" :style="{ color: user.faction_color }">{{ user.faction_label }}</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Lv.{{ user.level ?? 1 }} {{ user.level_name }}</p>
                    <p class="text-sm font-black text-violet-600 dark:text-violet-400 mt-2">{{ statValue(user) }}</p>
                </div>
            </div>

            <!-- 4위 이후 목록 -->
            <div class="divide-y divide-gray-100 dark:divide-slate-800">
                <div
                    v-for="user in users.slice(3)"
                    :key="user.id"
                    class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors"
                >
                    <span class="text-sm font-black text-gray-400 dark:text-slate-600 w-6 text-center">{{ user.rank }}</span>
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-base flex-shrink-0"
                        :style="{ backgroundColor: user.faction_color }">
                        {{ user.level_emoji ?? '🌱' }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm text-gray-900 dark:text-white truncate">{{ user.nickname }}</p>
                        <div class="flex items-center gap-1.5">
                            <p class="text-xs" :style="{ color: user.faction_color }">{{ user.faction_label }}</p>
                            <span class="text-xs text-slate-400 dark:text-slate-500">· Lv.{{ user.level ?? 1 }} {{ user.level_name }}</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-black text-violet-600 dark:text-violet-400">{{ statValue(user) }}</p>
                        <p class="text-xs text-gray-400 dark:text-slate-600">매너 {{ user.manner_score }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
