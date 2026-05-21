<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
defineOptions({ layout: AdminLayout })

const props = defineProps({ polls: Object, filters: Object })

const activeTab = ref(props.filters?.status ?? 'active')
const setTab = (tab) => {
  activeTab.value = tab
  router.get('/admin/polls', { status: tab }, { preserveState: true, replace: true })
}

const deletePoll = (poll) => {
  if (confirm(`"${poll.title}" 투표를 삭제하시겠습니까?`)) {
    router.delete(`/admin/polls/${poll.id}`)
  }
}
const toggleActive = (poll) => {
  router.patch(`/admin/polls/${poll.id}/toggle`)
}
</script>

<template>
  <div class="p-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-white">투표 관리</h1>
      <Link href="/admin/polls/create" class="bg-violet-600 hover:bg-violet-500 text-white text-sm px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        새 투표 만들기
      </Link>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 bg-slate-900 rounded-xl p-1 mb-6 w-fit border border-slate-800">
      <button
        v-for="tab in [{ key: 'active', label: '진행 중' }, { key: 'ended', label: '종료됨' }]"
        :key="tab.key"
        @click="setTab(tab.key)"
        :class="['px-4 py-2 rounded-lg text-sm font-medium transition-colors',
          activeTab === tab.key ? 'bg-violet-600 text-white' : 'text-slate-400 hover:text-white']"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Poll Cards -->
    <div v-if="!polls.data?.length" class="bg-slate-900 rounded-xl border border-slate-800 py-16 text-center text-slate-500 text-sm">
      {{ activeTab === 'active' ? '진행 중인 투표가 없습니다.' : '종료된 투표가 없습니다.' }}
    </div>

    <div class="space-y-4">
      <div
        v-for="poll in polls.data"
        :key="poll.id"
        class="bg-slate-900 rounded-xl border border-slate-800 p-5"
      >
        <div class="flex items-start justify-between gap-4">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-2">
              <span :class="['text-xs px-2 py-0.5 rounded-full font-medium',
                poll.is_active ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-700 text-slate-400']">
                {{ poll.is_active ? '진행 중' : '종료' }}
              </span>
              <span class="text-slate-600 text-xs">#{{ poll.id }}</span>
            </div>
            <h3 class="text-white font-semibold text-base mb-3">{{ poll.title }}</h3>

            <!-- Options -->
            <div class="space-y-2">
              <div v-for="opt in poll.options" :key="opt.id" class="flex items-center gap-3">
                <div class="flex-1 bg-slate-800 rounded-full h-2 overflow-hidden">
                  <div
                    class="bg-violet-500 h-full rounded-full transition-all"
                    :style="{ width: `${opt.percentage ?? 0}%` }"
                  ></div>
                </div>
                <span class="text-slate-300 text-xs w-24 truncate">{{ opt.text }}</span>
                <span class="text-slate-400 text-xs w-16 text-right tabular-nums">
                  {{ opt.votes_count ?? 0 }}표 ({{ (opt.percentage ?? 0).toFixed(1) }}%)
                </span>
              </div>
            </div>

            <div class="flex items-center gap-4 mt-3 text-xs text-slate-500">
              <span>총 {{ poll.total_votes?.toLocaleString() ?? 0 }}표</span>
              <span>{{ new Date(poll.starts_at).toLocaleDateString('ko') }} ~ {{ new Date(poll.ends_at).toLocaleDateString('ko') }}</span>
            </div>
          </div>

          <div class="flex flex-col gap-2 shrink-0">
            <Link :href="`/admin/polls/${poll.id}/edit`" class="text-violet-400 hover:text-violet-300 text-xs text-right transition-colors">수정</Link>
            <button @click="toggleActive(poll)" class="text-slate-400 hover:text-white text-xs text-right transition-colors">
              {{ poll.is_active ? '종료' : '재개' }}
            </button>
            <button @click="deletePoll(poll)" class="text-red-400 hover:text-red-300 text-xs text-right transition-colors">삭제</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="polls.last_page > 1" class="mt-6 flex justify-end gap-1">
      <Link
        v-for="link in polls.links"
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
</template>
