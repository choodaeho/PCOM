<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  types:       { type: Object, default: () => ({}) },
  defaultType: { type: String, default: 'terms' },
  document:    { type: Object, default: null },
})

const isEdit = computed(() => !!props.document)

const form = ref({
  type:           props.document?.type ?? props.defaultType,
  version:        props.document?.version ?? '',
  title:          props.document?.title ?? '',
  content:        props.document?.content ?? '',
  effective_date: props.document?.effective_date ?? new Date().toISOString().slice(0, 10),
  set_as_current: props.document?.is_current ?? false,
})

const errors   = ref({})
const preview  = ref(false)

const submit = () => {
  errors.value = {}
  if (isEdit.value) {
    router.put(`/admin/legal/${props.document.id}`, form.value, {
      onError: (e) => { errors.value = e },
    })
  } else {
    router.post('/admin/legal', form.value, {
      onError: (e) => { errors.value = e },
    })
  }
}
</script>

<template>
  <div>
    <!-- 헤더 -->
    <div class="flex items-center gap-4 mb-6">
      <Link href="/admin/legal" class="text-slate-400 hover:text-white transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
      </Link>
      <div>
        <h1 class="text-2xl font-black text-white">{{ isEdit ? '버전 수정' : '새 버전 추가' }}</h1>
        <p class="text-slate-400 text-sm mt-0.5">{{ isEdit ? `${document.version} 수정 중` : '새로운 약관 버전을 추가합니다.' }}</p>
      </div>
    </div>

    <form @submit.prevent="submit">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- 왼쪽: 입력 폼 -->
        <div class="space-y-5">

          <!-- 유형 + 버전 -->
          <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1.5">
                문서 유형 <span class="text-rose-400">*</span>
              </label>
              <select
                v-model="form.type"
                :disabled="isEdit"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-slate-200 focus:outline-none focus:border-violet-500 text-sm disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
              </select>
              <p v-if="errors.type" class="mt-1 text-xs text-rose-400">{{ errors.type }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">
                  버전 <span class="text-rose-400">*</span>
                </label>
                <input
                  v-model="form.version"
                  type="text"
                  placeholder="v1.0"
                  class="w-full px-4 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-slate-200 placeholder-slate-500 focus:outline-none focus:border-violet-500 text-sm font-mono"
                />
                <p v-if="errors.version" class="mt-1 text-xs text-rose-400">{{ errors.version }}</p>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1.5">
                  시행일 <span class="text-rose-400">*</span>
                </label>
                <input
                  v-model="form.effective_date"
                  type="date"
                  class="w-full px-4 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-slate-200 focus:outline-none focus:border-violet-500 text-sm"
                />
                <p v-if="errors.effective_date" class="mt-1 text-xs text-rose-400">{{ errors.effective_date }}</p>
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1.5">
                제목 <span class="text-rose-400">*</span>
              </label>
              <input
                v-model="form.title"
                type="text"
                placeholder="폴릿 서비스 이용약관"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-slate-200 placeholder-slate-500 focus:outline-none focus:border-violet-500 text-sm"
              />
              <p v-if="errors.title" class="mt-1 text-xs text-rose-400">{{ errors.title }}</p>
            </div>
          </div>

          <!-- HTML 편집기 -->
          <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <div class="flex items-center justify-between mb-2">
              <label class="text-xs font-semibold text-slate-400">
                HTML 콘텐츠 <span class="text-rose-400">*</span>
              </label>
              <button
                type="button"
                @click="preview = !preview"
                class="text-xs px-3 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors"
              >
                {{ preview ? '편집 보기' : '미리보기' }}
              </button>
            </div>
            <textarea
              v-if="!preview"
              v-model="form.content"
              rows="20"
              placeholder="<section id='article1'><h2>제1조 (목적)</h2><p>...</p></section>"
              class="w-full px-4 py-3 rounded-xl bg-slate-800 border border-slate-700 text-slate-200 placeholder-slate-600 focus:outline-none focus:border-violet-500 text-sm font-mono leading-relaxed resize-y"
            ></textarea>
            <div
              v-else
              class="min-h-[300px] px-4 py-3 rounded-xl bg-slate-800 border border-slate-700 legal-preview overflow-auto"
              v-html="form.content"
            ></div>
            <p v-if="errors.content" class="mt-1 text-xs text-rose-400">{{ errors.content }}</p>
          </div>

          <!-- 현재 버전 설정 + 제출 -->
          <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <label v-if="!isEdit" class="flex items-center gap-3 cursor-pointer mb-4">
              <input
                type="checkbox"
                v-model="form.set_as_current"
                class="w-4 h-4 rounded border-slate-600 text-violet-600 bg-slate-800 focus:ring-violet-500"
              />
              <div>
                <p class="text-sm font-semibold text-slate-200">즉시 현재 버전으로 설정</p>
                <p class="text-xs text-slate-500">체크 시 기존 현재 버전이 이전 버전으로 변경됩니다.</p>
              </div>
            </label>

            <div class="flex gap-3">
              <Link
                href="/admin/legal"
                class="flex-1 text-center py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-semibold transition-colors"
              >
                취소
              </Link>
              <button
                type="submit"
                class="flex-1 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-sm font-bold transition-colors"
              >
                {{ isEdit ? '저장하기' : '추가하기' }}
              </button>
            </div>
          </div>
        </div>

        <!-- 오른쪽: 실시간 미리보기 (데스크탑) -->
        <div class="hidden lg:block">
          <div class="sticky top-8 bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-800 flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-violet-500"></span>
              <span class="text-xs font-semibold text-slate-400">미리보기</span>
            </div>
            <div class="px-6 py-5 max-h-[80vh] overflow-y-auto legal-preview">
              <h1 class="text-xl font-black text-white mb-2">{{ form.title || '제목 없음' }}</h1>
              <p class="text-xs text-slate-400 mb-5">시행일: {{ form.effective_date }}</p>
              <div v-html="form.content"></div>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<style scoped>
.legal-preview :deep(section) { margin-bottom: 2rem; }
.legal-preview :deep(h2) { font-size: 1rem; font-weight: 700; color: #e2e8f0; margin-bottom: 0.75rem; padding-bottom: 0.375rem; border-bottom: 1px solid #1e293b; }
.legal-preview :deep(p) { color: #94a3b8; line-height: 1.7; font-size: 0.875rem; margin-bottom: 0.625rem; }
.legal-preview :deep(ol), .legal-preview :deep(ul) { color: #94a3b8; font-size: 0.875rem; line-height: 1.7; padding-left: 1.25rem; margin-bottom: 0.625rem; }
.legal-preview :deep(li) { margin-bottom: 0.3rem; }
.legal-preview :deep(strong) { color: #e2e8f0; font-weight: 600; }
.legal-preview :deep(table) { width: 100%; border-collapse: collapse; font-size: 0.8rem; margin: 0.75rem 0; }
.legal-preview :deep(th), .legal-preview :deep(td) { border: 1px solid #1e293b; padding: 0.5rem 0.75rem; color: #94a3b8; }
.legal-preview :deep(th) { background: #0f172a; color: #cbd5e1; }
.legal-preview :deep(a) { color: #a78bfa; text-decoration: underline; }
</style>
