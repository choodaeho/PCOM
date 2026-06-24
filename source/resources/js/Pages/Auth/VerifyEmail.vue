<script setup>
import { computed, ref } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

defineProps({ status: String })

const page = usePage()
const user = computed(() => page.props.auth?.user)

const form = useForm({})
const resending = ref(false)

const resend = () => {
  form.post('/email/verification-notification')
}

const verificationSent = computed(() => form.recentlySuccessful)
</script>

<template>
  <div class="min-h-[calc(100vh-10rem)] flex items-center justify-center px-4 py-16">
    <div class="w-full max-w-md text-center">
      <!-- Icon -->
      <div class="w-20 h-20 rounded-full bg-violet-500/20 border border-violet-500/30 flex items-center justify-center text-3xl mx-auto mb-6">
        📧
      </div>

      <h1 class="text-3xl font-black text-slate-900 dark:text-white mb-4">이메일 인증 필요</h1>

      <p class="text-slate-500 dark:text-slate-400 text-sm leading-relaxed mb-2">
        가입하신 이메일 주소로 인증 메일을 발송했습니다.
      </p>
      <p class="text-slate-700 dark:text-slate-300 text-sm font-medium mb-8">
        {{ user?.email }}
      </p>

      <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-6 mb-6">
        <ol class="text-left space-y-3 text-sm text-slate-500 dark:text-slate-400">
          <li class="flex items-start gap-3">
            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-violet-500/20 text-violet-400 text-xs flex items-center justify-center font-bold mt-0.5">1</span>
            이메일 받은 편지함을 확인하세요
          </li>
          <li class="flex items-start gap-3">
            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-violet-500/20 text-violet-400 text-xs flex items-center justify-center font-bold mt-0.5">2</span>
            "이메일 주소 인증" 버튼을 클릭하세요
          </li>
          <li class="flex items-start gap-3">
            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-violet-500/20 text-violet-400 text-xs flex items-center justify-center font-bold mt-0.5">3</span>
            인증 완료 후 성향 테스트를 시작하세요
          </li>
        </ol>
      </div>

      <!-- Success message -->
      <transition name="flash">
        <div v-if="verificationSent" class="mb-4 bg-emerald-900/40 border border-emerald-700/50 rounded-xl px-4 py-3 text-emerald-300 text-sm">
          인증 이메일을 재발송했습니다. 이메일을 확인하세요.
        </div>
      </transition>

      <!-- Status -->
      <div v-if="status === 'verification-link-sent'" class="mb-4 bg-emerald-900/40 border border-emerald-700/50 rounded-xl px-4 py-3 text-emerald-300 text-sm">
        새 인증 링크가 발송되었습니다.
      </div>

      <!-- Actions -->
      <div class="flex flex-col gap-3">
        <button
          @click="resend"
          :disabled="form.processing"
          class="w-full bg-violet-600 hover:bg-violet-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-3 px-4 rounded-xl transition-colors text-sm"
        >
          <span v-if="form.processing">발송 중...</span>
          <span v-else>인증 이메일 재발송</span>
        </button>

        <Link
          href="/logout"
          method="post"
          as="button"
          class="w-full bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 border border-gray-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-medium py-3 px-4 rounded-xl transition-colors text-sm"
        >
          로그아웃
        </Link>
      </div>

      <p class="text-slate-400 dark:text-slate-600 text-xs mt-6">
        스팸 메일함도 확인해 보세요.
      </p>
    </div>
  </div>
</template>

<style scoped>
.flash-enter-active, .flash-leave-active { transition: all 0.3s ease; }
.flash-enter-from, .flash-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
