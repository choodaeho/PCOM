<script setup>
import { ref, computed, watch } from 'vue'
import { Link, useForm, usePage, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
    board:          { type: Object, required: true },  // { id, name, slug, board_type }
    post:           { type: Object, required: true },
    myVote:         { type: String,  default: null },   // 'up' | 'down' | null
    myCommentVotes: { type: Object,  default: () => ({}) }, // { "commentId": "up"|"down" }
})

const page = usePage()
const user = computed(() => page.props.auth?.user)

// 놀이터 게시판 여부 — false이면 진영 배경 표시
const isPlayground = computed(() => props.board?.board_type === 'playground')

// 진영별 배경 pill 색상 (PostCard와 동일)
const factionBgClass = {
    conservative: 'bg-red-500/15 border border-red-500/30 text-red-700 dark:text-red-300',
    moderate:     'bg-violet-500/15 border border-violet-500/30 text-violet-700 dark:text-violet-400',
    progressive:  'bg-blue-500/15 border border-blue-500/30 text-blue-700 dark:text-blue-300',
}

// 아바타 배경색 (진영 색상 반영)
const avatarBgClass = {
    conservative: 'bg-red-500/20 text-red-700 dark:text-red-300',
    moderate:     'bg-violet-500/20 text-violet-700 dark:text-violet-400',
    progressive:  'bg-blue-500/20 text-blue-700 dark:text-blue-300',
}

// ─────────────────────────────────────────────
// 추천/비추천 (게시글)
// ─────────────────────────────────────────────
const voteState     = ref(props.myVote ?? null)
const voteCount     = ref(props.post.vote_up_count   ?? 0)
const voteDownCount = ref(props.post.vote_down_count ?? 0)

// 페이지 리로드(back()) 후 props 변경 시 동기화
watch(() => props.myVote,              (v) => { voteState.value     = v ?? null })
watch(() => props.post.vote_up_count,  (v) => { voteCount.value     = v ?? 0   })
watch(() => props.post.vote_down_count,(v) => { voteDownCount.value = v ?? 0   })

const vote = (type) => {
    if (!user.value)    { router.visit('/login'); return }
    if (isOwner.value)  return  // 본인 글은 클릭 불가 (서버 422 방지)

    const prev = voteState.value

    // ── Optimistic update (즉각 시각 피드백) ──────────────────
    if (prev === type) {
        // 같은 타입 재클릭 → 토글 취소
        voteState.value = null
        if (type === 'up') voteCount.value     = Math.max(0, voteCount.value - 1)
        else               voteDownCount.value = Math.max(0, voteDownCount.value - 1)
    } else {
        // 신규 또는 타입 변경
        voteState.value = type
        if (type === 'up') {
            voteCount.value++
            if (prev === 'down') voteDownCount.value = Math.max(0, voteDownCount.value - 1)
        } else {
            voteDownCount.value++
            if (prev === 'up') voteCount.value = Math.max(0, voteCount.value - 1)
        }
    }

    router.post(`/posts/${props.post.id}/vote`, { vote_type: type }, {
        preserveScroll: true,
        onError: () => {
            // 실패 시 서버 값으로 롤백
            voteState.value     = props.myVote ?? null
            voteCount.value     = props.post.vote_up_count   ?? 0
            voteDownCount.value = props.post.vote_down_count ?? 0
        },
    })
}

// ─────────────────────────────────────────────
// 신고 사유 목록 (공용)
// ─────────────────────────────────────────────
const reportReasons = [
    { value: 'hate_speech',    label: '혐오 발언' },
    { value: 'misinformation', label: '허위 정보' },
    { value: 'spam',           label: '스팸/광고' },
    { value: 'obscene',        label: '음란물' },
    { value: 'other',          label: '기타' },
]

// ─────────────────────────────────────────────
// 신고 (게시글)
// ─────────────────────────────────────────────
const showReport = ref(false)
const reportForm = useForm({ reason: '', detail: '' })

const openReport = () => { reportForm.reset(); showReport.value = true }

const submitReport = () => {
    reportForm.post(`/posts/${props.post.id}/report`, {
        onSuccess: () => { showReport.value = false; reportForm.reset() },
    })
}

// ─────────────────────────────────────────────
// 댓글
// ─────────────────────────────────────────────
const commentForm = useForm({ content: '', parent_id: null })
const replyTo     = ref(null)

