<script setup>
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  request: { type: Object, required: true },
})

const confirm = () => {
  if (!window.confirm('대상 콘텐츠를 삭제 처리하시겠습니까? 이 작업은 되돌릴 수 없습니다.')) return
  router.post(`/admin/deletion-requests/${props.request.id}/confirm`, {}, { preserveScroll: true })
}
const restore = () => {
  if (!window.confirm('콘텐츠를 복구하고 이 삭제 요청을 기각하시겠습니까?')) return
  router.post(`/admin/deletion-requests/${props.request.id}/restore`, {}, { preserveScroll: true })
}

const statusColor = {
  pending:   'text-amber-400 bg-amber-900/30 border-amber-800/50',
  completed: 'text-rose-400 bg-rose-900/30 border-rose-800/50',
  rejected:  'text-slate-400 bg-slate-800 border-slate-700',
}
const statusLabel = {
  pending:   '⏳ 대기 중',
  completed: '🗑️ 삭제 처리됨',
  rejected:  '↩️ 기각(복구됨)',
}
</script>

<template>
  <div>
    <div class="flex items-center gap-4 mb-6">
      <Link href="/admin/deletion-requests" class="text-slate-400 hover:text-white transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
      </Link>
      <div class="flex items-center gap-3">
        <h1 class="text-2xl font-black text-white">삭제요청 #{{ request.id }}</h1>
        <span :class="['text-xs font-semibold px-2.5 py-1 rounded-full border', statusColor[request.status] ?? 'bg-slate-800 text-slate-400']">
          {{ statusLabel[request.status] ?? request.status }}
        </span>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

      <!-- 요청 상세 -->
      <div class="lg:col-span-2 space-y-5">

        <!-- 요청 정보 -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
          <h2 class="text-sm font-bold text-slate-300 mb-4">요청 정보</h2>
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <p class="text-xs text-slate-500 mb-1">요청 유형</p>
              <p class="font-semibold text-violet-400">{{ request.type_label }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-500 mb-1">접수 일시</p>
              <p class="text-slate-300">{{ request.created_at }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-500 mb-1">신청자</p>
              <p class="text-slate-200">{{ request.requester_name }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-500 mb-1">이메일</p>
              <a :href="`mailto:${request.requester_email}`" class="text-violet-400 hover:underline">{{ request.requester_email }}</a>
            </div>
            <div class="col-span-2" v-if="request.target_url">
              <p class="text-xs text-slate-500 mb-1">대상 URL</p>
              <a :href="request.target_url" target="_blank" class="text-violet-400 hover:underline text-xs break-all">{{ request.target_url }}</a>
            </div>
          </div>
        </div>

        <!-- 삭제 사유 -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
          <h2 class="text-sm font-bold text-slate-300 mb-3">삭제 요청 사유</h2>
          <p class="text-sm text-slate-300 leading-relaxed bg-slate-800/60 rounded-xl p-4">{{ request.description }}</p>
        </div>

        <!-- 대상 게시물 -->
        <div v-if="request.related_post" class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-bold text-slate-300">블라인드된 게시물</h2>
            <span class="text-xs px-2 py-1 rounded-full bg-amber-900/30 text-amber-400 border border-amber-800/50">
              🚫 블라인드 중
            </span>
          </div>
          <div class="bg-slate-800/60 rounded-xl p-4">
            <p class="font-semibold text-slate-200 mb-2">{{ request.related_post.title }}</p>
            <p class="text-xs text-slate-400 leading-relaxed">{{ request.related_post.content }}</p>
            <p class="text-xs text-slate-600 mt-2">작성일: {{ request.related_post.created_at }}</p>
          </div>
        </div>

        <div v-else class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
          <h2 class="text-sm font-bold text-slate-400 mb-2">블라인드 대상</h2>
          <p class="text-sm text-slate-500">
            {{ request.blinded_type ? '댓글이 블라인드 처리되었습니다.' : 'URL 파싱 실패 또는 대상 게시물을 찾을 수 없습니다.' }}
          </p>
        </div>
      </div>

      <!-- 오른쪽: 처리 패널 -->
      <div class="space-y-5">

        <!-- 신청자 정보 -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
          <h2 class="text-sm font-bold text-slate-300 mb-3">신청자</h2>
          <div v-if="request.user" class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-violet-900/40 border border-violet-800/50 flex items-center justify-center text-sm font-bold text-violet-400">
              {{ request.user.nickname?.[0] }}
            </div>
            <div>
              <p class="text-sm font-semibold text-slate-200">{{ request.user.nickname }}</p>
              <p class="text-xs text-slate-500">{{ request.user.political_type }}</p>
            </div>
          </div>
          <p v-else class="text-sm text-slate-500">비회원 신청</p>
        </div>

        <!-- 처리 버튼 (pending 상태일 때만) -->
        <div v-if="request.status === 'pending'" class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-3">
          <h2 class="text-sm font-bold text-slate-300 mb-3">요청 처리</h2>

          <button
            @click="confirm"
            class="w-full py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-sm font-bold transition-colors"
          >
            🗑️ 삭제 확정
          </button>
          <p class="text-xs text-slate-500 text-center">게시물을 완전히 삭제 처리합니다.</p>

          <button
            @click="restore"
            class="w-full py-2.5 rounded-xl bg-emerald-700/50 hover:bg-emerald-700/70 border border-emerald-700/50 text-emerald-400 text-sm font-semibold transition-colors"
          >
            ↩️ 복구 (기각)
          </button>
          <p class="text-xs text-slate-500 text-center">블라인드를 해제하고 요청을 기각합니다.</p>
        </div>

        <div v-else class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
          <h2 class="text-sm font-bold text-slate-400 mb-2">처리 완료</h2>
          <p class="text-xs text-slate-500">처리 일시: {{ request.processed_at ?? '-' }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
