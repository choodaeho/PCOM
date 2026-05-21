<script setup>
import { computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
defineOptions({ layout: AdminLayout })

const props = defineProps({ poll: Object })
const isEdit = computed(() => !!props.poll)

const form = useForm({
  title: props.poll?.title ?? '',
  options: props.poll?.options?.map(o => ({ text: o.text })) ?? [{ text: '' }, { text: '' }],
  starts_at: props.poll?.starts_at ? props.poll.starts_at.slice(0, 16) : '',
  ends_at: props.poll?.ends_at ? props.poll.ends_at.slice(0, 16) : '',
  is_active: props.poll?.is_active ?? true,
})

const addOption = () => { if (form.options.length < 6) form.options.push({ text: '' }) }
const removeOption = (index) => { if (form.options.length > 2) form.options.splice(index, 1) }

const submit = () => {
  if (isEdit.value) {
    form.put(`/admin/polls/${props.poll.id}`)
  } else {
    form.post('/admin/polls')
  }
}
</script>

<template>
  <div class="p-6 max-w-2xl">
    <div class="flex items-center gap-3 mb-6">
      <Link href="/admin/polls" class="text-slate-400 hover:text-white transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </Link>
      <h1 class="text-2xl font-bold text-white">{{ isEdit ? '투표 수정' : '새 투표 만들기' }}</h1>
    </div>

    <form @submit.prevent="submit" class="space-y-6">
      <!-- Title -->
      <div>
        <label class="block text-slate-400 text-xs mb-1.5 font-medium uppercase tracking-wider">투표 제목 *</label>
        <input
          v-model="form.title"
          type="text"
          required
          placeholder="이슈 투표 제목을 입력하세요"
          class="w-full bg-slate-800 border border-slate-700 text-slate-100 placeholder-slate-600 rounded-lg px-4 py-2.5 focus:outline-none focus:border-violet-500"
        />
        <p v-if="form.errors.title" class="text-red-400 text-xs mt-1">{{ form.errors.title }}</p>
      </div>

      <!-- Options -->
      <div>
        <div class="flex items-center justify-between mb-2">
          <label class="text-slate-400 text-xs font-medium uppercase tracking-wider">투표 옵션 * (최소 2개, 최대 6개)</label>
          <button
            type="button"
            @click="addOption"
            :disabled="form.options.length >= 6"
            class="text-violet-400 hover:text-violet-300 disabled:opacity-40 text-xs transition-colors flex items-center gap-1"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            옵션 추가
          </button>
        </div>
        <div class="space-y-2">
          <div v-for="(opt, index) in form.options" :key="index" class="flex items-center gap-2">
            <span class="text-slate-600 text-sm w-5 text-center shrink-0">{{ index + 1 }}</span>
            <input
              v-model="opt.text"
              type="text"
              required
              :placeholder="`옵션 ${index + 1}`"
              class="flex-1 bg-slate-800 border border-slate-700 text-slate-100 placeholder-slate-600 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-violet-500"
            />
            <button
              type="button"
              @click="removeOption(index)"
              :disabled="form.options.length <= 2"
              class="text-slate-600 hover:text-red-400 disabled:opacity-30 transition-colors shrink-0"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
        <p v-if="form.errors.options" class="text-red-400 text-xs mt-1">{{ form.errors.options }}</p>
      </div>

      <!-- Date Range -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-slate-400 text-xs mb-1.5 font-medium uppercase tracking-wider">시작일 *</label>
          <input
            v-model="form.starts_at"
            type="datetime-local"
            required
            class="w-full bg-slate-800 border border-slate-700 text-slate-100 rounded-lg px-3 py-2.5 focus:outline-none focus:border-violet-500"
          />
          <p v-if="form.errors.starts_at" class="text-red-400 text-xs mt-1">{{ form.errors.starts_at }}</p>
        </div>
        <div>
          <label class="block text-slate-400 text-xs mb-1.5 font-medium uppercase tracking-wider">종료일 *</label>
          <input
            v-model="form.ends_at"
            type="datetime-local"
            required
            class="w-full bg-slate-800 border border-slate-700 text-slate-100 rounded-lg px-3 py-2.5 focus:outline-none focus:border-violet-500"
          />
          <p v-if="form.errors.ends_at" class="text-red-400 text-xs mt-1">{{ form.errors.ends_at }}</p>
        </div>
      </div>

      <!-- Active Toggle -->
      <div class="flex items-center justify-between bg-slate-800/50 rounded-xl px-4 py-3 border border-slate-800">
        <div>
          <p class="text-slate-200 text-sm font-medium">즉시 활성화</p>
          <p class="text-slate-500 text-xs mt-0.5">비활성화 시 임시저장 상태로 유지</p>
        </div>
        <button
          type="button"
          @click="form.is_active = !form.is_active"
          :class="['w-11 h-6 rounded-full transition-colors relative',
            form.is_active ? 'bg-violet-600' : 'bg-slate-700']"
        >
          <span :class="['absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-all',
            form.is_active ? 'left-5.5' : 'left-0.5']"></span>
        </button>
      </div>

      <!-- Submit -->
      <div class="flex gap-3 pt-2">
        <Link href="/admin/polls" class="px-5 py-2.5 text-slate-400 hover:text-white text-sm transition-colors">취소</Link>
        <button
          type="submit"
          :disabled="form.processing"
          class="bg-violet-600 hover:bg-violet-500 disabled:opacity-50 text-white text-sm px-6 py-2.5 rounded-lg transition-colors font-medium"
        >
          {{ form.processing ? '저장 중...' : (isEdit ? '수정 완료' : '투표 생성') }}
        </button>
      </div>
    </form>
  </div>
</template>