const submitComment = () => {
    commentForm.post(`/posts/${props.post.id}/comments`, {
        onSuccess: () => { commentForm.reset(); replyTo.value = null },
        preserveScroll: true,
    })
}

const setReply = (comment) => {
    replyTo.value         = comment
    commentForm.parent_id = comment.id
}

const cancelReply = () => {
    replyTo.value         = null
    commentForm.parent_id = null
}

// ─────────────────────────────────────────────
// 댓글/답글 추천/비추천
// ─────────────────────────────────────────────
// 서버에서 내려온 투표 상태로 초기화 → 페이지 리마운트 후에도 정확히 복원됨
const cVoteStates     = ref({ ...props.myCommentVotes })  // { [id]: 'up'|'down' }
const cVoteUpCounts   = ref({})   // { [id]: upCount }   — optimistic 카운터
const cVoteDownCounts = ref({})   // { [id]: downCount } — optimistic 카운터

// 페이지 리로드 후 서버 상태 동기화
watch(() => props.myCommentVotes, (v) => { cVoteStates.value = { ...v ?? {} } })

const getCVoteState     = (id)      => cVoteStates.value[String(id)] ?? null
const getCVoteUpCount   = (comment) => cVoteUpCounts.value[comment.id]   ?? (comment.vote_up_count   ?? 0)
const getCVoteDownCount = (comment) => cVoteDownCounts.value[comment.id] ?? (comment.vote_down_count ?? 0)

const voteComment = (comment, type) => {
    if (!user.value) { router.visit('/login'); return }
    if (comment.user_id === user.value.id) return  // 본인 댓글 방어

    const id   = comment.id
    const prev = getCVoteState(id)

    // ── Optimistic update ──────────────────────────────────────
    if (prev === type) {
        // 토글 취소
        cVoteStates.value = { ...cVoteStates.value, [id]: null }
        if (type === 'up')   cVoteUpCounts.value   = { ...cVoteUpCounts.value,   [id]: Math.max(0, getCVoteUpCount(comment)   - 1) }
        else                 cVoteDownCounts.value = { ...cVoteDownCounts.value, [id]: Math.max(0, getCVoteDownCount(comment) - 1) }
    } else {
        // 신규 or 타입 변경
        cVoteStates.value = { ...cVoteStates.value, [id]: type }
        if (type === 'up') {
            cVoteUpCounts.value = { ...cVoteUpCounts.value, [id]: getCVoteUpCount(comment) + 1 }
            if (prev === 'down') cVoteDownCounts.value = { ...cVoteDownCounts.value, [id]: Math.max(0, getCVoteDownCount(comment) - 1) }
        } else {
            cVoteDownCounts.value = { ...cVoteDownCounts.value, [id]: getCVoteDownCount(comment) + 1 }
            if (prev === 'up') cVoteUpCounts.value = { ...cVoteUpCounts.value, [id]: Math.max(0, getCVoteUpCount(comment) - 1) }
        }
    }

    router.post(`/comments/${id}/vote`, { vote_type: type }, {
        preserveScroll: true,
        onError: () => {
            // 실패 시 서버 상태로 롤백
            cVoteStates.value     = { ...props.myCommentVotes }
            cVoteUpCounts.value   = {}
            cVoteDownCounts.value = {}
        },
    })
}

// ─────────────────────────────────────────────
// 댓글/답글 신고
// ─────────────────────────────────────────────
const reportingCommentId = ref(null)
const cReportForm        = useForm({ reason: '', detail: '' })

const openCommentReport  = (id) => { cReportForm.reset(); reportingCommentId.value = id }
const closeCommentReport = ()   => { reportingCommentId.value = null; cReportForm.reset() }

const submitCommentReport = (id) => {
    cReportForm.post(`/comments/${id}/report`, {
        preserveScroll: true,
        onSuccess: () => closeCommentReport(),
    })
}

// ─────────────────────────────────────────────
// helpers
// ─────────────────────────────────────────────
const formatDate = (dateStr) => {
    if (!dateStr) return ''
    const d   = new Date(dateStr)
    const pad = (n) => String(n).padStart(2, '0')
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
}

// post.comments 는 Post::comments() 관계가 whereNull('parent_id') 이므로
// 최상위 댓글만 포함. 답글은 각 comment.replies 에 중첩되어 있음.
const topComments   = computed(() => props.post.comments ?? [])
const getReplies    = (comment) => comment.replies ?? []
const totalComments = computed(() =>
    topComments.value.reduce((sum, c) => sum + 1 + (c.replies?.length ?? 0), 0)
)

