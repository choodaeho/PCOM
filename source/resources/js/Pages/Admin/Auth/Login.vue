<template>
  <div class="min-h-screen bg-slate-950 flex items-center justify-center p-4">
    <!-- 배경 패턴 -->
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-slate-800/30 via-slate-950 to-slate-950 pointer-events-none" />

    <div class="relative w-full max-w-sm">
      <!-- 로고 / 헤더 -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-red-600/10 border border-red-600/30 mb-4">
          <svg class="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.955 11.955 0 003 12c0 3.859 1.825 7.296 4.67 9.5M21 12c0-3.859-1.825-7.296-4.67-9.5M9 3.964A11.959 11.959 0 0120.402 6" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-white tracking-tight">
          Polit <span class="text-red-500">관리자</span>
        </h1>
        <p class="mt-1 text-sm text-slate-400">관리자 전용 로그인</p>
      </div>

      <!-- 로그인 카드 -->
      <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-8">
        <form @submit.prevent="submit" class="space-y-5">
          <!-- 이메일 -->
          <div>
            <label for="email" class="block text-sm font-medium text-slate-300 mb-1.5">
              관리자 이메일
            </label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              autocomplete="username"
              required
              placeholder="admin@polit.kr"
              class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-white
                     placeholder-slate-500 text-sm
                     focus:outline-none focus:ring-2 focus:ring-red-500/50 focus:border-red-500/50
                     transition-colors"
            />
            <p v-if="errors.email" class="mt-1.5 text-xs text-red-400">{{ errors.email }}</p>
          </div>

          <!-- 비밀번호 -->
          <div>
            <label for="password" class="block text-sm font-medium text-slate-300 mb-1.5">
              비밀번호
            </label>
            <div class="relative">
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                required
                class="w-full px-4 py-2.5 pr-10 bg-slate-800 border border-slate-700 rounded-lg text-white
                       text-sm
                       focus:outline-none focus:ring-2 focus:ring-red-500/50 focus:border-red-500/50
                       transition-colors"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-200"
              >
                <svg v-if="!showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
              </button>
            </div>
            <p v-if="errors.password" class="mt-1.5 text-xs text-red-400">{{ errors.password }}</p>
          </div>

          <!-- 관리자 페이지 안내 -->
          <div v-if="$page.props.flash?.admin_redirect"
               class="flex items-start gap-2.5 p-3 bg-amber-500/10 border border-amber-500/30 rounded-lg">
            <svg class="w-4 h-4 text-amber-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <p class="text-xs text-amber-300">
              관리자 계정은 이 페이지에서만 로그인 가능합니다.
            </p>
          </div>

          <!-- 로그인 버튼 -->
          <button
            type="submit"
            :disabled="form.processing"
            class="w-full py-2.5 px-4 bg-red-600 hover:bg-red-500 disabled:bg-red-800 disabled:cursor-not-allowed
                   text-white text-sm font-semibold rounded-lg
                   transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2
                   focus:ring-offset-slate-900"
          >
            <span v-if="form.processing" class="flex items-center justify-center gap-2">
              <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
              확인 중...
            </span>
            <span v-else>다음 단계 →</span>
          </button>
        </form>
      </div>

      <!-- 하단 링크 -->
      <p class="mt-6 text-center text-xs text-slate-600">
        일반 사용자라면
        <a href="/login" class="text-slate-400 hover:text-slate-200 underline transition-colors">
          여기서 로그인
        </a>
      </p>

      <p class="mt-3 text-center text-xs text-slate-700">
        Polit Admin Panel · 무단 접근 시 법적 조치를 받을 수 있습니다.
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

const showPassword = ref(false)

const form = useForm({
  email:    '',
  password: '',
})

const errors = form.errors

function submit(): void {
  form.post('/admin/login', {
    onFinish: () => form.reset('password'),
  })
}
</script>
