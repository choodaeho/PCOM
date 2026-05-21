<script setup>
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
defineOptions({ layout: AdminLayout })

const props = defineProps({ boards: Array })

const deleteBoard = (board) => {
  if (confirm(`"${board.name}" 게시판을 삭제하시겠습니까?\n해당 게시판의 게시글도 함께 삭제될 수 있습니다.`)) {
    router.delete(`/admin/boards/${board.id}`)
  }
}
const toggleActive = (board) => {
  router.patch(`/admin/boards/${board.id}/toggle`)
}

const factionLabel = { conservative: '보수', moderate: '중도', progressive: '진보', all: '전체' }
const categoryLabel = { azit: '아지트', battle: '전쟁터', notice: '공지' }
const factionColor = {
  conservative: 'bg-red-500/20 text-red-400',
  moderate: 'bg-violet-500/20 text-violet-400',
  progressive: 'bg-blue-500/20 text-blue-400',
  all: 'bg-slate-700 text-slate-300',
}
</script>

<template>
  <div class="p-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-white">게시판 관리</h1>
      <Link href="/admin/boards/create" class="bg-violet-600 hover:bg-violet-500 text-white text-sm px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        게시판 추가
      </Link>
    </div>

    <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
      <div v-if="!boards?.length" class="py-16 text-center text-slate-500 text-sm">
        게시판이 없습니다. 새 게시판을 추가하세요.
      </div>
      <table v-else class="w-full text-sm">
        <thead class="bg-slate-800/50">
          <tr class="text-slate-400 text-left text-xs uppercase tracking-wider">
            <th class="px-4 py-3 font-medium">게시판 이름</th>
            <th class="px-4 py-3 font-medium">슬러그</th>
            <th class="px-4 py-3 font-medium">카테고리</th>
            <th class="px-4 py-3 font-medium">대상 진영</th>
            <th class="px-4 py-3 font-medium">설명</th>
            <th class="px-4 py-3 font-medium">게시글 수</th>
            <th class="px-4 py-3 font-medium text-center">활성</th>
            <th class="px-4 py-3 font-medium">액션</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
          <tr
            v-for="board in boards"
            :key="board.id"
            :class="['hover:bg-slate-800/30 transition-colors', !board.is_active ? 'opacity-50' : '']"
          >
            <td class="px-4 py-3 text-slate-100 font-medium">{{ board.name }}</td>
            <td class="px-4 py-3">
              <code class="text-slate-400 text-xs bg-slate-800 px-2 py-0.5 rounded">{{ board.slug }}</code>
            </td>
            <td class="px-4 py-3">
              <span class="text-xs bg-slate-700 text-slate-300 px-2 py-0.5 rounded-full">
                {{ categoryLabel[board.category] ?? board.category }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span :class="['text-xs px-2 py-0.5 rounded-full font-medium', factionColor[board.allowed_faction] ?? 'bg-slate-700 text-slate-400']">
                {{ factionLabel[board.allowed_faction] ?? board.allowed_faction }}
              </span>
            </td>
            <td class="px-4 py-3 text-slate-400 max-w-xs truncate">{{ board.description ?? '-' }}</td>
            <td class="px-4 py-3 text-slate-400 text-center">{{ board.posts_count ?? 0 }}</td>
            <td class="px-4 py-3 text-center">
              <button @click="toggleActive(board)" :class="['w-10 h-5 rounded-full transition-colors relative inline-block', board.is_active ? 'bg-violet-600' : 'bg-slate-700']">
                <span :class="['absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-all', board.is_active ? 'left-5' : 'left-0.5']"></span>
              </button>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <Link :href="`/admin/boards/${board.id}/edit`" class="text-violet-400 hover:text-violet-300 text-xs transition-colors">수정</Link>
                <button @click="deleteBoard(board)" class="text-red-400 hover:text-red-300 text-xs transition-colors">삭제</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
