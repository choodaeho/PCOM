<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  document:  { type: Object, required: true },
  versions:  { type: Array,  default: () => [] },
  isCurrent: { type: Boolean, default: true },
})

const viewVersion = (id) => {
  if (id === props.document.id) return
  router.get('/legal/privacy', { v: id }, { preserveScroll: false })
}

const currentId = computed(() => props.document.id)
</script>

<template>
<Head title="개인정보처리방침">
  <meta name="description" content="폴릿 개인정보처리방침 — 개인정보 수집·이용에 관한 안내입니다." />
  <meta name="robots" content="noindex" />
</Head>
  <div class="max-w-6xl mx-auto px-4 py-10">

    <!-- 이전 버전 보기 경고 배너 -->
    <div v-if="!isCurrent" class="mb-6 flex items-center gap-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700/50 rounded-xl px-5 py-3 text-sm">
      <span class="text-xl flex-shrink-0">⚠️</span>
      <div>
        <span class="font-semibold text-amber-700 dark:text-amber-400">이전 버전을 보고 있습니다.</span>
        <span class="text-amber-600 dark:text-amber-500 ml-1">현재 적용 중인 방침을 보려면</span>
        <Link href="/legal/privacy" class="ml-1 text-amber-700 dark:text-amber-400 underline font-semibold hover:no-underline">여기</Link>
        <span class="text-amber-600 dark:text-amber-500">를 클릭하세요.</span>
      </div>
    </div>

    <!-- 개인정보보호 담당자 Quick Card -->
    <div class="mb-8 bg-violet-50 dark:bg-violet-900/10 border border-violet-200 dark:border-violet-800/40 rounded-2xl px-6 py-5 flex items-center gap-5">
      <div class="w-12 h-12 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center text-2xl flex-shrink-0">
        🔒
      </div>
      <div>
        <p class="text-xs font-semibold text-violet-500 dark:text-violet-400 mb-1">개인정보 관련 문의</p>
        <p class="text-sm font-bold text-slate-800 dark:text-slate-200">개인정보보호팀</p>
        <a href="mailto:privacy@polit.kr" class="text-sm text-violet-600 dark:text-violet-400 hover:underline">privacy@polit.kr</a>
      </div>
    </div>

    <div class="flex gap-8">

      <!-- 사이드바: 개정 이력 -->
      <aside class="hidden lg:block w-64 flex-shrink-0">
        <div class="sticky top-8">
          <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-slate-800">
              <h2 class="text-sm font-bold text-slate-700 dark:text-slate-300">📋 개정 이력</h2>
            </div>
            <div class="p-3 space-y-1">
              <button
                v-for="v in versions"
                :key="v.id"
                @click="viewVersion(v.id)"
                :class="[
                  'w-full text-left px-3 py-2.5 rounded-xl transition-colors',
                  v.id === currentId
                    ? 'bg-violet-600 text-white'
                    : 'hover:bg-gray-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400'
                ]"
              >
                <div class="flex items-center justify-between mb-0.5">
                  <span class="text-xs font-bold">{{ v.version }}</span>
                  <span v-if="v.is_current" class="text-[10px] font-bold px-1.5 py-0.5 rounded-full"
                    :class="v.id === currentId ? 'bg-white/20 text-white' : 'bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400'">
                    현행
                  </span>
                </div>
                <p class="text-xs" :class="v.id === currentId ? 'text-violet-200' : 'text-slate-400 dark:text-slate-500'">
                  {{ v.effective_date }} 시행
                </p>
              </button>
            </div>
          </div>
        </div>
      </aside>

      <!-- 본문 -->
      <div class="flex-1 min-w-0">
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">

          <!-- 문서 헤더 -->
          <div class="px-8 py-7 border-b border-gray-200 dark:border-slate-800">
            <div class="flex items-center gap-3 mb-3">
              <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 border border-violet-200 dark:border-violet-700/50">
                {{ document.version }}
              </span>
              <span v-if="document.is_current" class="text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-700/50">
                현재 적용
              </span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white mb-2">{{ document.title }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
              시행일: <strong class="text-slate-700 dark:text-slate-300">{{ document.effective_date }}</strong>
            </p>
          </div>

          <!-- 모바일 개정 이력 -->
          <div class="lg:hidden px-6 py-4 border-b border-gray-200 dark:border-slate-800">
            <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">개정 이력</label>
            <select
              @change="viewVersion(Number($event.target.value))"
              :value="currentId"
              class="w-full text-sm bg-gray-100 dark:bg-slate-800 border border-gray-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:border-violet-500"
            >
              <option v-for="v in versions" :key="v.id" :value="v.id">
                {{ v.version }} — {{ v.effective_date }} 시행{{ v.is_current ? ' (현행)' : '' }}
              </option>
            </select>
          </div>

          <!-- 방침 본문 -->
          <div class="px-8 py-7 legal-content" v-html="document.content"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.legal-content :deep(section) { margin-bottom: 2.5rem; }
.legal-content :deep(h2) {
  font-size: 1.05rem;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 0.875rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #e2e8f0;
}
:global(html.dark) .legal-content :deep(h2) { color: #f1f5f9; border-bottom-color: #1e293b; }
.legal-content :deep(p) { color: #475569; line-height: 1.75; margin-bottom: 0.75rem; font-size: 0.9rem; }
:global(html.dark) .legal-content :deep(p) { color: #94a3b8; }
.legal-content :deep(ol), .legal-content :deep(ul) {
  color: #475569; font-size: 0.9rem; line-height: 1.75; padding-left: 1.5rem; margin-bottom: 0.75rem;
}
:global(html.dark) .legal-content :deep(ol),
:global(html.dark) .legal-content :deep(ul) { color: #94a3b8; }
.legal-content :deep(li) { margin-bottom: 0.4rem; }
.legal-content :deep(strong) { color: #1e293b; font-weight: 600; }
:global(html.dark) .legal-content :deep(strong) { color: #e2e8f0; }
.legal-content :deep(table) { width: 100%; border-collapse: collapse; margin: 1rem 0; font-size: 0.875rem; }
.legal-content :deep(th), .legal-content :deep(td) { border: 1px solid #e2e8f0; padding: 0.625rem 1rem; text-align: left; }
:global(html.dark) .legal-content :deep(th),
:global(html.dark) .legal-content :deep(td) { border-color: #1e293b; color: #94a3b8; }
.legal-content :deep(th) { background: #f8fafc; font-weight: 600; color: #334155; }
:global(html.dark) .legal-content :deep(th) { background: #0f172a; color: #cbd5e1; }
/* contact-table (개인정보보호책임자) 강조 */
.legal-content :deep(.contact-table th) {
  background: #ede9fe; color: #6d28d9; border-color: #ddd6fe; width: 120px;
}
:global(html.dark) .legal-content :deep(.contact-table th) {
  background: #1e1b4b; color: #a78bfa; border-color: #312e81;
}
.legal-content :deep(.contact-table td) { font-weight: 600; color: #334155; }
:global(html.dark) .legal-content :deep(.contact-table td) { color: #e2e8f0; }
.legal-content :deep(a) { color: #7c3aed; text-decoration: underline; }
:global(html.dark) .legal-content :deep(a) { color: #a78bfa; }
.legal-content :deep(a:hover) { color: #6d28d9; }
:global(html.dark) .legal-content :deep(a:hover) { color: #c4b5fd; }
</style>
