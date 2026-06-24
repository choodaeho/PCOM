<script setup>
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  requestTypes: { type: Array, default: () => [] },
})

const page = usePage()

// 폼 상태
const form = ref({
  request_type:    '',
  requester_name:  '',
  requester_email: '',
  target_url:      '',
  description:     '',
  agree_privacy:   false,
})
const errors    = ref({})
const submitting = ref(false)
const submitted  = ref(false)

// 접수번호 (제출 후 표시용)
const submittedAt = ref('')

const selectedType = computed(() =>
  props.requestTypes.find(t => t.value === form.value.request_type)
)

const descLength = computed(() => form.value.description.length)

const submit = () => {
  if (!form.value.agree_privacy) {
    errors.value.agree_privacy = '개인정보 수집·이용에 동의해주세요.'
    return
  }
  submitting.value = true
  errors.value = {}

  router.post('/legal/deletion-request', form.value, {
    onSuccess: () => {
      submitting.value = false
      submitted.value  = true
      submittedAt.value = new Date().toLocaleString('ko-KR', {
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit',
      })
    },
    onError: (errs) => {
      submitting.value = false
      errors.value = errs
    },
  })
}

const resetForm = () => {
  submitted.value = false
  form.value = {
    request_type: '', requester_name: '', requester_email: '',
    target_url: '', description: '', agree_privacy: false,
  }
  errors.value = {}
}
</script>