// 수정/삭제 권한: 본인 글 or 관리자
const isOwner = computed(() => user.value && user.value.id === props.post.user_id)
</script>

<template>
    <div class="max-w-3xl mx-auto px-4 py-10">
        <!-- Breadcrumb -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-500 min-w-0">
                <Link href="/boards" class="hover:text-slate-700 dark:hover:text-slate-300 transition-colors shrink-0">커뮤니티</Link>
                <span class="shrink-0">/</span>
                <Link :href="`/boards/${board.slug}`" class="hover:text-slate-700 dark:hover:text-slate-300 transition-colors shrink-0">
                    {{ board.name }}
                </Link>
                <span class="shrink-0">/</span>
                <span class="text-slate-500 dark:text-slate-400 truncate">{{ post.title }}</span>
            </div>
            <Link
                :href="`/boards/${board.slug}`"
                class="flex items-center gap-1.5 shrink-0 ml-4 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                목록
            </Link>
        </div>

        <!-- Post Article -->
        <article class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden mb-6">
            <!-- Post Header -->
            <div class="p-4 sm:p-6 border-b border-gray-200 dark:border-slate-800">
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-4 leading-snug">{{ post.title }}</h1>
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <!-- 익명 -->
                                <span v-if="post.is_anonymous" class="text-sm font-semibold text-slate-800 dark:text-slate-200">익명</span>

                                <!-- 진영 배경 pill: 레벨이모지 + 닉네임 (놀이터 제외) -->
                                <span
                                    v-else-if="!isPlayground && post.faction"
                                    :class="[
                                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-sm font-semibold',
                                        factionBgClass[post.faction] ?? 'bg-slate-200/60 border border-slate-300 text-slate-700 dark:bg-slate-700/60 dark:border-slate-600 dark:text-slate-300'
                                    ]"
                                    :title="`${post.faction === 'conservative' ? '보수' : post.faction === 'moderate' ? '중도' : '진보'} · Lv.${post.user?.level ?? 1}`"
                                >
                                    <span v-if="post.user?.level_emoji" class="leading-none text-[15px]">{{ post.user.level_emoji }}</span>
                                    {{ post.user?.nickname ?? '알 수 없음' }}
                                </span>

                                <!-- 놀이터: 무채색 pill -->
                                <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-sm font-semibold bg-slate-100 border border-slate-200 text-slate-700 dark:bg-slate-800/60 dark:border-slate-700 dark:text-slate-300">
                                    <span v-if="post.user?.level_emoji" class="leading-none text-[15px]">{{ post.user.level_emoji }}</span>
                                    {{ post.user?.nickname ?? '알 수 없음' }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-500">{{ formatDate(post.created_at) }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-500">
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
                            {{ totalComments }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Post Content (Quill HTML 렌더링) -->
            <div class="p-4 sm:p-6">
                <div class="ql-content prose-polit text-slate-700 dark:text-slate-300 leading-relaxed text-[15px] sm:text-base"
                     v-html="post.content"></div>
            </div>

            <!-- Vote / Actions -->
            <div class="px-4 sm:px-6 py-4 border-t border-gray-200 dark:border-slate-800 flex items-center justify-between flex-wrap gap-3">
                <!-- Vote buttons: 본인 글이면 비활성 안내, 타인 글이면 투표 가능 -->
                <div class="flex items-center gap-2">
                    <template v-if="user && !isOwner">
                        <!-- 추천 -->
                        <button
                            @click="vote('up')"
                            :class="[
                                'flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all border',
                                voteState === 'up'
                                    ? 'bg-emerald-500/20 border-emerald-500/50 text-emerald-400'
                                    : 'bg-gray-100 dark:bg-slate-800 border-gray-300 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:border-emerald-500/50 hover:text-emerald-400'
                            ]"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                            </svg>
                            추천 <span v-if="voteCount > 0">{{ voteCount }}</span>
                        </button>
                        <!-- 비추천 -->
                        <button
                            @click="vote('down')"
                            :class="[
                                'flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all border',
                                voteState === 'down'
                                    ? 'bg-red-500/20 border-red-500/50 text-red-400'
                                    : 'bg-gray-100 dark:bg-slate-800 border-gray-300 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:border-red-500/50 hover:text-red-400'
                            ]"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018a2 2 0 01.485.06l3.76.94m-7 10v5a2 2 0 002 2h.096c.5 0 .905-.405.905-.905 0-.714.211-1.412.608-2.006L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5" />
                            </svg>
                            비추천 <span v-if="voteDownCount > 0">{{ voteDownCount }}</span>
                        </button>
                    </template>
                    <!-- 비로그인 or 본인 글: 카운트만 표시 (read-only) -->
                    <template v-else>
                        <span class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-gray-100/50 dark:bg-slate-800/50 border border-gray-200 dark:border-slate-800 text-slate-400 dark:text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                            </svg>
                            추천 <span v-if="voteCount > 0">{{ voteCount }}</span>
                        </span>
                        <span class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-gray-100/50 dark:bg-slate-800/50 border border-gray-200 dark:border-slate-800 text-slate-400 dark:text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018a2 2 0 01.485.06l3.76.94m-7 10v5a2 2 0 002 2h.096c.5 0 .905-.405.905-.905 0-.714.211-1.412.608-2.006L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5" />
                            </svg>
                            비추천 <span v-if="voteDownCount > 0">{{ voteDownCount }}</span>
                        </span>
                    </template>
                </div>

                <div class="flex items-center gap-2">
                    <!-- 수정/삭제 (본인 게시글) -->
                    <template v-if="isOwner">
                        <Link
                            :href="`/boards/${board.slug}/posts/${post.id}/edit`"
                            class="text-xs text-slate-500 dark:text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 transition-colors px-2 py-1 rounded"
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
                        @click="openReport"
                        class="text-xs text-slate-500 dark:text-slate-500 hover:text-red-400 transition-colors flex items-center gap-1 px-2 py-1 rounded"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        신고
                    </button>
                </div>
            </div>

            <!-- Report Form (게시글) -->
            <transition name="slide">
                <div v-if="showReport" class="px-6 pb-5 border-t border-gray-200 dark:border-slate-800 pt-4">
                    <form @submit.prevent="submitReport">
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mb-3">신고 사유를 선택하세요</p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mb-3">
                            <label
                                v-for="r in reportReasons"
                                :key="r.value"
                                class="flex items-center gap-2 cursor-pointer group"
                            >
                                <input
                                    type="radio"
                                    v-model="reportForm.reason"
                                    :value="r.value"
                                    class="w-3.5 h-3.5 border-gray-400 dark:border-slate-600 bg-gray-100 dark:bg-slate-800 text-red-500 focus:ring-red-500 focus:ring-offset-0"
                                />
                                <span class="text-sm text-slate-600 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors select-none">
                                    {{ r.label }}
                                </span>
                            </label>
                        </div>
                        <p v-if="reportForm.errors.reason" class="text-xs text-red-400 mb-2">사유를 선택해주세요</p>
                        <textarea
                            v-model="reportForm.detail"
                            rows="2"
                            placeholder="상세 내용 (선택사항)..."
                            class="w-full bg-gray-100 dark:bg-slate-800 border border-gray-300 dark:border-slate-700 rounded-xl px-3 py-2.5 text-slate-700 dark:text-slate-300 placeholder-slate-400 dark:placeholder-slate-500 text-sm focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 resize-none"
                        ></textarea>
                        <div class="flex gap-2 mt-3">
                            <button
                                type="submit"
                                :disabled="reportForm.processing || !reportForm.reason"
                                class="bg-red-600 hover:bg-red-500 disabled:opacity-40 text-white text-xs font-semibold px-4 py-2 rounded-lg transition-colors"
                            >
                                신고 제출
                            </button>
                            <button
                                type="button"
                                @click="showReport = false"
                                class="bg-gray-200 dark:bg-slate-700 hover:bg-gray-300 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 text-xs font-medium px-4 py-2 rounded-lg transition-colors"
                            >
                                취소
                            </button>
                        </div>
                    </form>
                </div>
            </transition>
        </article>

        <!-- 목록으로 버튼 -->
        <div class="flex justify-center mb-6">
            <Link
                :href="`/boards/${board.slug}`"
                class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 border border-gray-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white text-sm font-medium transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                목록으로
            </Link>
        </div>

        <!-- Comments Section -->
        <section>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-slate-500 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                댓글 {{ totalComments }}개
            </h2>

            <!-- 댓글 입력 폼: 로그인 시 -->
            <div v-if="user" class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-5 mb-6">
                <div v-if="replyTo" class="flex items-center justify-between mb-3 bg-gray-100 dark:bg-slate-800 rounded-lg px-3 py-2">
                    <span class="text-xs text-violet-500 dark:text-violet-400 font-medium">
                        @{{ replyTo.user?.nickname ?? '익명' }} 에게 답글
                    </span>
                    <button @click="cancelReply" class="text-slate-500 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white transition-colors text-xs">취소</button>
                </div>
                <form @submit.prevent="submitComment">
                    <textarea
                        v-model="commentForm.content"
                        rows="3"
                        :placeholder="replyTo ? '답글을 입력하세요...' : '댓글을 입력하세요...'"
                        class="w-full bg-gray-100 dark:bg-slate-800 border border-gray-300 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-700 dark:text-slate-300 placeholder-slate-400 dark:placeholder-slate-500 text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 resize-none transition-colors"
                        required
                    ></textarea>
                    <p v-if="commentForm.errors.content" class="text-xs text-red-400 mt-1">{{ commentForm.errors.content }}</p>
                    <div class="flex items-center justify-end mt-3">
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
            <div v-else class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-5 mb-6 text-center">
                <p class="text-slate-500 dark:text-slate-500 text-sm mb-3">댓글을 작성하려면 로그인이 필요합니다</p>
                <Link href="/login" class="text-violet-500 dark:text-violet-400 hover:text-violet-600 dark:hover:text-violet-300 text-sm font-medium transition-colors">
                    로그인하기 →
                </Link>
            </div>

            <!-- Comments List -->
            <div class="space-y-3">
                <div v-if="topComments.length === 0" class="text-center py-8 text-slate-500 dark:text-slate-500 text-sm">
                    첫 번째 댓글을 작성해보세요.
                </div>

                <!-- Comment -->
                <div v-for="comment in topComments" :key="comment.id">
                    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-5">
                        <!-- Comment Header -->
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex flex-col gap-0.5">
                                <!-- 진영 배경 pill (놀이터 제외, 익명 제외) -->
                                <span
                                    v-if="!isPlayground && !comment.is_anonymous && comment.user?.political_type"
                                    :class="[
                                        'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold w-fit',
                                        factionBgClass[comment.user.political_type] ?? 'bg-slate-500/10 border border-slate-500/30 text-slate-600 dark:text-slate-400'
                                    ]"
                                    :title="`${comment.user.political_type === 'conservative' ? '보수' : comment.user.political_type === 'moderate' ? '중도' : '진보'} · Lv.${comment.user?.level ?? 1}`"
                                >
                                    <span v-if="comment.user?.level_emoji" class="leading-none">{{ comment.user.level_emoji }}</span>
                                    {{ comment.user?.nickname ?? '알 수 없음' }}
                                </span>
                                <!-- 익명 -->
                                <span v-else-if="comment.is_anonymous" class="text-sm font-semibold text-slate-800 dark:text-slate-200">익명</span>
                                <!-- 놀이터: 무채색 pill -->
                                <span v-else class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold w-fit bg-slate-100 border border-slate-200 text-slate-700 dark:bg-slate-800/60 dark:border-slate-700 dark:text-slate-300">
                                    <span v-if="comment.user?.level_emoji" class="leading-none">{{ comment.user.level_emoji }}</span>
                                    {{ comment.user?.nickname ?? '알 수 없음' }}
                                </span>
                                <p class="text-xs text-slate-500 dark:text-slate-500">{{ formatDate(comment.created_at) }}</p>
                            </div>
                            <!-- 답글 버튼: 로그인 시에만 -->
                            <button
                                v-if="user"
                                @click="setReply(comment)"
                                class="text-xs text-slate-500 dark:text-slate-500 hover:text-violet-400 transition-colors flex items-center gap-1"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                </svg>
                                답글
                            </button>
                        </div>

                        <!-- Comment Body -->
                        <p class="text-slate-700 dark:text-slate-300 text-sm leading-relaxed">{{ comment.content }}</p>

                        <!-- Comment Actions (추천/비추천/신고) -->
                        <div class="flex items-center gap-2 mt-3">
                            <!-- 추천 -->
                            <button
                                v-if="user && comment.user_id !== user.id"
                                @click="voteComment(comment, 'up')"
                                :class="[
                                    'flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium transition-all border',
                                    getCVoteState(comment.id) === 'up'
                                        ? 'bg-emerald-500/20 border-emerald-500/40 text-emerald-400'
                                        : 'bg-gray-200/60 dark:bg-slate-800/60 border-gray-300 dark:border-slate-700 text-slate-400 dark:text-slate-500 hover:border-emerald-500/40 hover:text-emerald-400'
                                ]"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                                </svg>
                                <span>{{ getCVoteUpCount(comment) > 0 ? getCVoteUpCount(comment) : '추천' }}</span>
                            </button>
                            <!-- 비추천 -->
                            <button
                                v-if="user && comment.user_id !== user.id"
                                @click="voteComment(comment, 'down')"
                                :class="[
                                    'flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium transition-all border',
                                    getCVoteState(comment.id) === 'down'
                                        ? 'bg-red-500/20 border-red-500/40 text-red-400'
                                        : 'bg-gray-200/60 dark:bg-slate-800/60 border-gray-300 dark:border-slate-700 text-slate-400 dark:text-slate-500 hover:border-red-500/40 hover:text-red-400'
                                ]"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018a2 2 0 01.485.06l3.76.94m-7 10v5a2 2 0 002 2h.096c.5 0 .905-.405.905-.905 0-.714.211-1.412.608-2.006L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5" />
                                </svg>
                                <span>{{ getCVoteDownCount(comment) > 0 ? getCVoteDownCount(comment) : '비추천' }}</span>
                            </button>
                            <!-- 신고 -->
                            <button
                                v-if="user && comment.user_id !== user.id"
                                @click="openCommentReport(comment.id)"
                                class="ml-auto flex items-center gap-1 px-2 py-1 rounded-lg text-xs text-gray-300 dark:text-slate-600 hover:text-red-400 transition-colors"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                신고
                            </button>
                        </div>

                        <!-- Comment Report Form -->
                        <transition name="slide">
                            <div v-if="reportingCommentId === comment.id" class="mt-3 bg-gray-100 dark:bg-slate-800/50 rounded-xl p-4 border border-gray-300 dark:border-slate-700">
                                <form @submit.prevent="submitCommentReport(comment.id)">
                                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mb-2">신고 사유를 선택하세요</p>
                                    <div class="grid grid-cols-2 gap-1.5 mb-3">
                                        <label
                                            v-for="r in reportReasons"
                                            :key="r.value"
                                            class="flex items-center gap-2 cursor-pointer group"
                                        >
                                            <input
                                                type="radio"
                                                v-model="cReportForm.reason"
                                                :value="r.value"
                                                class="w-3 h-3 border-gray-400 dark:border-slate-600 bg-gray-100 dark:bg-slate-700 text-red-500 focus:ring-red-500 focus:ring-offset-0"
                                            />
                                            <span class="text-xs text-slate-600 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors select-none">{{ r.label }}</span>
                                        </label>
                                    </div>
                                    <textarea
                                        v-model="cReportForm.detail"
                                        rows="2"
                                        placeholder="상세 내용 (선택사항)..."
                                        class="w-full bg-gray-100 dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 text-slate-700 dark:text-slate-300 placeholder-slate-400 dark:placeholder-slate-500 text-xs focus:outline-none focus:border-red-500 resize-none"
                                    ></textarea>
                                    <div class="flex gap-2 mt-2">
                                        <button
                                            type="submit"
                                            :disabled="cReportForm.processing || !cReportForm.reason"
                                            class="bg-red-600 hover:bg-red-500 disabled:opacity-40 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors"
                                        >
                                            신고
                                        </button>
                                        <button
                                            type="button"
                                            @click="closeCommentReport"
                                            class="bg-gray-200 dark:bg-slate-700 hover:bg-gray-300 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 text-xs px-3 py-1.5 rounded-lg transition-colors"
                                        >
                                            취소
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </transition>
                    </div>

                    <!-- Replies -->
                    <div v-if="getReplies(comment).length > 0" class="ml-8 mt-2 space-y-2">
                        <div
                            v-for="reply in getReplies(comment)"
                            :key="reply.id"
                            class="bg-gray-50 dark:bg-slate-900/60 border border-gray-200 dark:border-slate-800 rounded-xl p-4"
                        >
                            <!-- Reply Header -->
                            <div class="flex items-center gap-2 mb-2 flex-wrap">
                                <svg class="w-3.5 h-3.5 text-gray-300 dark:text-slate-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                </svg>
                                <!-- 진영 배경 pill (놀이터 제외, 익명 제외) -->
                                <span
                                    v-if="!isPlayground && !reply.is_anonymous && reply.user?.political_type"
                                    :class="[
                                        'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold',
                                        factionBgClass[reply.user.political_type] ?? 'bg-slate-500/10 border border-slate-500/30 text-slate-600 dark:text-slate-400'
                                    ]"
                                    :title="`${reply.user.political_type === 'conservative' ? '보수' : reply.user.political_type === 'moderate' ? '중도' : '진보'} · Lv.${reply.user?.level ?? 1}`"
                                >
                                    <span v-if="reply.user?.level_emoji" class="leading-none">{{ reply.user.level_emoji }}</span>
                                    {{ reply.user?.nickname ?? '알 수 없음' }}
                                </span>
                                <!-- 익명 -->
                                <span v-else-if="reply.is_anonymous" class="text-xs font-semibold text-slate-600 dark:text-slate-300">익명</span>
                                <!-- 놀이터: 무채색 pill -->
                                <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 border border-slate-200 text-slate-700 dark:bg-slate-800/60 dark:border-slate-700 dark:text-slate-300">
                                    <span v-if="reply.user?.level_emoji" class="leading-none">{{ reply.user.level_emoji }}</span>
                                    {{ reply.user?.nickname ?? '알 수 없음' }}
                                </span>
                                <span class="text-xs text-gray-300 dark:text-slate-600 ml-auto">{{ formatDate(reply.created_at) }}</span>
                            </div>

                            <!-- Reply Body -->
                            <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">{{ reply.content }}</p>

                            <!-- Reply Actions (추천/비추천/신고) -->
                            <div class="flex items-center gap-2 mt-2">
                                <button
                                    v-if="user && reply.user_id !== user.id"
                                    @click="voteComment(reply, 'up')"
                                    :class="[
                                        'flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium transition-all border',
                                        getCVoteState(reply.id) === 'up'
                                            ? 'bg-emerald-500/20 border-emerald-500/40 text-emerald-400'
                                            : 'bg-gray-200/60 dark:bg-slate-800/60 border-gray-300 dark:border-slate-700 text-slate-500 dark:text-slate-600 hover:border-emerald-500/40 hover:text-emerald-400'
                                    ]"
                                >
                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                                    </svg>
                                    <span>{{ getCVoteUpCount(reply) > 0 ? getCVoteUpCount(reply) : '추천' }}</span>
                                </button>
                                <button
                                    v-if="user && reply.user_id !== user.id"
                                    @click="voteComment(reply, 'down')"
                                    :class="[
                                        'flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium transition-all border',
                                        getCVoteState(reply.id) === 'down'
                                            ? 'bg-red-500/20 border-red-500/40 text-red-400'
                                            : 'bg-gray-200/60 dark:bg-slate-800/60 border-gray-300 dark:border-slate-700 text-slate-500 dark:text-slate-600 hover:border-red-500/40 hover:text-red-400'
                                    ]"
                                >
                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018a2 2 0 01.485.06l3.76.94m-7 10v5a2 2 0 002 2h.096c.5 0 .905-.405.905-.905 0-.714.211-1.412.608-2.006L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5" />
                                    </svg>
                                    <span>{{ getCVoteDownCount(reply) > 0 ? getCVoteDownCount(reply) : '비추천' }}</span>
                                </button>
                                <button
                                    v-if="user && reply.user_id !== user.id"
                                    @click="openCommentReport(reply.id)"
                                    class="ml-auto flex items-center gap-1 px-1.5 py-0.5 rounded-md text-xs text-gray-300 dark:text-slate-700 hover:text-red-400 transition-colors"
                                >
                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    신고
                                </button>
                            </div>

                            <!-- Reply Report Form -->
                            <transition name="slide">
                                <div v-if="reportingCommentId === reply.id" class="mt-2 bg-gray-100 dark:bg-slate-800/50 rounded-xl p-3 border border-gray-300 dark:border-slate-700">
                                    <form @submit.prevent="submitCommentReport(reply.id)">
                                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mb-2">신고 사유를 선택하세요</p>
                                        <div class="grid grid-cols-2 gap-1.5 mb-2">
                                            <label
                                                v-for="r in reportReasons"
                                                :key="r.value"
                                                class="flex items-center gap-1.5 cursor-pointer group"
                                            >
                                                <input
                                                    type="radio"
                                                    v-model="cReportForm.reason"
                                                    :value="r.value"
                                                    class="w-3 h-3 border-gray-400 dark:border-slate-600 bg-gray-100 dark:bg-slate-700 text-red-500 focus:ring-red-500 focus:ring-offset-0"
                                                />
                                                <span class="text-xs text-slate-600 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors select-none">{{ r.label }}</span>
                                            </label>
                                        </div>
                                        <textarea
                                            v-model="cReportForm.detail"
                                            rows="1"
                                            placeholder="상세 내용 (선택사항)..."
                                            class="w-full bg-gray-100 dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg px-2.5 py-1.5 text-slate-700 dark:text-slate-300 placeholder-slate-400 dark:placeholder-slate-500 text-xs focus:outline-none focus:border-red-500 resize-none"
                                        ></textarea>
                                        <div class="flex gap-2 mt-2">
                                            <button
                                                type="submit"
                                                :disabled="cReportForm.processing || !cReportForm.reason"
                                                class="bg-red-600 hover:bg-red-500 disabled:opacity-40 text-white text-xs font-semibold px-3 py-1 rounded-lg transition-colors"
                                            >
                                                신고
                                            </button>
                                            <button
                                                type="button"
                                                @click="closeCommentReport"
                                                class="bg-gray-200 dark:bg-slate-700 hover:bg-gray-300 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 text-xs px-3 py-1 rounded-lg transition-colors"
                                            >
                                                취소
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </transition>
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

