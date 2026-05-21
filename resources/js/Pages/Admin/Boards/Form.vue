<script setup>
import { computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
defineOptions({ layout: AdminLayout })

const props = defineProps({ board: Object })
const isEdit = computed(() => !!props.board)

const form = useForm({
  name: props.board?.name ?? '',
  slug: props.board?.slug ?? '',
  category: props.board?.category ?? 'azit',
  allowed_faction: props.board?.allowed_faction ?? 'all',
  description: props.board?.description ?? '',
  sort_order: props.board?.sort_order ?? 0,
  is_active: props.board?.is_active ?? true,
})

// Auto-generate slug from name
const generateSlug = () => {
  if (!isEdit.value) {
    form.slug = form.name
      .toLowerCase()
      .replace(/[^a-z0-9가-힣\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
      .slice(0, 50)
  }
}

const submit = () => {
  if (isEdit.value) {
    form.put(`/admin/boards/${props.board.id}`)
  } else {
    form.post('/admin/boards')
  }
}
</script>

<template>
  <div class="p-6 max-w-2xl">
    <div class="flex items-center gap-3 mb-6">
      <Link href="/admin/boards" class="text-slate-400 hover:text-white transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </Link>
      <h1 class="text-2xl font-bold text-white">{{ isEdit ? '게시판 수정' : '게시판 추가' }}</h1>
    </div>

    <form @submit.prevent="submit" class="space-y-5">
      <!-- Name -->
      <div>
        <label class="block text-slate-400 text-xs mb-1.5 font-medium uppercase tracking-wider">게시판 이름 *</label>
        <input
          v-model="form.name"
          @input="generateSlug"
          type="text"
          required
          placeholder="예: 보수 자유게시판"
          class="w-full bg-slate-800 border border-slate-700 text-slate-100 placeholder-slate-600 rounded-lg px-4 py-2.5 focus:outline-none focus:border-violet-500"
        />
        <p v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</p>
      </div>

      <!-- Slug -->
      <div>
        <label class="block text-slate-400 text-xs mb-1.5 font-medium uppercase tracking-wider">슬러그 (URL) *</label>
        <div class="flex items-center gap-2">
          <span class="text-slate-500 text-sm shrink-0">/boards/</span>
          <input
            v-model="form.slug"
            type="text"
            required
            placeholder="board-slug"
            class="flex-1 bg-slate-800 border border-slate-700 text-slate-100 placeholder-slate-600 rounded-lg px-4 py-2.5 focus:outline-none focus:border-violet-500"
          />
        </div>
        <p v-if="form.errors.slug" class="text-red-400 text-xs mt-1">{{ form.errors.slug }}</p>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <!-- Category -->
        <div>
          <label class="block text-slate-400 text-xs mb-1.5 font-medium uppercase tracking-wider">카테고리 *</label>
          <select
            v-model="form.category"
            class="w-full bg-slate-800 border border-slate-700 text-slate-300 rounded-lg px-3 py-2.5 focus:outline-none focus:border-violet-500"
          >
            <option value="azit">아지트 (진영 전용)</option>
            <option value="battle">전쟁터 (공용)</option>
            <option value="notice">공지사항</option>
          </select>
          <p v-if="form.errors.category" class="text-red-400 text-xs mt-1">{{ form.errors.category }}</p>
        </div>

        <!-- Allowed Faction -->
        <div>
          <label class="block text-slate-400 text-xs mb-1.5 font-medium uppercase tracking-wider">접근 진영 *</label>
          <select
            v-model="form.allowed_faction"
            class="w-full bg-slate-800 border border-slate-700 text-slate-300 rounded-lg px-3 py-2.5 focus:outline-none focus:border-violet-500"
          >
            <option value="all">전체 (공용)</option>
            <option value="conservative">보수 전용</option>
            <option value="moderate">중도 전용</option>
            <option value="progressive">진보 전용</option>
          </select>
          <p v-if="form.errors.allowed_faction" class="text-red-400 text-xs mt-1">{{ form.errors.allowed_faction }}</p>
        </div>
      </div>

      <!-- Description -->
      <div>
        <label class="block text-slate-400 text-xs mb-1.5 font-medium uppercase tracking-wider">설명 (선택)</label>
        <textarea
          v-model="form.description"
          rows="3"
          placeholder="게시판 소개 문구"
          class="w-full bg-slate-800 border border-slate-700 text-slate-100 placeholder-slate-600 rounded-lg px-4 py-2.5 resize-none focus:outline-none focus:border-violet-500"
        ></textarea>
      </div>

      <!-- Sort Order + Active -->
      <div class="flex items-center gap-4">
        <div class="flex-1">
          <label class="block text-slate-400 text-xs mb-1.5 font-medium uppercase tracking-wider">정렬 순서</label>
          <input
            v-model="form.sort_order"
            type="number"
            min="0"
            class="w-full bg-slate-800 border border-slate-700 text-slate-100 rounded-lg px-4 py-2.5 focus:outline-none focus:border-violet-500"
          />
        </div>
        <div class="flex items-center justify-between bg-slate-800/50 rounded-xl px-4 py-3 border border-slate-800 flex-1">
          <div>
            <p class="text-slate-200 text-sm font-medium">활성화</p>
            <p class="text-slate-500 text-xs">비활성 시 숨김 처리</p>
          </div>
          <button
            type="button"
            @click="form.is_active = !form.is_active"
            :class="['w-11 h-6 rounded-full transition-colors relative', form.is_active ? 'bg-violet-600' : 'bg-slate-700']"
          >
            <span :class="['absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-all', form.is_active ? 'left-5.5' : 'left-0.5']"></span>
          </button>
        </div>
      </div>

      <!-- Submit -->
      <div class="flex gap-3 pt-2">
        <Link href="/admin/boards" class="px-5 py-2.5 text-slate-400 hover:text-white text-sm transition-colors">취소</Link>
        <button
          type="submit"
          :disabled="form.processing"
          class="bg-violet-600 hover:bg-violet-500 disabled:opacity-50 text-white text-sm px-6 py-2.5 rounded-lg transition-colors font-medium"
        >
          {{ form.processing ? '저장 중...' : (isEdit ? '수정 완료' : '게시판 생성') }}
        </button>
      </div>
    </form>
  </div>
</template>
