<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  grouped: { type: Object, default: () => ({}) },
  types:   { type: Object, default: () => ({}) },
})

const tabs = computed(() => Object.entries(props.types).map(([key, label]) => ({ key, label })))
const activeTab = ref(Object.keys(props.types)[0] ?? 'terms')

const docs = computed(() => props.grouped[activeTab.value] ?? [])

const setCurrent = (id) => {
  router.post(`/admin/legal/${id}/set-current`, {}, { preserveScroll: true })
}
const destroy = (id) => {
  if (!confirm('이 버전을 삭제하시겠습니까?')) return
  router.delete(`/admin/legal/${id}`, { preserveScroll: true })
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-black text-white">약관 관리</h1>
        <p class="text-slate-400 text-sm mt-1">이용약관, 개인정보처리방침, 청소년보호정책의 버전을 관리합니다.</p>
      </div>
      <Link
        :href="`/admin/legal/create?type=${activeTab}`"
        class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-sm font-semibold transition-colors"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        새 버전 추가
      </Link>
    </div>

    <!-- 탭 -->
    <div class="flex bg-slate-900 border border-slate-800 rounded-xl p-1 mb-6 gap-1 w-fit">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        @click="activeTab = tab.key"
        :class="[
          'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
          activeTab === tab.key
            ? 'bg-violet-600 text-white'
            : 'text-slate-400 hover:text-white'
        ]"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- 문서 목록 -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
      <div v-if="docs.length === 0" class="py-16 text-center text-slate-500">
        <p class="text-3xl mb-2">📄</p>
        <p>등록된 버전이 없습니다.</p>
      </div>

      <table v-else class="w-full">
        <thead>
          <tr class="border-b border-slate-800 bg-slate-800/50">
            <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">버전</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">제목</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">시행일</th>
            <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">상태</th>
            <th class="px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider text-right">관리</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
          <tr v-for="doc in docs" :key="doc.id" class="hover:bg-slate-800/30 transition-colors">
            <td class="px-5 py-4">
              <span class="font-mono text-sm font-bold text-violet-400">{{ doc.version }}</span>
            </td>
            <td class="px-5 py-4 text-sm text-slate-200">{{ doc.title }}</td>
            <td class="px-5 py-4 text-sm text-slate-400">{{ doc.effective_date }}</td>
            <td class="px-5 py-4">
              <span v-if="doc.is_current"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-900/40 text-emerald-400 border border-emerald-800/50">
                ✅ 현재 적용
              </span>
              <span v-else class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-slate-800 text-slate-500">
                이전 버전
              </span>
            </td>
            <td class="px-5 py-4">
              <div class="flex items-center justify-end gap-2">
                <button
                  v-if="!doc.is_current"
                  @click="setCurrent(doc.id)"
                  class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-900/30 hover:bg-emerald-900/50 text-emerald-400 border border-emerald-800/50 transition-colors"
                >
                  현재로 설정
                </button>
                <Link
                  :href="`/admin/legal/${doc.id}/edit`"
                  class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors"
                >
                  수정
                </Link>
                <button
                  v-if="!doc.is_current"
                  @click="destroy(doc.id)"
                  class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-900/30 hover:bg-rose-900/50 text-rose-400 border border-rose-800/50 transition-colors"
                >
                  삭제
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
