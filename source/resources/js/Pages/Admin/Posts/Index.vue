<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
defineOptions({ layout: AdminLayout })

const props = defineProps({ posts: Object, filters: Object, boards: Array })

const search = ref(props.filters?.search ?? '')
const statusFilter = ref(props.filters?.status ?? '')
const factionFilter = ref(props.filters?.faction ?? '')
const boardFilter = ref(props.filters?.board_id ?? '')

const doSearch = () => {
  router.get('/admin/posts', {
    search: search.value,
    status: statusFilter.value,
    faction: factionFilter.value,
    board_id: boardFilter.value,
  }, { preserveState: true, replace: true })
}

const hidePost = (post) => {
  if (confirm(`"${post.title}" 게시글을 숨기시겠습니까?`)) {
    router.patch(`/admin/posts/${post.id}/hide`)
  }
}
const restorePost = (post) => {
  router.patch(`/admin/posts/${post.id}/restore`)
}
const deletePost = (post) => {
  if (confirm(`"${post.title}" 게시글을 영구 삭제하시겠습니까?\n이 작업은 되돌릴 수 없습니다.`)) {
    router.delete(`/admin/posts/${post.id}`)
  }
}

const factionLabel = { conservative: '보수', moderate: '중도', progressive: '진보' }
const statusLabel = { published: '공개', hidden: '숨김', deleted: '삭제됨', draft: '임시저장' }
const factionColor = {
  conservative: 'bg-red-500/20 text-red-400',
  moderate: 'bg-violet-500/20 text-violet-400',
  progressive: 'bg-blue-500/20 text-blue-400',
}
const statusColor = {
  published: 'bg-emerald-500/20 text-emerald-400',
  hidden: 'bg-orange-500/20 text-orange-400',
  deleted: 'bg-red-500/20 text-red-400',
  draft: 'bg-slate-700 text-slate-400',
}
</script>

<template>
  <div class="p-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-white">게시글 관리</h1>
      <span class="text-slate-400 text-sm">총 {{ posts.total?.toLocaleString() ?? 0 }}개</span>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-3 mb-6">
      <input
        v-model="search"
        @keyup.enter="doSearch"
        placeholder="제목 또는 작성자 검색"
        class="bg-slate-800 border border-slate-700 text-slate-100 placeholder-slate-500 rounded-lg px-3 py-2 text-sm flex-1 min-w-48 focus:outline-none focus:border-violet-500"
      />
      <select
        v-model="boardFilter"
        @change="doSearch"
        class="bg-slate-800 border border-slate-700 text-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-violet-500"
      >
        <option value="">전체 게시판</option>
        <option v-for="board in boards" :key="board.id" :value="board.id">{{ board.name }}</option>
      </select>
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
        <option value="published">공개</option>
        <option value="hidden">숨김</option>
        <option value="deleted">삭제됨</option>
      </select>
      <button @click="doSearch" class="bg-violet-600 hover:bg-violet-500 text-white px-4 py-2 rounded-lg text-sm transition-colors">
        검색
      </button>
    </div>

    <!-- Table -->
    <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
      <div v-if="!posts.data?.length" class="py-16 text-center text-slate-500 text-sm">
        검색 결과가 없습니다.
      </div>
      <table v-else class="w-full text-sm">
        <thead class="bg-slate-800/50">
          <tr class="text-slate-400 text-left text-xs uppercase tracking-wider">
            <th class="px-4 py-3 font-medium">제목</th>
            <th class="px-4 py-3 font-medium">게시판</th>
            <th class="px-4 py-3 font-medium">작성자</th>
            <th class="px-4 py-3 font-medium">진영</th>
            <th class="px-4 py-3 font-medium">상태</th>
            <th class="px-4 py-3 font-medium">신고</th>
            <th class="px-4 py-3 font-medium">작성일</th>
            <th class="px-4 py-3 font-medium">액션</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
          <tr v-for="post in posts.data" :key="post.id" class="hover:bg-slate-800/30 transition-colors">
            <td class="px-4 py-3 max-w-xs">
              <a :href="`/posts/${post.id}`" target="_blank"
                class="text-slate-100 hover:text-violet-400 font-medium truncate block transition-colors">
                {{ post.title }}
              </a>
            </td>
            <td class="px-4 py-3 text-slate-400 text-xs whitespace-nowrap">{{ post.board?.name ?? '-' }}</td>
            <td class="px-4 py-3 text-slate-400">{{ post.user?.nickname ?? post.user?.name ?? '-' }}</td>
            <td class="px-4 py-3">
              <span :class="['text-xs px-2 py-0.5 rounded-full font-medium', factionColor[post.faction] ?? 'bg-slate-700 text-slate-400']">
                {{ factionLabel[post.faction] ?? post.faction ?? '-' }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span :class="['text-xs px-2 py-0.5 rounded-full font-medium', statusColor[post.status] ?? 'bg-slate-700 text-slate-400']">
                {{ statusLabel[post.status] ?? post.status }}
              </span>
            </td>
            <td class="px-4 py-3 text-center">
              <span :class="['text-xs font-medium', post.reports_count > 0 ? 'text-red-400' : 'text-slate-600']">
                {{ post.reports_count ?? 0 }}
              </span>
            </td>
            <td class="px-4 py-3 text-slate-500 text-xs whitespace-nowrap">
              {{ new Date(post.created_at).toLocaleDateString('ko') }}
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <button v-if="post.status === 'published'" @click="hidePost(post)" class="text-orange-400 hover:text-orange-300 text-xs transition-colors">숨김</button>
                <button v-if="post.status === 'hidden'" @click="restorePost(post)" class="text-emerald-400 hover:text-emerald-300 text-xs transition-colors">복구</button>
                <button v-if="post.status !== 'deleted'" @click="deletePost(post)" class="text-red-400 hover:text-red-300 text-xs transition-colors">영구삭제</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="posts.last_page > 1" class="mt-4 flex items-center justify-between">
      <p class="text-slate-500 text-xs">{{ posts.from }}–{{ posts.to }} / {{ posts.total }}개</p>
      <div class="flex gap-1">
        <Link
          v-for="link in posts.links"
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
  </div>
</template>
