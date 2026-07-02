<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps<{
  questions: Array<any>
  source?: string | null
}>()

const currentStep = ref(0)
const form = useForm({
  answers: props.questions.map(q => ({ question_id: q.id, value: null })),
})

const current = computed(() => props.questions[currentStep.value])
const progress = computed(() => ((currentStep.value + 1) / props.questions.length) * 100)
const isLast = computed(() => currentStep.value === props.questions.length - 1)
const canNext = computed(() => form.answers[currentStep.value].value !== null)

const next = () => {
  if (canNext.value && !isLast.value) currentStep.value++
}
const prev = () => {
  if (currentStep.value > 0) currentStep.value--
}
const submit = () => form.post('/political-test')

const selectAnswer = (value) => {
  form.answers[currentStep.value].value = value
}

const choiceLabels = [
  { value: 2, label: '매우 동의', emoji: '💯', color: 'border-emerald-500 bg-emerald-500/10 text-emerald-300' },
  { value: 1, label: '동의', emoji: '👍', color: 'border-emerald-700 bg-emerald-700/10 text-emerald-400' },
  { value: 0, label: '중립', emoji: '😐', color: 'border-slate-600 bg-slate-700/30 text-slate-300' },
  { value: -1, label: '반대', emoji: '👎', color: 'border-red-700 bg-red-700/10 text-red-400' },
  { value: -2, label: '매우 반대', emoji: '❌', color: 'border-red-500 bg-red-500/10 text-red-300' },
]

const isSelected = (value) => form.answers[currentStep.value].value === value
</script>

<template>
  <div class="min-h-[calc(100vh-10rem)] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-2xl">
      <!-- Header -->
      <!-- 회원가입 경유 배너 -->
      <div v-if="source === 'register'"
        class="flex items-center justify-between bg-violet-500/10 border border-violet-500/30 rounded-xl px-4 py-3 mb-6 text-sm">
        <div class="flex items-center gap-2 text-violet-500 dark:text-violet-300 flex-1 min-w-0">
          <span class="flex-shrink-0">📝</span>
          <span class="text-xs sm:text-sm">회원가입에서 왔어요 — 테스트 완료 후 결과를 자동 적용할 수 있습니다</span>
        </div>
        <Link href="/register" class="text-xs text-slate-500 hover:text-slate-400 transition-colors ml-4 whitespace-nowrap">
          돌아가기
        </Link>
      </div>

      <div class="text-center mb-8">
        <div class="inline-flex items-center gap-2 bg-violet-500/10 border border-violet-500/30 rounded-full px-4 py-1.5 text-violet-500 dark:text-violet-400 text-xs font-semibold mb-4">
          🧭 정치 성향 테스트
        </div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white">나의 진영을 찾아라</h1>
      </div>

      <!-- Progress -->
      <div class="mb-8">
        <div class="flex items-center justify-between text-xs text-slate-500 mb-2">
          <span>질문 {{ currentStep + 1 }} / {{ questions.length }}</span>
          <span>{{ Math.round(progress) }}% 완료</span>
        </div>
        <div class="h-2 bg-gray-200 dark:bg-slate-800 rounded-full overflow-hidden">
          <div
            class="h-full bg-gradient-to-r from-violet-600 to-violet-400 rounded-full transition-all duration-500"
            :style="{ width: progress + '%' }"
          ></div>
        </div>
        <!-- Step dots -->
        <div class="flex gap-1.5 mt-3 justify-center flex-wrap">
          <div
            v-for="(_, i) in questions"
            :key="i"
            :class="[
              'h-1.5 rounded-full transition-all duration-300',
              i === currentStep
                ? 'w-6 bg-violet-400'
                : i < currentStep
                  ? 'w-1.5 bg-violet-400 dark:bg-violet-700'
                  : 'w-1.5 bg-gray-300 dark:bg-slate-700'
            ]"
          ></div>
        </div>
      </div>

      <!-- Question Card -->
      <transition name="slide" mode="out-in">
        <div :key="currentStep" class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-8">
          <!-- Question number badge -->
          <div class="flex items-center gap-3 mb-6">
            <span class="w-8 h-8 rounded-lg bg-violet-500/20 text-violet-400 text-sm font-bold flex items-center justify-center flex-shrink-0">
              {{ currentStep + 1 }}
            </span>
            <p class="text-slate-900 dark:text-white font-bold text-lg leading-snug">{{ current.question }}</p>
          </div>

          <!-- Choices -->
          <div class="space-y-3">
            <button
              v-for="choice in choiceLabels"
              :key="choice.value"
              @click="selectAnswer(choice.value)"
              :class="[
                'w-full flex items-center gap-4 px-5 py-4 rounded-xl border-2 transition-all text-left',
                isSelected(choice.value)
                  ? choice.color + ' shadow-lg scale-[1.01]'
                  : 'border-gray-300 dark:border-slate-700 bg-gray-100/50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 hover:border-gray-400 dark:hover:border-slate-500 hover:bg-gray-100 dark:hover:bg-slate-800'
              ]"
            >
              <span class="text-xl flex-shrink-0">{{ choice.emoji }}</span>
              <span class="font-semibold text-sm">{{ choice.label }}</span>
              <span v-if="isSelected(choice.value)" class="ml-auto">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
              </span>
            </button>
          </div>
        </div>
      </transition>

      <!-- Navigation -->
      <div class="flex items-center justify-between mt-6">
        <button
          @click="prev"
          :disabled="currentStep === 0"
          class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 border border-gray-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-sm font-medium transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          이전
        </button>

        <span class="text-slate-400 dark:text-slate-600 text-xs">
          {{ form.answers.filter(a => a.value !== null).length }}/{{ questions.length }} 답변 완료
        </span>

        <!-- Next or Submit -->
        <template v-if="!isLast">
          <button
            @click="next"
            :disabled="!canNext"
            class="flex items-center gap-2 px-6 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-sm font-bold transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
          >
            다음
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </template>
        <template v-else>
          <button
            @click="submit"
            :disabled="!canNext || form.processing"
            class="flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-blue-600 hover:from-violet-500 hover:to-blue-500 text-white text-sm font-bold transition-all disabled:opacity-30 disabled:cursor-not-allowed shadow-lg shadow-violet-500/20"
          >
            <span v-if="form.processing">분석 중...</span>
            <span v-else>결과 확인하기</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </button>
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped>
.slide-enter-active, .slide-leave-active {
  transition: all 0.25s ease;
}
.slide-enter-from {
  opacity: 0;
  transform: translateX(20px);
}
.slide-leave-to {
  opacity: 0;
  transform: translateX(-20px);
}
</style>
