<script setup>
import { ref } from 'vue'
import { useForm, router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
defineOptions({ layout: AdminLayout })

const props = defineProps({ reports: Object, filters: Object })

const tabs = [
  { key: 'pending', label: '미처리' },
  { key: 'reviewed', label: '검토 중' },
  { key: 'actioned', label: '조치 완료' },
  { key: 'dismissed', label: '기각' },
]
const activeTab = ref(props.filters?.status ?? 'pending')
const setTab = (tab) => {
  activeTab.value = tab
  router.get('/admin/reports', { status: tab }, { preserveState: true, replace: true })
}

const actionModal = ref({ open: false, report: null, type: '' })
const actionForm = useForm({ admin_note: '', action: '' })

const openAction = (report, type) => {
  actionModal.value = { open: true, report, type }
  actionForm.action = type
}
const submitAction = () => {
  actionForm.post(`/admin/reports/${actionModal.value.report.id}/action`, {
    onSuccess: () => { actionModal.value = { open: false, report: null, type: '' }; actionForm.reset() }
  })
}

const reasonLabel = {
  spam: '스팸',
  hate: '혐오 표현',
  misinformation: '허위 정보',
  abuse: '욕설/비방',
  illegal: '불법 콘텐츠',
  other: '기타',
}
const targetLabel = { post: '게시글', comment: '댓글', user: '사용자' }
</script>

<template>
  <div class="p-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-white">신고 관리</h1>
      <span class="text-red-400 text-sm font-medium">
        미처리 {{ reports?.pending_count ?? 0 }}건
      </span>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 bg-slate-900 rounded-xl p-1 mb-6 w-fit border border-slate-800">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        @click="setTab(tab.key)"
        :class="['px-4 py-2 rounded-lg text-sm font-medium transition-colors',
          activeTab === tab.key
            ? 'bg-violet-600 text-white'
            : 'text-slate-400 hover:text-white']"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Table -->
    <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
      <div v-if="!reports.data?.length" class="py-16 text-center text-slate-500 text-sm">
        해당 상태의 신고가 없습니다.
      </div>
      <table v-else class="w-full text-sm">
        <thead class="bg-slate-800/50">
          <tr class="text-slate-400 text-left text-xs uppercase tracking-wider">
            <th class="px-4 py-3 font-medium">신고 유형</th>
            <th class="px-4 py-3 font-medium">대상 유형</th>
            <th class="px-4 py-3 font-medium">대상 내용</th>
            <th class="px-4 py-3 font-medium">신고자</th>
            <th class="px-4 py-3 font-medium">신고 일시</th>
            <th class="px-4 py-3 font-medium">액션</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
          <tr v-for="report in reports.data" :key="report.id" class="hover:bg-slate-800/30 transition-colors">
            <td class="px-4 py-3">
              <span class="text-xs bg-red-500/20 text-red-400 px-2 py-0.5 rounded-full font-medium">
                {{ reasonLabel[report.reason] ?? report.reason }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span class="text-xs bg-slate-700 text-slate-300 px-2 py-0.5 rounded-full">
                {{ targetLabel[report.reportable_type] ?? report.reportable_type }}
              </span>
            </td>
            <td class="px-4 py-3 text-slate-300 max-w-xs">
              <p class="truncate">{{ report.target_title ?? report.target_content }}</p>
            </td>
            <td class="px-4 py-3 text-slate-400">{{ report.reporter?.nickname ?? report.reporter?.name }}</td>
            <td class="px-4 py-3 text-slate-500 text-xs whitespace-nowrap">
              {{ new Date(report.created_at).toLocaleString('ko') }}
            </td>
            <td class="px-4 py-3">
              <div v-if="activeTab === 'pending'" class="flex items-center gap-3">
                <button @click="openAction(report, 'actioned')" class="text-orange-400 hover:text-orange-300 text-xs transition-colors">처리</button>
                <button @click="openAction(report, 'dismissed')" class="text-slate-400 hover:text-slate-300 text-xs transition-colors">기각</button>
              </div>
              <div v-else>
                <span class="text-xs text-slate-600">{{ report.admin_note ? '메모 있음' : '-' }}</span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="reports.last_page > 1" class="mt-4 flex items-center justify-between">
      <p class="text-slate-500 text-xs">{{ reports.from }}–{{ reports.to }} / {{ reports.total }}건</p>
      <div class="flex gap-1">
        <Link
          v-for="link in reports.links"
          :key="link.label"
          :href="link.url ?? ''"
          :class="['px-3 py-1.5 rounded-lg text-xs transition-colors',
            link.active ? 'bg-violet-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700',
            !link.url ? 'opacity-40 pointer-events-none' : '']"
          v-html="link.label"
          preserve-scroll
        />
      </div>
    </div>

    <!-- Action Modal -->
    <div v-if="actionModal.open" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
      <div class="bg-slate-900 rounded-xl p-6 w-full max-w-md border border-slate-700 shadow-2xl">
        <h3 class="text-white font-bold text-lg mb-1">
          {{ actionModal.type === 'actioned' ? '신고 처리' : '신고 기각' }}
        </h3>
        <p class="text-slate-400 text-sm mb-5">
          신고 ID #{{ actionModal.report?.id }} — 관리자 메모를 입력하세요.
        </p>

        <label class="block text-slate-400 text-xs mb-1.5 font-medium uppercase tracking-wider">관리자 메모</label>
        <textarea
          v-model="actionForm.admin_note"
          rows="4"
          :placeholder="actionModal.type === 'actioned' ? '조치 내용을 기록하세요 (예: 해당 게시글 숨김 처리)' : '기각 사유를 입력하세요'"
          class="w-full bg-slate-800 border border-slate-700 text-slate-100 placeholder-slate-600 rounded-lg px-3 py-2 mb-5 resize-none focus:outline-none focus:border-violet-500"
        ></textarea>

        <div class="flex gap-3 justify-end">
          <button
            @click="actionModal.open = false; actionForm.reset()"
            class="text-slate-400 hover:text-white text-sm px-4 py-2 transition-colors"
          >
            취소
          </button>
          <button
            @click="submitAction"
            :disabled="actionForm.processing"
            :class="['text-white text-sm px-5 py-2 rounded-lg transition-colors disabled:opacity-50',
              actionModal.type === 'actioned' ? 'bg-orange-600 hover:bg-orange-500' : 'bg-slate-700 hover:bg-slate-600']"
          >
            {{ actionForm.processing ? '처리 중...' : (actionModal.type === 'actioned' ? '처리 완료' : '기각') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
