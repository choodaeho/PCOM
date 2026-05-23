<script setup>
import { ref } from 'vue'
import { useForm, router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
defineOptions({ layout: AdminLayout })

const props = defineProps({ users: Object, filters: Object })

const suspendModal = ref({ open: false, user: null })
const suspendForm = useForm({ days: 7, reason: '' })

const openSuspend = (user) => { suspendModal.value = { open: true, user } }
const submitSuspend = () => {
  suspendForm.post(`/admin/users/${suspendModal.value.user.id}/suspend`, {
    onSuccess: () => { suspendModal.value = { open: false, user: null }; suspendForm.reset() }
  })
}

const search = ref(props.filters?.search ?? '')
const factionFilter = ref(props.filters?.faction ?? '')
const statusFilter = ref(props.filters?.status ?? '')

const doSearch = () => {
  router.get('/admin/users', {
    search: search.value,
    faction: factionFilter.value,
    status: statusFilter.value,
  }, { preserveState: true, replace: true })
}

const ban = (user) => {
  if (confirm(`${user.nickname}을(를) 영구 차단하시겠습니까?\n이 작업은 되돌릴 수 없습니다.`)) {
    router.post(`/admin/users/${user.id}/ban`, { reason: '관리자 판단' })
  }
}
const activate = (user) => {
  if (confirm(`${user.nickname}의 계정을 활성화하시겠습니까?`)) {
    router.post(`/admin/users/${user.id}/activate`)
  }
}

const factionLabel = { conservative: '보수', moderate: '중도', progressive: '진보' }
const statusLabel = { active: '활성', suspended: '정지', banned: '차단', pending: '미완료' }
</script>

<template>
  <div class="p-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-white">사용자 관리</h1>
      <span class="text-slate-400 text-sm">총 {{ users.total?.toLocaleString() ?? 0 }}명</span>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-3 mb-6">
      <input
        v-model="search"
        @keyup.enter="doSearch"
        placeholder="이메일 또는 닉네임 검색"
        class="bg-slate-800 border border-slate-700 text-slate-100 placeholder-slate-500 rounded-lg px-3 py-2 text-sm flex-1 min-w-48 focus:outline-none focus:border-violet-500"
      />
      <select
        v-model="factionFilter"
        @change="doSearch"
        class="bg-slate-800 border border-slate-700 text-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-violet-500"
      >
        <option value="">전체 진영</option>
        <option value="conservative">보수</option>
        <option value="moderate">중도</option>
        <option value="progressive">진보</option>
      </select>
      <select
        v-model="statusFilter"
        @change="doSearch"
        class="bg-slate-800 border border-slate-700 text-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-violet-500"
      >
        <option value="">전체 상태</option>
        <option value="active">활성</option>
        <option value="suspended">정지</option>
        <option value="banned">차단</option>
        <option value="pending">미완료</option>
      </select>
      <button
        @click="doSearch"
        class="bg-violet-600 hover:bg-violet-500 text-white px-4 py-2 rounded-lg text-sm transition-colors"
      >
        검색
      </button>
    </div>

    <!-- Table -->
    <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
      <div v-if="!users.data?.length" class="py-16 text-center text-slate-500 text-sm">
        검색 결과가 없습니다.
      </div>
      <table v-else class="w-full text-sm">
        <thead class="bg-slate-800/50">
          <tr class="text-slate-400 text-left text-xs uppercase tracking-wider">
            <th class="px-4 py-3 font-medium">ID</th>
            <th class="px-4 py-3 font-medium">닉네임</th>
            <th class="px-4 py-3 font-medium">이메일</th>
            <th class="px-4 py-3 font-medium">진영</th>
            <th class="px-4 py-3 font-medium">상태</th>
            <th class="px-4 py-3 font-medium">가입일</th>
            <th class="px-4 py-3 font-medium">게시글</th>
            <th class="px-4 py-3 font-medium">액션</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
          <tr v-for="user in users.data" :key="user.id" class="hover:bg-slate-800/30 transition-colors">
            <td class="px-4 py-3 text-slate-500 text-xs">{{ user.id }}</td>
            <td class="px-4 py-3 text-slate-100 font-medium">{{ user.nickname ?? user.name }}</td>
            <td class="px-4 py-3 text-slate-400">{{ user.email }}</td>
            <td class="px-4 py-3">
              <span :class="['text-xs px-2 py-0.5 rounded-full font-medium', {
                'bg-red-500/20 text-red-400': user.political_type === 'conservative',
                'bg-violet-500/20 text-violet-400': user.political_type === 'moderate',
                'bg-blue-500/20 text-blue-400': user.political_type === 'progressive',
                'bg-slate-700 text-slate-500': !user.political_type,
              }]">
                {{ factionLabel[user.political_type] ?? '미완료' }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span :class="['text-xs px-2 py-0.5 rounded-full font-medium', {
                'bg-emerald-500/20 text-emerald-400': user.status === 'active',
                'bg-orange-500/20 text-orange-400': user.status === 'suspended',
                'bg-red-500/20 text-red-400': user.status === 'banned',
                'bg-slate-700 text-slate-500': user.status === 'pending' || !user.status,
              }]">
                {{ statusLabel[user.status] ?? user.status }}
              </span>
            </td>
            <td class="px-4 py-3 text-slate-400 text-xs whitespace-nowrap">
              {{ new Date(user.created_at).toLocaleDateString('ko') }}
            </td>
            <td class="px-4 py-3 text-slate-400 text-center">{{ user.posts_count ?? 0 }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <button
                  v-if="user.status !== 'banned'"
                  @click="openSuspend(user)"
                  class="text-orange-400 hover:text-orange-300 text-xs transition-colors"
                >
                  정지
                </button>
                <button
                  v-if="user.status !== 'banned'"
                  @click="ban(user)"
                  class="text-red-400 hover:text-red-300 text-xs transition-colors"
                >
                  차단
                </button>
                <button
                  v-if="user.status !== 'active'"
                  @click="activate(user)"
                  class="text-emerald-400 hover:text-emerald-300 text-xs transition-colors"
                >
                  활성화
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="users.last_page > 1" class="mt-4 flex items-center justify-between text-sm">
      <p class="text-slate-500 text-xs">
        {{ users.from }}–{{ users.to }} / {{ users.total }}명
      </p>
      <div class="flex gap-1">
        <Link
          v-for="link in users.links"
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

    <!-- Suspend Modal -->
    <div v-if="suspendModal.open" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
      <div class="bg-slate-900 rounded-xl p-6 w-full max-w-md border border-slate-700 shadow-2xl">
        <h3 class="text-white font-bold text-lg mb-1">일시 정지</h3>
        <p class="text-slate-400 text-sm mb-5">
          <span class="text-violet-400 font-medium">{{ suspendModal.user?.nickname ?? suspendModal.user?.name }}</span> 계정을 정지합니다.
        </p>

        <label class="block text-slate-400 text-xs mb-1.5 font-medium uppercase tracking-wider">정지 기간 (일)</label>
        <input
          v-model="suspendForm.days"
          type="number"
          min="1"
          max="365"
          class="w-full bg-slate-800 border border-slate-700 text-slate-100 rounded-lg px-3 py-2 mb-4 focus:outline-none focus:border-violet-500"
        />

        <label class="block text-slate-400 text-xs mb-1.5 font-medium uppercase tracking-wider">사유</label>
        <textarea
          v-model="suspendForm.reason"
          rows="3"
          placeholder="정지 사유를 입력하세요"
          class="w-full bg-slate-800 border border-slate-700 text-slate-100 placeholder-slate-600 rounded-lg px-3 py-2 mb-5 resize-none focus:outline-none focus:border-violet-500"
        ></textarea>

        <p v-if="suspendForm.errors.reason" class="text-red-400 text-xs mb-3">{{ suspendForm.errors.reason }}</p>

        <div class="flex gap-3 justify-end">
          <button
            @click="suspendModal.open = false; suspendForm.reset()"
            class="text-slate-400 hover:text-white text-sm px-4 py-2 transition-colors"
          >
            취소
          </button>
          <button
            @click="submitSuspend"
            :disabled="suspendForm.processing"
            class="bg-orange-600 hover:bg-orange-500 disabled:opacity-50 text-white text-sm px-5 py-2 rounded-lg transition-colors"
          >
            {{ suspendForm.processing ? '처리 중...' : '정지 적용' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
