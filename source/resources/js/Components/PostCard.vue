<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    post:        { type: Object, required: true },
    boardSlug:   { type: String, required: true },  // board.slug (URL에 사용)
    showFaction: { type: Boolean, default: false },  // 놀이터 제외 게시판에서 진영 배경 노출
})

// 진영별 배경 pill 색상 (레벨이모지+닉네임을 감싸는 배경)
const factionBgClass = {
    conservative: 'bg-red-500/15 border border-red-500/30 text-red-700 dark:text-red-300',
    moderate:     'bg-violet-500/15 border border-violet-500/30 text-violet-700 dark:text-violet-400',
    progressive:  'bg-blue-500/15 border border-blue-500/30 text-blue-700 dark:text-blue-300',
}

const formatDate = (dateStr) => {
    if (!dateStr) return ''
    const d   = new Date(dateStr)
    const pad = (n) => String(n).padStart(2, '0')
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
}
</script>

<template>
    <Link
        :href="`/boards/${boardSlug}/posts/${post.id}`"
        class="group flex items-start gap-4 bg-white dark:bg-slate-900 hover:bg-gray-100/80 dark:hover:bg-slate-800/80 border border-gray-200 dark:border-slate-800 hover:border-gray-300 dark:hover:border-slate-700 rounded-xl px-5 py-4 transition-all"
    >
        <!-- Left: main content -->
        <div class="flex-1 min-w-0">
            <div class="flex items-start gap-2 mb-1.5 flex-wrap">
                <h3 class="text-[15px] font-semibold text-slate-800 dark:text-slate-200 group-hover:text-slate-900 dark:group-hover:text-white transition-colors line-clamp-2 sm:line-clamp-1 flex-1 leading-snug">
                    {{ post.title }}
                </h3>
            </div>

            <!-- Meta row -->
            <div class="flex items-center gap-2 sm:gap-3 flex-wrap text-xs text-slate-400 dark:text-slate-500">
                <div class="flex items-center gap-1.5">
                    <!-- 익명 -->
                    <span v-if="post.is_anonymous">익명</span>

                    <!-- 진영 배경 pill: 레벨이모지 + 닉네임을 진영 색으로 감쌈 -->
                    <span
                        v-else-if="showFaction && post.faction"
                        :class="[
                            'inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-medium',
                            factionBgClass[post.faction] ?? 'bg-slate-500/10 border border-slate-500/30 text-slate-600 dark:text-slate-400'
                        ]"
                        :title="`${post.faction === 'conservative' ? '보수' : post.faction === 'moderate' ? '중도' : '진보'} · Lv.${post.user?.level ?? 1}`"
                    >
                        <span v-if="post.user?.level_emoji" class="leading-none text-[13px]">{{ post.user.level_emoji }}</span>
                        {{ post.user?.nickname ?? '알 수 없음' }}
                    </span>

                    <!-- 놀이터 or faction 없음: 무채색 pill -->
                    <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-medium bg-slate-100 border border-slate-200 text-slate-700 dark:bg-slate-800/60 dark:border-slate-700 dark:text-slate-400">
                        <span v-if="post.user?.level_emoji" class="leading-none text-[13px]" :title="`Lv.${post.user?.level ?? 1}`">{{ post.user.level_emoji }}</span>
                        {{ post.user?.nickname ?? '알 수 없음' }}
                    </span>
                </div>
                <span class="text-gray-300 dark:text-slate-700">·</span>
                <span>{{ formatDate(post.created_at) }}</span>
            </div>
        </div>

        <!-- Right: stats -->
        <div class="flex items-center gap-3 text-xs text-slate-400 dark:text-slate-500 flex-shrink-0 mt-0.5">
            <!-- 추천 수 -->
            <span :class="[
                'flex items-center gap-1',
                (post.vote_up_count ?? 0) > 0 ? 'text-emerald-500' : '',
            ]">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                </svg>
                {{ post.vote_up_count ?? 0 }}
            </span>

            <!-- 댓글 수 -->
            <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                {{ post.comment_count ?? 0 }}
            </span>

            <!-- 조회수 -->
            <span class="hidden sm:flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                {{ post.view_count ?? 0 }}
            </span>
        </div>
    </Link>
</template>
