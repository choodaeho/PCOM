<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import FactionBadge from '@/Components/FactionBadge.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
    recentPosts:    { type: Array, default: () => [] },
    recentComments: { type: Array, default: () => [] },
    stats: {
        type: Object,
        default: () => ({ post_count: 0, comment_count: 0, vote_up_count: 0 }),
    },
})

const page = usePage()
const user = computed(() => page.props.auth.user)

const factionConfig = {
    conservative: { color: '#E24B4A', bgGradient: 'from-red-900/30 to-slate-900' },
    moderate:     { color: '#7F77DD', bgGradient: 'from-violet-900/30 to-slate-900' },
    progressive:  { color: '#378ADD', bgGradient: 'from-blue-900/30 to-slate-900' },
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
</script>

<template>
    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- 프로필 헤더 -->
        <div
            :class="[
                'rounded-2xl border border-slate-800 p-8 mb-6 bg-gradient-to-br',
                user?.political_type ? factionConfig[user.political_type]?.bgGradient : 'from-slate-800 to-slate-900'
            ]"
        >
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-5">
                    <!-- 아바타 (이니셜) -->
                    <div
                        class="w-20 h-20 rounded-2xl flex items-center justify-center text-3xl font-black text-white"
                        :style="{ backgroundColor: user?.political_type ? factionConfig[user.political_type]?.color : '#475569' }"
                    >
                        {{ user?.nickname?.charAt(0)?.toUpperCase() ?? '?' }}
                    </div>

                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <h1 class="text-2xl font-bold text-white">{{ user?.nickname }}</h1>
                            <FactionBadge
                                v-if="user?.political_type"
                                :type="user.political_type"
                                :label="user.faction_label"
                                :emoji="user.faction_emoji"
                            />
                        </div>
                        <p class="text-slate-400 text-sm mb-3">{{ user?.email }}</p>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-500">매너 점수</span>
                            <span :class="['text-sm font-bold', mannerColor]">{{ user?.manner_score ?? 0 }}점</span>
                            <span class="text-xs text-slate-500">{{ mannerLabel }}</span>
                        </div>
                    </div>
                </div>

                <Link
                    href="/profile/edit"
                    class="bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-sm px-4 py-2 rounded-lg transition-colors border border-slate-700"
                >
                    프로필 수정
                </Link>
            </div>
        </div>

        <!-- 활동 통계 -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-slate-900 rounded-xl border border-slate-800 p-4 text-center">
                <div class="text-2xl font-bold text-white mb-1">{{ stats.post_count }}</div>
                <div class="text-xs text-slate-400">작성한 게시글</div>
            </div>
            <div class="bg-slate-900 rounded-xl border border-slate-800 p-4 text-center">
                <div class="text-2xl font-bold text-white mb-1">{{ stats.comment_count }}</div>
                <div class="text-xs text-slate-400">작성한 댓글</div>
            </div>
            <div class="bg-slate-900 rounded-xl border border-slate-800 p-4 text-center">
                <div class="text-2xl font-bold text-white mb-1">{{ stats.vote_up_count }}</div>
                <div class="text-xs text-slate-400">받은 추천</div>
            </div>
        </div>

        <!-- 최근 게시글 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800 mb-6">
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
                <h2 class="text-white font-semibold">최근 게시글</h2>
            </div>
            <div v-if="recentPosts.length === 0" class="px-6 py-8 text-center text-slate-500 text-sm">
                작성한 게시글이 없습니다.
            </div>
            <div v-else class="divide-y divide-slate-800">
                <div v-for="post in recentPosts" :key="post.id" class="px-6 py-3 hover:bg-slate-800/30 transition-colors">
                    <Link
                        :href="`/boards/${post.board?.slug}/posts/${post.id}`"
                        class="flex items-center justify-between group"
                    >
                        <div>
                            <span class="text-slate-200 group-hover:text-white text-sm transition-colors line-clamp-1">
                                {{ post.title }}
                            </span>
                            <span class="text-xs text-slate-500 mt-0.5">{{ post.board?.name }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-slate-500 shrink-0 ml-4">
                            <span>👍 {{ post.vote_up_count }}</span>
                            <span>💬 {{ post.comment_count }}</span>
                            <span>{{ new Date(post.created_at).toLocaleDateString('ko') }}</span>
                        </div>
                    </Link>
                </div>
            </div>
        </div>

        <!-- 최근 댓글 -->
        <div class="bg-slate-900 rounded-xl border border-slate-800">
            <div class="px-6 py-4 border-b border-slate-800">
                <h2 class="text-white font-semibold">최근 댓글</h2>
            </div>
            <div v-if="recentComments.length === 0" class="px-6 py-8 text-center text-slate-500 text-sm">
                작성한 댓글이 없습니다.
            </div>
            <div v-else class="divide-y divide-slate-800">
                <div v-for="comment in recentComments" :key="comment.id" class="px-6 py-3">
                    <p class="text-slate-300 text-sm line-clamp-2">{{ comment.content }}</p>
                    <div class="flex items-center gap-2 mt-1 text-xs text-slate-500">
                        <span>{{ comment.post?.title }}</span>
                        <span>·</span>
                        <span>{{ new Date(comment.created_at).toLocaleDateString('ko') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
