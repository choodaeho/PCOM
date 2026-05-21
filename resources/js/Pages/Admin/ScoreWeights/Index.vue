<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
defineOptions({ layout: AdminLayout })

const props = defineProps({ weights: Array })

// Local copy for inline editing
const localWeights = ref(props.weights.map(w => ({ ...w })))
const saving = ref({})

const saveWeight = (weight) => {
  saving.value[weight.id] = true
  router.patch(`/admin/score-weights/${weight.id}`, {
    weight: parseFloat(weight.weight),
    is_active: weight.is_active,
  }, {
    preserveState: true,
    onFinish: () => { saving.value[weight.id] = false }
  })
}

const toggleActive = (weight) => {
  weight.is_active = !weight.is_active
  saveWeight(weight)
}

const actionTypeLabel = {
  post_create: '게시글 작성',
  post_upvote: '게시글 추천 받음',
  post_downvote: '게시글 비추천 받음',
  comment_create: '댓글 작성',
  comment_upvote: '댓글 추천 받음',
  report_received: '신고 누적',
  report_confirmed: '신고 확정',
  daily_login: '일일 접속',
  post_view: '게시글 조회수',
}
</script>

<template>
  <div class="p-6">
    <div class="flex items-start justify-between mb-2">
      <h1 class="text-2xl font-bold text-white">점수 가중치 관리</h1>
    </div>

    <!-- Warning -->
    <div class="bg-orange-500/10 border border-orange-500/30 rounded-xl px-4 py-3 mb-6 flex items-center gap-3">
      <svg class="w-5 h-5 text-orange-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
      </svg>
      <p class="text-orange-300 text-sm">
        <span class="font-semibold">주의:</span> 가중치 변경 시 즉시 캐시가 초기화되며, 다음 집계 사이클부터 새 값이 적용됩니다.
      </p>
    </div>

    <!-- Table -->
    <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-800/50">
          <tr class="text-slate-400 text-left text-xs uppercase tracking-wider">
            <th class="px-4 py-3 font-medium">액션 유형</th>
            <th class="px-4 py-3 font-medium">설명</th>
            <th class="px-4 py-3 font-medium w-40">가중치 (weight)</th>
            <th class="px-4 py-3 font-medium w-24 text-center">활성화</th>
            <th class="px-4 py-3 font-medium w-24 text-center">저장</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
          <tr
            v-for="weight in localWeights"
            :key="weight.id"
            :class="['transition-colors', weight.is_active ? 'hover:bg-slate-800/30' : 'opacity-50 hover:bg-slate-800/20']"
          >
            <td class="px-4 py-3">
              <code class="text-violet-400 text-xs bg-violet-500/10 px-2 py-0.5 rounded">{{ weight.action_type }}</code>
            </td>
            <td class="px-4 py-3 text-slate-300">
              {{ weight.description ?? actionTypeLabel[weight.action_type] ?? '-' }}
            </td>
            <td class="px-4 py-3">
              <input
                v-model="weight.weight"
                type="number"
                step="0.1"
                min="-10"
                max="100"
                class="w-full bg-slate-800 border border-slate-700 text-slate-100 rounded-lg px-3 py-1.5 text-sm text-right focus:outline-none focus:border-violet-500 tabular-nums"
              />
            </td>
            <td class="px-4 py-3 text-center">
              <button
                type="button"
                @click="toggleActive(weight)"
                :class="['w-10 h-5 rounded-full transition-colors relative inline-block',
                  weight.is_active ? 'bg-violet-600' : 'bg-slate-700']"
              >
                <span :class="['absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-all',
                  weight.is_active ? 'left-5' : 'left-0.5']"></span>
              </button>
            </td>
            <td class="px-4 py-3 text-center">
              <button
                @click="saveWeight(weight)"
                :disabled="saving[weight.id]"
                class="bg-slate-700 hover:bg-violet-600 disabled:opacity-50 text-white text-xs px-3 py-1.5 rounded-lg transition-colors"
              >
                {{ saving[weight.id] ? '저장 중' : '저장' }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <p class="text-slate-600 text-xs mt-3">
      * 양수: 점수 가산 / 음수: 점수 감산 / 0: 효과 없음
    </p>
  </div>
</template>
