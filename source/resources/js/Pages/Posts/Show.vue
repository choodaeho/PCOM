<script setup>
import { ref, computed } from 'vue'
import { Link, useForm, usePage, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import FactionBadge from '@/Components/FactionBadge.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
    board:  { type: Object, required: true },  // { id, name, slug, board_type }
    post:   { type: Object, required: true },
    myVote: { type: String, default: null },    // 'up' | 'down' | null
})

const page = usePage()
const user = computed(() => page.props.auth?.user)

// ─────────────────────────────────────────────
// 추천/비추천
// ─────────────────────────────────────────────
const voteState = ref(props.myVote ?? null)
const voteCount = ref(props.post.vote_up_count ?? 0)

const vote = (type) => {
    if (!user.value) {
        router.visit('/login')
        return
    }
    router.post(`/posts/${props.post.id}/vote`, { type }, {
        preserveScroll: true,
        onSuccess: (page) => {
            voteState.value = page.props.flash?.vote_state ?? voteState.value
            voteCount.value = page.props.flash?.vote_count ?? voteCount.value
        },
    })
}

// ─────────────────────────────────────────────
// 신고
// ─────────────────────────────────────────────
const showReport  = ref(false)
const reportForm  = useForm({ reason: '' })
const submitReport = () => {
    reportForm.post(`/posts/${props.post.id}/report`, {
        onSuccess: () => { showReport.value = false },
    })
}

// ─────────────────────────────────────────────
// 댓글
// ─────────────────────────────────────────────
const commentForm = useForm({ content: '', parent_id: null, is_anonymous: false })
const replyTo     = ref(null)

const submitComment = () => {
    commentForm.post(`/posts/${props.post.id}/comments`, {
        onSuccess: () => {
            commentForm.reset()
            replyTo.value = null
        },
        preserveScroll: true,
    })
}

const setReply = (comment) => {
    replyTo.value          = comment
    commentForm.parent_id  = comment.id
}

const cancelReply = () => {
    replyTo.value          = null
    commentForm.parent_id  = null
}

// ─────────────────────────────────────────────
// helpers
// ─────────────────────────────────────────────
const factionConfig = {
    conservative: { color: 'text-red-400',    bg: 'bg-red-500/10',    border: 'border-red-500/30' },
    moderate:     { color: 'text-violet-400', bg: 'bg-violet-500/10', border: 'border-violet-500/30' },
    progressive:  { color: 'text-blue-400',   bg: 'bg-blue-500/10',   border: 'border-blue-500/30' },
}

const formatDate = (dateStr) => {
    if (!dateStr) return ''
    return new Date(dateStr).toLocaleString('ko-KR', {
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit',
    })
}

const allComments  = computed(() => props.post.comments ?? [])
const topComments  = computed(() => allComments.value.filter(c => !c.parent_id))
const replies      = (parentId) => allComments.value.filter(c => c.parent_id === parentId)

// 수정/삭제 권한: 본인 글 or 관리자
const isOwner = computed(() => user.value && user.value.id === props.post.user_id)
</script>

