<script setup>
import { computed } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  board: Object,  // { id, name, category }
})

const page = usePage()
const user = computed(() => page.props.auth?.user)

const form = useForm({
  title: '',
  content: '',
  is_anonymous: false,
  board_id: props.board.id,
})

const submit = () => {
  form.post(`/boards/${props.board.id}/posts`, {
    onSuccess: () => form.reset(),
  })
}
</script>

<template>
  <div class="max-w-3xl mx-auto px-4 py-10">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center gap-2 text-sm text-slate-500 mb-4">
        <Link href="/boards" class="hover:text-slate-300 transition-colors">커뮤니티</Link>
        <span>/</span>
        <Link :href="`/boards/${board.id}`" class="hover:text-slate-300 transition-colors">{{ board.name }}</Link>
        <span>/</span>
        <span class="text-slate-400">글쓰기</span>
      </div>
      <h1 class="text-2xl font-black text-white">새 글 작성</h1>
    </div>

    <!-- Form -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
      <form @submit.prevent="submit">
        <!-- Title -->
        <div class="p-6 border-b border-slate-800">
          <input
            v-model="form.title"
            type="text"
            placeholder="제목을 입력하세요"
            maxlength="200"
            required
            class="w-full bg-transparent text-white text-xl font-bold placeholder-slate-600 focus:outline-none"
            :class="{ 'text-red-400': form.errors.title }"
          />
          <div class="flex items-center justify-between mt-2">
            <p v-if="form.errors.title" class="text-xs text-red-400">{{ form.errors.title }}</p>
            <span class="text-xs text-slate-600 ml-auto">{{ form.title.length }}/200</span>
          </div>
        </div>

        <!-- Content -->
        <div class="p-6 border-b border-slate-800">
          <textarea
            v-model="form.content"
            rows="16"
            placeholder="내용을 입력하세요..."
            required
            class="w-full bg-transparent text-slate-300 placeholder-slate-600 text-sm leading-relaxed focus:outline-none resize-none"
          ></textarea>
          <p v-if="form.errors.content" class="text-xs text-red-400 mt-1">{{ form.errors.content }}</p>
        </div>

        <!-- Options & Actions -->
        <div class="p-5 flex items-center justify-between flex-wrap gap-3">
          <!-- Anonymous option -->
          <label class="flex items-center gap-2 cursor-pointer group">
            <input
              type="checkbox"
              v-model="form.is_anonymous"
              class="w-4 h-4 rounded border-slate-600 bg-slate-800 text-violet-500 focus:ring-violet-500"
            />
            <div>
              <span class="text-sm font-medium text-slate-300 group-hover:text-white transition-colors">익명으로 작성</span>
              <p class="text-xs text-slate-500">작성자 이름과 진영이 숨겨집니다</p>
            </div>
          </label>

          <!-- Buttons -->
          <div class="flex items-center gap-3">
            <Link
              :href="`/boards/${board.id}`"
              class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 text-sm font-medium transition-colors"
            >
              취소
            </Link>
            <button
              type="submit"
              :disabled="form.processing"
              class="px-6 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-sm transition-colors"
            >
              <span v-if="form.processing">저장 중...</span>
              <span v-else>게시하기</span>
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- Tips -->
    <div class="mt-6 bg-slate-900/50 border border-slate-800 rounded-xl p-4">
      <h3 class="text-xs font-semibold text-slate-400 mb-2 flex items-center gap-2">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        작성 가이드
      </h3>
      <ul class="text-xs text-slate-500 space-y-1">
        <li>· 타인을 비방하거나 혐오 표현이 포함된 글은 삭제될 수 있습니다.</li>
        <li>· 허위 사실 유포는 신고 대상이 됩니다.</li>
        <li>· 건전한 정치 토론 문화를 만들어주세요.</li>
      </ul>
    </div>
  </div>
</template>
