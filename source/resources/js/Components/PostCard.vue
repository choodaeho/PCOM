<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import UserNicknamePopup from '@/Components/UserNicknamePopup.vue'

const props = defineProps({
    post:         { type: Object,  required: true },
    boardSlug:    { type: String,  required: true },
    showFaction:  { type: Boolean, default: false },
    hotThreshold: { type: Number,  default: 5 },   // 우측 추천수 색상 기준
})

// ── 상대시간 ─────────────────────────────────────────────────────
const timeAgo = (dateStr) => {
    if (!dateStr) return ''
    const diff  = Date.now() - new Date(dateStr).getTime()
    const mins  = Math.floor(diff / 60_000)
    if (mins < 1)   return '방금 전'
    if (mins < 60)  return `${mins}분 전`
    const hours = Math.floor(mins / 60)
    if (hours < 24) return `${hours}시간 전`
    const days = Math.floor(hours / 24)
    if (days < 7)   return `${days}일 전`
    const d   = new Date(dateStr)
    const pad = (n) => String(n).padStart(2, '0')
    return `${d.getFullYear()}.${pad(d.getMonth() + 1)}.${pad(d.getDate())}`
}

// is_hot: DB 컬럼 직접 사용 (FM코리아 방식 — 한 번 등재되면 영구 유지)
const isHot    = computed(() => props.post.is_hot === true || props.post.is_hot === 1)
// 추천 수가 threshold 이상이면 우측 카운터 색상 강조 (is_hot 아직 안 됐어도 미리 표시)
const isHotish = computed(() => (props.post.vote_up_count ?? 0) >= props.hotThreshold)

// 🆕 NEW: 작성 후 1시간 이내
const isNew = computed(() => {
    if (!props.post.created_at) return false
    return Date.now() - new Date(props.post.created_at).getTime() < 60 * 60 * 1000
})

// 📌 공지 고정
const isNotice = computed(() => props.post.is_notice === true || props.post.is_notice === 1)
</script>

<template>
    <Link
        :href="`/boards/${boardSlug}/posts/${post.id}`"
        :class="[
            'group flex items-start gap-3 border rounded-xl px-4 py-4 sm:py-3.5 transition-all',
            isNotice
                ? 'bg-violet-50 dark:bg-violet-900/10 border-violet-200 dark:border-violet-800/40 hover:border-violet-300 dark:hover:border-violet-700'
                : 'bg-white dark:bg-slate-900 hover:bg-gray-50 dark:hover:bg-slate-800/60 border-gray-200 dark:border-slate-800 hover:border-gray-300 dark:hover:border-slate-700',
        ]"
    >
        <!-- Left: main content -->
        <div class="flex-1 min-w-0">

            <!-- Title row — mb-3(모바일): 메타 영역과 간격 확보 → 닉네임 오클릭 방지 -->
            <div class="flex items-start gap-2 mb-3 sm:mb-2">
                <!-- 공지 배지 -->
                <span
                    v-if="isNotice"
                    class="flex-shrink-0 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-violet-500 text-white leading-none mt-0.5"
                >
                    공지
                </span>

                <!-- 카테고리 (말머리) 태그 -->
                <span
                    v-if="post.category"
                    class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 leading-none mt-0.5 whitespace-nowrap"
                >
                    {{ post.category }}
                </span>

                <!-- 제목 + [댓글수] — flex로 분리해 제목이 길어도 [N]이 항상 표시 -->
                <div class="flex-1 flex items-baseline min-w-0">
                    <h3 class="truncate text-[15px] font-semibold text-slate-800 dark:text-slate-200 group-hover:text-slate-900 dark:group-hover:text-white transition-colors leading-snug">
                        {{ post.title }}
                    </h3>
                    <span
                        v-if="(post.comment_count ?? 0) > 0"
                        class="flex-shrink-0 text-violet-500 dark:text-violet-400 font-bold text-[15px] leading-snug ml-0.5"
                    >[{{ post.comment_count }}]</span>
                </div>
            </div>

            <!-- Meta row -->
            <div class="flex items-center gap-2 flex-wrap text-xs text-slate-400 dark:text-slate-500">

                <!-- 뱃지: HOT(is_hot 등재) / NEW(1시간 이내) -->
                <span
                    v-if="isHot && !isNotice"
                    class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-bold bg-orange-500/15 text-orange-500 dark:text-orange-400 border border-orange-400/30 leading-none"
                >
                    🔥 HOT
                </span>
                <span
                    v-else-if="isNew && !isNotice"
                    class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-500/15 text-emerald-500 dark:text-emerald-400 border border-emerald-400/30 leading-none"
                >
                    🆕 NEW
                </span>

                <!-- 작성자 pill -->
                <div class="flex items-center gap-1">
                    <span v-if="post.is_anonymous" class="text-xs font-medium text-slate-500 dark:text-slate-400">익명</span>
                    <UserNicknamePopup
                        v-else-if="showFaction && post.faction"
                        :user-id="post.user?.id"
                        :nickname="post.user?.nickname ?? '알 수 없음'"
                        :level-emoji="post.user?.level_emoji ?? '🌱'"
                        :level="post.user?.level ?? 1"
                        :political-type="post.faction"
                        :pill-sm="true"
                    />
                    <UserNicknamePopup
                        v-else
                        :user-id="post.user?.id"
                        :nickname="post.user?.nickname ?? '알 수 없음'"
                        :level-emoji="post.user?.level_emoji ?? '🌱'"
                        :level="post.user?.level ?? 1"
                        :pill-sm="true"
                    />
                </div>

                <span class="text-gray-300 dark:text-slate-700">·</span>

                <!-- 상대시간 -->
                <time :datetime="post.created_at" :title="post.created_at">{{ timeAgo(post.created_at) }}</time>
            </div>
        </div>

        <!-- Right: stats -->
        <div class="flex items-center gap-3 text-xs text-slate-400 dark:text-slate-500 flex-shrink-0 mt-0.5">
            <!-- 추천 수 (is_hot 또는 threshold 이상이면 오렌지) -->
            <span :class="[
                'flex items-center gap-1 font-medium',
                (isHot || isHotish) ? 'text-orange-500 dark:text-orange-400' : '',
            ]">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                </svg>
                {{ post.vote_up_count ?? 0 }}
            </span>

            <!-- 조회수 -->
            <span class="hidden sm:flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                {{ (post.view_count ?? 0).toLocaleString() }}
            </span>
        </div>
    </Link>
</template>