/* ── Quill 콘텐츠 렌더링 (게시글 뷰어) ─────────────────── */
.ql-content { line-height: 1.85; }
.ql-content :deep(h1) { font-size: 1.75rem; font-weight: 900; color: #f1f5f9; margin: 1.2rem 0 0.5rem; }
.ql-content :deep(h2) { font-size: 1.375rem; font-weight: 800; color: #f1f5f9; margin: 1rem 0 0.4rem; }
.ql-content :deep(h3) { font-size: 1.125rem; font-weight: 700; color: #e2e8f0; margin: 0.8rem 0 0.35rem; }
.ql-content :deep(p)  { margin: 0.4rem 0; }

.ql-content :deep(strong) { color: #f1f5f9; font-weight: 700; }
.ql-content :deep(em)     { font-style: italic; }
.ql-content :deep(s)      { text-decoration: line-through; color: #64748b; }
.ql-content :deep(u)      { text-decoration: underline; }

.ql-content :deep(a) { color: #818cf8; text-decoration: underline; }
.ql-content :deep(a:hover) { color: #a78bfa; }

.ql-content :deep(blockquote) {
  border-left: 4px solid #6d28d9;
  margin: 14px 0;
  padding: 10px 16px;
  background: rgba(109,40,217,0.08);
  border-radius: 0 8px 8px 0;
  color: #a5b4fc;
}

.ql-content :deep(pre) {
  background: #0f172a;
  border: 1px solid #334155;
  border-radius: 10px;
  padding: 14px 18px;
  color: #7dd3fc;
  font-family: 'JetBrains Mono', 'Fira Code', monospace;
  font-size: 0.875rem;
  overflow-x: auto;
  margin: 12px 0;
}

.ql-content :deep(img) {
  max-width: 100%;
  border-radius: 10px;
  margin: 10px 0;
  display: block;
  cursor: zoom-in;
}

.ql-content :deep(iframe) {
  width: 100%;
  aspect-ratio: 16 / 9;
  border-radius: 10px;
  border: none;
  margin: 10px 0;
  display: block;
}

.ql-content :deep(ul), .ql-content :deep(ol) { padding-left: 1.5em; margin: 8px 0; }
.ql-content :deep(li) { margin: 4px 0; }

.ql-content :deep(.ql-align-center) { text-align: center; }
.ql-content :deep(.ql-align-right)  { text-align: right; }

/* ── 라이트 모드 Quill 콘텐츠 오버라이드 ─────────────────── */
:global(html:not(.dark)) .ql-content :deep(h1),
:global(html:not(.dark)) .ql-content :deep(h2),
:global(html:not(.dark)) .ql-content :deep(strong) { color: #0f172a; }

:global(html:not(.dark)) .ql-content :deep(h3) { color: #1e293b; }

:global(html:not(.dark)) .ql-content :deep(s) { color: #94a3b8; }

:global(html:not(.dark)) .ql-content :deep(a) { color: #7c3aed; }
:global(html:not(.dark)) .ql-content :deep(a:hover) { color: #6d28d9; }

:global(html:not(.dark)) .ql-content :deep(blockquote) {
  background: rgba(109,40,217,0.05);
  color: #6d28d9;
}

:global(html:not(.dark)) .ql-content :deep(pre) {
  background: #f8fafc;
  border-color: #e2e8f0;
  color: #0369a1;
}
</style>
