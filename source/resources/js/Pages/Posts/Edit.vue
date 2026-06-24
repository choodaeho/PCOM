<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import QuillEditor from '@/Components/QuillEditor.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  post:  Object,   // { id, title, content, is_anonymous }
  board: Object,   // { id, name, slug }
})

const form = useForm({
  title:        props.post.title        ?? '',
  content:      props.post.content      ?? '',
  is_anonymous: props.post.is_anonymous ?? false,
})

const submit = () => {
  form.put(`/boards/${props.board.slug}/posts/${props.post.id}`)
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
        <span class="text-slate-500 dark:text-slate-400">수정</span>
      </div>
      <div class="flex items-center gap-3">
        <span class="text-xs bg-amber-500/20 text-amber-400 border border-amber-500/30 px-2.5 py-1 rounded-full font-semibold">
          ✏️ 수정 모드
        </span>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white">게시글 수정</h1>
      </div>
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
            placeholder="내용을 입력하세요..."
            min-height="380px"
          />
          <p v-if="form.errors.content" class="text-xs text-red-400 mt-2">{{ form.errors.content }}</p>
        </div>

        <!-- 하단 옵션 & 버튼 -->
        <div class="p-5 flex items-center justify-between flex-wrap gap-3 bg-white dark:bg-slate-900">

          <!-- 익명 -->
          <label class="flex items-center gap-2.5 cursor-pointer group">
            <input
              type="checkbox"
              v-model="form.is_anonymous"
              class="w-4 h-4 rounded border-gray-400 dark:border-slate-600 bg-gray-100 dark:bg-slate-800 text-violet-500 focus:ring-violet-500 focus:ring-offset-slate-900"
            />
            <div>
              <span class="text-sm font-medium text-slate-600 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white transition-colors">익명으로 표시</span>
              <p class="text-xs text-slate-400 dark:text-slate-500">작성자 이름과 진영이 숨겨집니다</p>
            </div>
          </label>

          <!-- 액션 버튼 -->
          <div class="flex items-center gap-3">
            <Link
              :href="`/boards/${board.slug}/posts/${post.id}`"
              class="px-5 py-2.5 rounded-xl bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 border border-gray-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-sm font-medium transition-colors"
            >취소</Link>
            <button
              type="submit"
              :disabled="form.processing || !form.isDirty"
              class="px-6 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-sm transition-colors"
            >
              <span v-if="form.processing">저장 중...</span>
              <span v-else>수정 저장</span>
            </button>
          </div>
        </div>
      </form>
    </div>

    <p class="mt-3 text-xs text-slate-400 dark:text-slate-600 text-center">수정 후에도 원본 작성 시간이 표시됩니다.</p>
  </div>
</template>
