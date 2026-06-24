<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  requests: { type: Object, required: true },
  filters:  { type: Object, default: () => ({}) },
})

const statusFilter = ref(props.filters.status ?? '')
const typeFilter   = ref(props.filters.type   ?? '')

const applyFilter = () => {
  router.get('/admin/deletion-requests', {
    status: statusFilter.value || undefined,
    type:   typeFilter.value   || undefined,
  }, { preserveState: true })
}

const statusOptions = [
  { value: '',           label: '전체' },
  { value: 'pending',    label: '대기 중' },
  { value: 'completed',  label: '삭제 처리' },
  { value: 'rejected',   label: '복구(기각)' },
]

const typeOptions = [
  { value: '',              label: '전체 유형' },
  { value: 'personal_info', label: '개인정보' },
  { value: 'defamation',    label: '명예훼손' },
  { value: 'copyright',     label: '저작권' },
  { value: 'post',          label: '게시물' },
  { value: 'comment',       label: '댓글' },
  { value: 'other',         label: '기타' },
]

const statusBadge = (status) => ({
  pending:   'bg-amber-900/30 text-amber-400 border-amber-800/50',
  completed: 'bg-rose-900/30 text-rose-400 border-rose-800/50',
  rejected:  'bg-slate-800 text-slate-400 border-slate-700',
}[status] ?? 'bg-slate-800 text-slate-400')

const statusLabel = (status) => ({
  pending:   '⏳ 대기 중',
  completed: '🗑️ 삭제 처리',
  rejected:  '↩️ 복구(기각)',
}[status] ?? status)

const blindedBadge = (r) => {
  if (!r.blinded_type) return null
  return r.related_post ? '🚫 게시물 블라인드' : '🚫 댓글 블라인드'
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-black text-white">삭제요청 관리</h1>
        <p class="text-slate-400 text-sm mt-1">
          사용자가 제출한 삭제요청 목록. 요청 접수 시 대상 게시물이 자동 블라인드 처리됩니다.
        </p>
      </div>
    </div>

    <!-- 필터 -->
    <div class="flex items-center gap-3 mb-5 flex-wrap">
      <select
        v-model="statusFilter"
        @change="applyFilter"
        class="px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-violet-500"
      >
        <option v-for="o in statusOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
      </select>
      <select
        v-model="typeFilter"
        @change="applyFilter"
        class="px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-violet-500"
      >
        <option v-for="o in typeOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
      </select>
      <span class="ml-auto text-xs text-slate-500">총 {{ requests.total ?? 0 }}건</span>
    </div>

    <!-- 테이블 -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
      <div v-if="!requests.data?.length" class="py-16 text-center text-slate-500">
        <p class="text-3xl mb-2">📭</p>
        <p>접수된 삭제요청이 없습니다.</p>
      </div>

      <table v-else class="w-full">
        <thead>
          <tr class="border-b border-slate-800 bg-slate-800/50">
            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase">접수일</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase">유형</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase">신청자</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase">대상 게시물</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase">블라인드</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase">상태</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
          <tr
            v-for="req in requests.data"
            :key="req.id"
            class="hover:bg-slate-800/30 transition-colors"
          >
            <td class="px-4 py-4 text-xs text-slate-400 whitespace-nowrap">{{ req.created_at }}</td>
            <td class="px-4 py-4">
              <span class="text-xs font-semibold text-violet-400">{{ req.type_label }}</span>
            </td>
            <td class="px-4 py-4">
              <p class="text-sm text-slate-200">{{ req.requester_name }}</p>
              <p class="text-xs text-slate-500">{{ req.requester_email }}</p>
            </td>
            <td class="px-4 py-4 max-w-xs">
              <p v-if="req.related_post" class="text-sm text-slate-300 truncate">{{ req.related_post.title }}</p>
              <a
                v-else-if="req.target_url"
                :href="req.target_url"
                target="_blank"
                class="text-xs text-violet-400 hover:underline truncate block max-w-[200px]"
              >{{ req.target_url }}</a>
              <span v-else class="text-xs text-slate-600">-</span>
            </td>
            <td class="px-4 py-4">
              <span v-if="blindedBadge(req)" class="text-xs text-amber-400">{{ blindedBadge(req) }}</span>
              <span v-else class="text-xs text-slate-600">-</span>
            </td>
            <td class="px-4 py-4">
              <span :class="['text-xs font-semibold px-2.5 py-1 rounded-full border', statusBadge(req.status)]">
                {{ statusLabel(req.status) }}
              </span>
            </td>
            <td class="px-4 py-4 text-right">
              <Link
                :href="`/admin/deletion-requests/${req.id}`"
                class="text-xs px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors"
              >
                상세
              </Link>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- 페이지네이션 -->
      <div v-if="requests.last_page > 1" class="px-4 py-3 border-t border-slate-800 flex justify-center gap-1">
        <Link
          v-for="link in requests.links"
          :key="link.label"
          :href="link.url ?? ''"
          v-html="link.label"
          :class="[
            'px-3 py-1 rounded-lg text-sm',
            link.active
              ? 'bg-violet-600 text-white'
              : link.url
                ? 'bg-slate-800 text-slate-300 hover:bg-slate-700'
                : 'text-slate-600 cursor-default'
          ]"
        />
      </div>
    </div>
  </div>
</template>