<template>
    <div class="max-w-3xl mx-auto px-4 py-10">
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-slate-500 mb-6 min-w-0">
            <Link href="/boards" class="hover:text-slate-300 transition-colors shrink-0">커뮤니티</Link>
            <span>/</span>
            <Link :href="`/boards/${board.slug}`" class="hover:text-slate-300 transition-colors shrink-0">
                {{ board.name }}
            </Link>
            <span>/</span>
            <span class="text-slate-400 truncate">{{ post.title }}</span>
        </div>

        <!-- Post Article -->
        <article class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden mb-6">
            <!-- Post Header -->
            <div class="p-6 border-b border-slate-800">
                <h1 class="text-2xl font-black text-white mb-4 leading-snug">{{ post.title }}</h1>
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-3">
                        <!-- Avatar -->
                        <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-xs font-bold text-slate-300">
                            {{ post.is_anonymous ? '?' : (post.user?.nickname?.[0]?.toUpperCase() ?? '?') }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-slate-200">
                                    {{ post.is_anonymous ? '익명' : (post.user?.nickname ?? '알 수 없음') }}
                                </span>
                                <FactionBadge
                                    v-if="post.faction && !post.is_anonymous"
                                    :type="post.faction"
                                />
                            </div>
                            <p class="text-xs text-slate-500">{{ formatDate(post.created_at) }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-slate-500">
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            {{ post.view_count ?? 0 }}
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            {{ allComments.length }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Post Content -->
            <div class="p-6">
                <div class="text-slate-300 leading-relaxed whitespace-pre-wrap text-sm">{{ post.content }}</div>
            </div>

            <!-- Vote / Actions -->
            <div class="px-6 py-4 border-t border-slate-800 flex items-center justify-between flex-wrap gap-3">
                <!-- Vote buttons -->
                <div class="flex items-center gap-2">
                    <button
                        @click="vote('up')"
                        :class="[
                            'flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all border',
                            voteState === 'up'
                                ? 'bg-emerald-500/20 border-emerald-500/50 text-emerald-400'
                                : 'bg-slate-800 border-slate-700 text-slate-400 hover:border-emerald-500/50 hover:text-emerald-400'
                        ]"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                        </svg>
                        추천 <span v-if="voteCount > 0">{{ voteCount }}</span>
                    </button>
                    <button
                        @click="vote('down')"
                        :class="[
                            'flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all border',
                            voteState === 'down'
                                ? 'bg-red-500/20 border-red-500/50 text-red-400'
                                : 'bg-slate-800 border-slate-700 text-slate-400 hover:border-red-500/50 hover:text-red-400'
                        ]"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018a2 2 0 01.485.06l3.76.94m-7 10v5a2 2 0 002 2h.096c.5 0 .905-.405.905-.905 0-.714.211-1.412.608-2.006L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5" />
                        </svg>
                        비추천
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <!-- 수정/삭제 (본인 게시글) — URL 수정: /boards/{slug}/posts/{id}/edit -->
                    <template v-if="isOwner">
                        <Link
                            :href="`/boards/${board.slug}/posts/${post.id}/edit`"
                            class="text-xs text-slate-500 hover:text-slate-300 transition-colors px-2 py-1 rounded"
                        >
                            수정
                        </Link>
                        <Link
                            :href="`/boards/${board.slug}/posts/${post.id}`"
                            method="delete"
                            as="button"
                            class="text-xs text-red-500 hover:text-red-400 transition-colors px-2 py-1 rounded"
                        >
                            삭제
                        </Link>
                    </template>

                    <!-- 신고 (타인 게시글 + 로그인) -->
                    <button
                        v-if="user && !isOwner"
                        @click="showReport = !showReport"
                        class="text-xs text-slate-500 hover:text-red-400 transition-colors flex items-center gap-1 px-2 py-1 rounded"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        신고
                    </button>
                </div>
            </div>

            <!-- Report Form -->
            <transition name="slide">
                <div v-if="showReport" class="px-6 pb-4 border-t border-slate-800 pt-4">
                    <form @submit.prevent="submitReport">
                        <label class="block text-xs font-medium text-slate-400 mb-2">신고 사유</label>
                        <textarea
                            v-model="reportForm.reason"
                            rows="3"
                            placeholder="신고 사유를 입력하세요..."
                            class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2.5 text-slate-300 placeholder-slate-500 text-sm focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 resize-none"
                            required
                        ></textarea>
                        <p v-if="reportForm.errors.reason" class="text-xs text-red-400 mt-1">{{ reportForm.errors.reason }}</p>
                        <div class="flex gap-2 mt-3">
                            <button
                                type="submit"
                                :disabled="reportForm.processing"
                                class="bg-red-600 hover:bg-red-500 text-white text-xs font-semibold px-4 py-2 rounded-lg transition-colors disabled:opacity-50"
                            >
                                신고 제출
                            </button>
                            <button
                                type="button"
                                @click="showReport = false"
                                class="bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-medium px-4 py-2 rounded-lg transition-colors"
                            >
                                취소
                            </button>
                        </div>
                    </form>
                </div>
            </transition>
        </article>

        <!-- Comments Section -->
        <section>
            <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                댓글 {{ allComments.length }}개
            </h2>

            <!-- 댓글 입력 폼: 로그인 시 -->
            <div v-if="user" class="bg-slate-900 border border-slate-800 rounded-2xl p-5 mb-6">
                <div v-if="replyTo" class="flex items-center justify-between mb-3 bg-slate-800 rounded-lg px-3 py-2">
                    <span class="text-xs text-violet-400 font-medium">
                        @{{ replyTo.user?.nickname ?? '익명' }} 에게 답글
                    </span>
                    <button @click="cancelReply" class="text-slate-500 hover:text-white transition-colors text-xs">취소</button>
                </div>
                <form @submit.prevent="submitComment">
                    <textarea
                        v-model="commentForm.content"
                        rows="3"
                        :placeholder="replyTo ? '답글을 입력하세요...' : '댓글을 입력하세요...'"
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-slate-300 placeholder-slate-500 text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 resize-none transition-colors"
                        required
                    ></textarea>
                    <p v-if="commentForm.errors.content" class="text-xs text-red-400 mt-1">{{ commentForm.errors.content }}</p>
                    <div class="flex items-center justify-between mt-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="commentForm.is_anonymous"
                                class="w-3.5 h-3.5 rounded border-slate-600 bg-slate-800 text-violet-500 focus:ring-violet-500"
                            />
                            <span class="text-xs text-slate-400">익명으로 작성</span>
                        </label>
                        <button
                            type="submit"
                            :disabled="commentForm.processing"
                            class="bg-violet-600 hover:bg-violet-500 disabled:opacity-50 text-white font-semibold px-5 py-2 rounded-xl text-sm transition-colors"
                        >
                            등록
                        </button>
                    </div>
                </form>
            </div>

            <!-- 댓글 입력 폼: 비로그인 시 안내 -->
            <div v-else class="bg-slate-900 border border-slate-800 rounded-2xl p-5 mb-6 text-center">
                <p class="text-slate-500 text-sm mb-3">댓글을 작성하려면 로그인이 필요합니다</p>
                <Link href="/login" class="text-violet-400 hover:text-violet-300 text-sm font-medium transition-colors">
                    로그인하기 →
                </Link>
            </div>

            <!-- Comments List -->
            <div class="space-y-3">
                <div v-if="topComments.length === 0" class="text-center py-8 text-slate-500 text-sm">
                    첫 번째 댓글을 작성해보세요.
                </div>

                <div v-for="comment in topComments" :key="comment.id">
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-full bg-slate-700 flex items-center justify-center text-xs font-bold text-slate-300 flex-shrink-0">
                                    {{ comment.is_anonymous ? '?' : (comment.user?.nickname?.[0]?.toUpperCase() ?? '?') }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold text-slate-200">
                                            {{ comment.is_anonymous ? '익명' : (comment.user?.nickname ?? '알 수 없음') }}
                                        </span>
                                        <FactionBadge
                                            v-if="comment.user?.political_type && !comment.is_anonymous"
                                            :type="comment.user.political_type"
                                        />
                                    </div>
                                    <p class="text-xs text-slate-500">{{ formatDate(comment.created_at) }}</p>
                                </div>
                            </div>
                            <!-- 답글 버튼: 로그인 시에만 -->
                            <button
                                v-if="user"
                                @click="setReply(comment)"
                                class="text-xs text-slate-500 hover:text-violet-400 transition-colors flex items-center gap-1"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                </svg>
                                답글
                            </button>
                        </div>
                        <p class="text-slate-300 text-sm leading-relaxed pl-9">{{ comment.content }}</p>
                    </div>

                    <!-- Replies -->
                    <div v-if="replies(comment.id).length > 0" class="ml-8 mt-2 space-y-2">
                        <div
                            v-for="reply in replies(comment.id)"
                            :key="reply.id"
                            class="bg-slate-900/60 border border-slate-800 rounded-xl p-4"
                        >
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-3.5 h-3.5 text-slate-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                </svg>
                                <div class="w-6 h-6 rounded-full bg-slate-700 flex items-center justify-center text-xs font-bold text-slate-300 flex-shrink-0">
                                    {{ reply.is_anonymous ? '?' : (reply.user?.nickname?.[0]?.toUpperCase() ?? '?') }}
                                </div>
                                <span class="text-xs font-semibold text-slate-300">
                                    {{ reply.is_anonymous ? '익명' : (reply.user?.nickname ?? '알 수 없음') }}
                                </span>
                                <FactionBadge
                                    v-if="reply.user?.political_type && !reply.is_anonymous"
                                    :type="reply.user.political_type"
                                />
                                <span class="text-xs text-slate-600 ml-auto">{{ formatDate(reply.created_at) }}</span>
                            </div>
                            <p class="text-slate-400 text-sm leading-relaxed pl-8">{{ reply.content }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: all 0.2s ease; }
.slide-enter-from, .slide-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
