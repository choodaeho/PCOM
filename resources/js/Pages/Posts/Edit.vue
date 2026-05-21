<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  post: Object,   // { id, title, content, is_anonymous, board_id }
  board: Object,  // { id, name }
})

const form = useForm({
  title: props.post.title ?? '',
  content: props.post.content ?? '',
  is_anonymous: props.post.is_anonymous ?? false,
})

const submit = () => {
  form.put(`/posts/${props.post.id}`)
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
        <Link :href="`/posts/${post.id}`" class="hover:text-slate-300 transition-colors truncate max-w-xs">{{ post.title }}</Link>
        <span>/</span>
        <span class="text-slate-400">수정</span>
      </div>
      <div class="flex items-center gap-3">
        <span class="text-xs bg-amber-500/20 text-amber-400 border border-amber-500/30 px-2.5 py-1 rounded-full font-semibold">
          ✏️ 수정 모드
        </span>
        <h1 class="text-2xl font-black text-white">게시글 수정</h1>
      </div>
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
              <span class="text-sm font-medium text-slate-300 group-hover:text-white transition-colors">익명으로 표시</span>
              <p class="text-xs text-slate-500">작성자 이름과 진영이 숨겨집니다</p>
            </div>
          </label>

          <!-- Buttons -->
          <div class="flex items-center gap-3">
            <Link
              :href="`/posts/${post.id}`"
              class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 text-sm font-medium transition-colors"
            >
              취소
            </Link>
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

    <!-- Warning -->
    <div class="mt-4 text-xs text-slate-600 text-center">
      수정 후에도 원본 작성 시간이 표시됩니다.
    </div>
  </div>
</template>