<template>
  <div class="max-w-3xl mx-auto px-4 py-10">

    <!-- 페이지 헤더 -->
    <div class="mb-8">
      <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-3">
        <Link href="/legal/terms" class="hover:text-slate-700 dark:hover:text-slate-200 transition-colors">법적 고지</Link>
        <span>/</span>
        <span class="text-slate-700 dark:text-slate-200">삭제 요청</span>
      </div>
      <h1 class="text-3xl font-black text-slate-900 dark:text-white mb-2">삭제 요청</h1>
      <p class="text-slate-500 dark:text-slate-400 text-sm">
        명예훼손, 개인정보 침해, 저작권 위반 등의 게시물 삭제를 요청할 수 있습니다.
        접수 후 영업일 기준 <strong class="text-slate-700 dark:text-slate-300">7일 이내</strong> 처리됩니다.
      </p>
    </div>

    <!-- ══════════ 접수 완료 상태 ══════════ -->
    <div v-if="submitted" class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
      <div class="px-8 py-16 text-center">
        <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-4xl">
          ✅
        </div>
        <h2 class="text-2xl font-black text-slate-900 dark:text-white mb-3">삭제 요청이 접수되었습니다</h2>
        <p class="text-slate-500 dark:text-slate-400 text-sm mb-2">
          입력하신 이메일(<strong class="text-slate-700 dark:text-slate-300">{{ form.requester_email }}</strong>)로 처리 결과를 안내해드립니다.
        </p>
        <p class="text-xs text-slate-400 dark:text-slate-500 mb-8">접수 일시: {{ submittedAt }}</p>

        <div class="flex items-center justify-center gap-3 flex-wrap">
          <button
            @click="resetForm"
            class="px-5 py-2.5 rounded-xl bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 border border-gray-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-sm font-medium transition-colors"
          >
            추가 요청하기
          </button>
          <Link
            href="/"
            class="px-5 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-sm font-semibold transition-colors"
          >
            홈으로 이동
          </Link>
        </div>
      </div>
    </div>

    <!-- ══════════ 삭제 요청 폼 ══════════ -->
    <form v-else @submit.prevent="submit" class="space-y-6">

      <!-- 요청 유형 선택 -->
      <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-6">
        <h2 class="text-base font-bold text-slate-900 dark:text-white mb-4">1. 삭제 요청 유형</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <label
            v-for="type in requestTypes"
            :key="type.value"
            :class="[
              'relative flex flex-col gap-1 p-4 rounded-xl border-2 cursor-pointer transition-all',
              form.request_type === type.value
                ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/20'
                : 'border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50 hover:border-gray-300 dark:hover:border-slate-600'
            ]"
          >
            <input
              type="radio"
              :value="type.value"
              v-model="form.request_type"
              class="absolute opacity-0 pointer-events-none"
            />
            <span class="text-sm font-bold" :class="form.request_type === type.value ? 'text-violet-700 dark:text-violet-300' : 'text-slate-700 dark:text-slate-200'">
              {{ type.label }}
            </span>
            <span class="text-xs leading-relaxed" :class="form.request_type === type.value ? 'text-violet-500 dark:text-violet-400' : 'text-slate-400 dark:text-slate-500'">
              {{ type.description }}
            </span>
            <!-- 선택 체크 -->
            <div v-if="form.request_type === type.value" class="absolute top-3 right-3 w-5 h-5 rounded-full bg-violet-500 flex items-center justify-center">
              <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
              </svg>
            </div>
          </label>
        </div>
        <p v-if="errors.request_type" class="mt-2 text-xs text-rose-500">{{ errors.request_type }}</p>
      </div>

      <!-- 신청자 정보 -->
      <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-6">
        <h2 class="text-base font-bold text-slate-900 dark:text-white mb-4">2. 신청자 정보</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- 이름 -->
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">
              이름 <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="form.requester_name"
              type="text"
              placeholder="홍길동"
              maxlength="100"
              class="w-full px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-slate-800 border border-gray-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-violet-500 dark:focus:border-violet-500 text-sm transition-colors"
            />
            <p v-if="errors.requester_name" class="mt-1 text-xs text-rose-500">{{ errors.requester_name }}</p>
          </div>
          <!-- 이메일 -->
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">
              이메일 <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="form.requester_email"
              type="email"
              placeholder="example@email.com"
              maxlength="200"
              class="w-full px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-slate-800 border border-gray-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-violet-500 dark:focus:border-violet-500 text-sm transition-colors"
            />
            <p v-if="errors.requester_email" class="mt-1 text-xs text-rose-500">{{ errors.requester_email }}</p>
          </div>
        </div>
      </div>

      <!-- 삭제 대상 정보 -->
      <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-6">
        <h2 class="text-base font-bold text-slate-900 dark:text-white mb-4">3. 삭제 대상 정보</h2>

        <!-- 대상 URL -->
        <div class="mb-4">
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">
            대상 게시물 URL
            <span class="ml-1 font-normal text-slate-400 dark:text-slate-500">(선택)</span>
          </label>
          <input
            v-model="form.target_url"
            type="url"
            placeholder="https://polit.kr/boards/..."
            maxlength="1000"
            class="w-full px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-slate-800 border border-gray-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-violet-500 dark:focus:border-violet-500 text-sm font-mono transition-colors"
          />
          <p v-if="errors.target_url" class="mt-1 text-xs text-rose-500">{{ errors.target_url }}</p>
        </div>

        <!-- 삭제 사유 -->
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">
              삭제 요청 사유 <span class="text-rose-500">*</span>
            </label>
            <span class="text-xs" :class="descLength > 1800 ? 'text-rose-500' : 'text-slate-400 dark:text-slate-500'">
              {{ descLength }} / 2,000
            </span>
          </div>
          <textarea
            v-model="form.description"
            rows="6"
            maxlength="2000"
            placeholder="삭제를 요청하는 이유를 구체적으로 작성해주세요. (어떤 내용이, 왜 문제가 되는지 설명해주세요)"
            class="w-full px-4 py-3 rounded-xl bg-gray-100 dark:bg-slate-800 border border-gray-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-violet-500 dark:focus:border-violet-500 text-sm leading-relaxed resize-y transition-colors"
          ></textarea>
          <p v-if="errors.description" class="mt-1 text-xs text-rose-500">{{ errors.description }}</p>
        </div>
      </div>

      <!-- 개인정보 동의 + 제출 -->
      <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-6">

        <!-- 개인정보 수집 안내 -->
        <div class="mb-5 bg-gray-50 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700 rounded-xl p-4 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
          <p class="font-semibold text-slate-600 dark:text-slate-300 mb-2">📋 개인정보 수집·이용 안내</p>
          <table class="w-full text-xs">
            <tbody>
              <tr class="border-b border-gray-200 dark:border-slate-700">
                <td class="py-1.5 pr-3 font-medium text-slate-600 dark:text-slate-300 whitespace-nowrap">수집 항목</td>
                <td class="py-1.5">이름, 이메일 주소</td>
              </tr>
              <tr class="border-b border-gray-200 dark:border-slate-700">
                <td class="py-1.5 pr-3 font-medium text-slate-600 dark:text-slate-300 whitespace-nowrap">수집 목적</td>
                <td class="py-1.5">삭제 요청 처리 및 결과 통보</td>
              </tr>
              <tr>
                <td class="py-1.5 pr-3 font-medium text-slate-600 dark:text-slate-300 whitespace-nowrap">보유 기간</td>
                <td class="py-1.5">요청 처리 완료 후 3년</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- 동의 체크박스 -->
        <label class="flex items-start gap-3 cursor-pointer mb-6">
          <input
            type="checkbox"
            v-model="form.agree_privacy"
            class="mt-0.5 w-4 h-4 rounded border-gray-300 dark:border-slate-600 text-violet-600 focus:ring-violet-500 cursor-pointer flex-shrink-0"
          />
          <span class="text-sm text-slate-700 dark:text-slate-300">
            위 개인정보 수집·이용에 동의합니다. <span class="text-rose-500">*</span>
          </span>
        </label>
        <p v-if="errors.agree_privacy" class="mt-1 mb-4 text-xs text-rose-500">{{ errors.agree_privacy }}</p>

        <!-- 제출 버튼 -->
        <button
          type="submit"
          :disabled="submitting || !form.request_type || !form.requester_name || !form.requester_email || !form.description || !form.agree_privacy"
          class="w-full py-3 rounded-xl font-bold text-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          :class="submitting ? 'bg-violet-400 text-white cursor-wait' : 'bg-violet-600 hover:bg-violet-500 text-white'"
        >
          <span v-if="submitting" class="flex items-center justify-center gap-2">
            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            제출 중...
          </span>
          <span v-else>삭제 요청 제출</span>
        </button>

        <p class="mt-3 text-center text-xs text-slate-400 dark:text-slate-500">
          접수 후 영업일 기준 7일 이내 이메일로 결과를 안내드립니다.
        </p>
      </div>

    </form>
  </div>
</template>
