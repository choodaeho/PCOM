<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

defineProps({
  canResetPassword: Boolean,
  status: String,
})

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
<Head title="로그인">
  <meta name="robots" content="noindex, nofollow" />
</Head>
  <div class="min-h-[calc(100vh-10rem)] flex items-center justify-center px-4 py-16">
    <div class="w-full max-w-md">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-3xl font-black text-slate-900 dark:text-white mb-2">다시 전장으로</h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm">로그인하여 커뮤니티에 참여하세요</p>
      </div>

      <!-- Status message -->
      <div v-if="status" class="mb-6 bg-emerald-900/40 border border-emerald-700/50 rounded-xl px-4 py-3 text-emerald-300 text-sm text-center">
        {{ status }}
      </div>

      <!-- Card -->
      <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-8">
        <!-- Social Login -->
        <div class="space-y-3 mb-6">
          <a href="/auth/kakao"
            class="flex items-center justify-center gap-3 w-full bg-[#FEE500] hover:bg-[#FFDA00] text-[#191919] font-semibold py-3 px-4 rounded-xl transition-colors text-sm">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 3C6.48 3 2 6.48 2 10.8c0 2.74 1.63 5.15 4.1 6.6L5.1 21l4.7-2.3c.72.1 1.46.16 2.2.16 5.52 0 10-3.48 10-7.8S17.52 3 12 3z"/>
            </svg>
            카카오로 시작하기
          </a>
          <a href="/auth/naver"
            class="flex items-center justify-center gap-3 w-full bg-[#03C75A] hover:bg-[#02B350] text-white font-semibold py-3 px-4 rounded-xl transition-colors text-sm">
            <span class="font-black text-base leading-none">N</span>
            네이버로 시작하기
          </a>
          <a href="/auth/google"
            class="flex items-center justify-center gap-3 w-full bg-white hover:bg-gray-100 text-slate-800 font-semibold py-3 px-4 rounded-xl transition-colors text-sm border border-gray-200">
            <svg class="w-5 h-5" viewBox="0 0 24 24">
              <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
              <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
              <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
              <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Google로 시작하기
          </a>
        </div>

        <!-- Divider -->
        <div class="flex items-center gap-4 mb-6">
          <div class="flex-1 h-px bg-gray-300 dark:bg-slate-700"></div>
          <span class="text-slate-400 dark:text-slate-500 text-xs">또는 이메일로 로그인</span>
          <div class="flex-1 h-px bg-gray-300 dark:bg-slate-700"></div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submit" class="space-y-4">
          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1.5">이메일</label>
            <input
              v-model="form.email"
              type="email"
              autocomplete="username"
              required
              placeholder="email@example.com"
              class="w-full bg-gray-100 dark:bg-slate-800 border border-gray-300 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors text-sm"
              :class="{ 'border-red-500': form.errors.email }"
            />
            <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-400">{{ form.errors.email }}</p>
          </div>

          <!-- Password -->
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="text-sm font-medium text-slate-600 dark:text-slate-300">비밀번호</label>
              <Link v-if="canResetPassword" href="/forgot-password" class="text-xs text-violet-500 dark:text-violet-400 hover:text-violet-400 dark:hover:text-violet-300 transition-colors">
                비밀번호 찾기
              </Link>
            </div>
            <input
              v-model="form.password"
              type="password"
              autocomplete="current-password"
              required
              placeholder="••••••••"
              class="w-full bg-gray-100 dark:bg-slate-800 border border-gray-300 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors text-sm"
              :class="{ 'border-red-500': form.errors.password }"
            />
            <p v-if="form.errors.password" class="mt-1.5 text-xs text-red-400">{{ form.errors.password }}</p>
          </div>

          <!-- Remember -->
          <div class="flex items-center gap-2">
            <input
              id="remember"
              v-model="form.remember"
              type="checkbox"
              class="w-4 h-4 rounded border-gray-400 dark:border-slate-600 bg-gray-100 dark:bg-slate-800 text-violet-500 focus:ring-violet-500"
            />
            <label for="remember" class="text-sm text-slate-500 dark:text-slate-400 cursor-pointer">로그인 상태 유지</label>
          </div>

          <!-- Submit -->
          <button
            type="submit"
            :disabled="form.processing"
            class="w-full bg-violet-600 hover:bg-violet-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-3 px-4 rounded-xl transition-colors text-sm"
          >
            <span v-if="form.processing">로그인 중...</span>
            <span v-else>로그인</span>
          </button>
        </form>
      </div>

      <!-- Register link -->
      <p class="text-center text-slate-400 dark:text-slate-500 text-sm mt-6">
        아직 계정이 없으신가요?
        <Link href="/register" class="text-violet-500 dark:text-violet-400 hover:text-violet-400 dark:hover:text-violet-300 font-semibold transition-colors ml-1">
          회원가입
        </Link>
      </p>
    </div>
  </div>
</template>
