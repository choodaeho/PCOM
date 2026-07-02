<script setup>
import { computed } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import QuillEditor from '@/Components/QuillEditor.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  board: Object,  // { id, name, slug, board_type }
})

const page = usePage()
const user = computed(() => page.props.auth?.user)

const form = useForm({
  title:   '',
  content: '',
})

const submit = () => {
  form.post(`/boards/${props.board.slug}/posts`, {
    onSuccess: () => form.reset(),
  })
}
</script>

<template>
  <div class="max-w-4xl mx-auto px-4 py-10">

    <!-- 헤더 -->
    <div class="mb-6">
      <div class="flex items-center gap-2 text-sm text-slate-500 mb-3">
        <Link href="/boards" class="hover:text-slate-600 dark:hover:text-slate-300 transition-colors">커뮤니티</Link>
        <span>/</span>
        <Link :href="`/boards/${board.slug}`" class="hover:text-slate-600 dark:hover:text-slate-300 transition-colors">{{ board.name }}</Link>
        <span>/</span>
        <span class="text-slate-500 dark:text-slate-400">글쓰기</span>
      </div>
      <h1 class="text-2xl font-black text-slate-900 dark:text-white">새 글 작성</h1>
    </div>

    <!-- 폼 -->
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
      <form @submit.prevent="submit">

        <!-- 제목 -->
        <div class="p-5 border-b border-gray-200 dark:border-slate-800">
          <input
            v-model="form.title"
            type="text"
            placeholder="제목을 입력하세요"
            maxlength="300"
            required
            class="w-full bg-transparent text-slate-900 dark:text-white text-xl font-bold placeholder-slate-300 dark:placeholder-slate-600 focus:outline-none"
            :class="{ 'text-red-400': form.errors.title }"
          />
          <div class="flex items-center justify-between mt-1.5">
            <p v-if="form.errors.title" class="text-xs text-red-400">{{ form.errors.title }}</p>
            <span class="text-xs text-slate-400 dark:text-slate-600 ml-auto">{{ form.title.length }}/300</span>
          </div>
        </div>

        <!-- 본문 (Quill 에디터) -->
        <div class="p-4 border-b border-gray-200 dark:border-slate-800">
          <QuillEditor
            v-model="form.content"
            placeholder="내용을 입력하세요... 이미지는 클립보드 붙여넣기 또는 툴바 버튼으로 추가할 수 있습니다."
            min-height="380px"
          />
          <p v-if="form.errors.content" class="text-xs text-red-400 mt-2">{{ form.errors.content }}</p>
        </div>

        <!-- 하단 버튼 -->
        <div class="p-5 flex items-center justify-end flex-wrap gap-3 bg-white dark:bg-slate-900">

          <!-- 액션 버튼 -->
          <div class="flex items-center gap-3">
            <Link
              :href="`/boards/${board.slug}`"
              class="px-5 py-2.5 rounded-xl bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 border border-gray-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-sm font-medium transition-colors"
            >취소</Link>
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

    <!-- 작성 가이드 -->
    <div class="mt-5 bg-gray-50 dark:bg-slate-900/50 border border-gray-200 dark:border-slate-800 rounded-xl p-4">
      <h3 class="text-xs font-semibold text-slate-500 dark:text-slate-400 mb-2 flex items-center gap-2">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        작성 가이드
      </h3>
      <ul class="text-xs text-slate-400 dark:text-slate-500 space-y-1.5">
        <li>· 이미지: 툴바 📷 버튼 또는 클립보드 붙여넣기 (최대 5MB / JPEG·PNG·GIF·WebP)</li>
        <li>· 동영상: 툴바 🎬 버튼 → YouTube·Vimeo URL 입력</li>
        <li>· 이모지: 툴바 😊 버튼으로 다양한 이모지 삽입 가능</li>
        <li>· 타인을 비방하거나 혐오 표현이 포함된 글은 삭제될 수 있습니다.</li>
      </ul>
    </div>
  </div>
</template>
