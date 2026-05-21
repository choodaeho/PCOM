<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  agree_terms: false,
  agree_privacy: false,
})

const submit = () => {
  form.post('/register', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <div class="min-h-[calc(100vh-10rem)] flex items-center justify-center px-4 py-16">
    <div class="w-full max-w-md">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-3xl font-black text-white mb-2">진영에 합류하라</h1>
        <p class="text-slate-400 text-sm">회원가입 후 성향 테스트로 진영을 배정받으세요</p>
      </div>

      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8">
        <!-- Social Register -->
        <div class="space-y-3 mb-6">
          <a href="/auth/kakao"
            class="flex items-center justify-center gap-3 w-full bg-[#FEE500] hover:bg-[#FFDA00] text-[#191919] font-semibold py-3 px-4 rounded-xl transition-colors text-sm">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 3C6.48 3 2 6.48 2 10.8c0 2.74 1.63 5.15 4.1 6.6L5.1 21l4.7-2.3c.72.1 1.46.16 2.2.16 5.52 0 10-3.48 10-7.8S17.52 3 12 3z"/>
            </svg>
            카카오로 가입하기
          </a>
          <a href="/auth/naver"
            class="flex items-center justify-center gap-3 w-full bg-[#03C75A] hover:bg-[#02B350] text-white font-semibold py-3 px-4 rounded-xl transition-colors text-sm">
            <span class="font-black text-base leading-none">N</span>
            네이버로 가입하기
          </a>
          <a href="/auth/google"
            class="flex items-center justify-center gap-3 w-full bg-white hover:bg-slate-100 text-slate-800 font-semibold py-3 px-4 rounded-xl transition-colors text-sm">
            <svg class="w-5 h-5" viewBox="0 0 24 24">
              <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
              <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
              <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
              <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Google로 가입하기
          </a>
        </div>

        <!-- Divider -->
        <div class="flex items-center gap-4 mb-6">
          <div class="flex-1 h-px bg-slate-700"></div>
          <span class="text-slate-500 text-xs">또는 이메일로 가입</span>
          <div class="flex-1 h-px bg-slate-700"></div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submit" class="space-y-4">
          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-slate-300 mb-1.5">닉네임</label>
            <input
              v-model="form.name"
              type="text"
              autocomplete="name"
              required
              placeholder="사용할 닉네임을 입력하세요"
              class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors text-sm"
              :class="{ 'border-red-500': form.errors.name }"
            />
            <p v-if="form.errors.name" class="mt-1.5 text-xs text-red-400">{{ form.errors.name }}</p>
          </div>

          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-slate-300 mb-1.5">이메일</label>
            <input
              v-model="form.email"
              type="email"
              autocomplete="username"
              required
              placeholder="email@example.com"
              class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors text-sm"
              :class="{ 'border-red-500': form.errors.email }"
            />
            <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-400">{{ form.errors.email }}</p>
          </div>

          <!-- Password -->
          <div>
            <label class="block text-sm font-medium text-slate-300 mb-1.5">비밀번호</label>
            <input
              v-model="form.password"
              type="password"
              autocomplete="new-password"
              required
              placeholder="8자 이상 입력하세요"
              class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors text-sm"
              :class="{ 'border-red-500': form.errors.password }"
            />
            <p v-if="form.errors.password" class="mt-1.5 text-xs text-red-400">{{ form.errors.password }}</p>
          </div>

          <!-- Password Confirm -->
          <div>
            <label class="block text-sm font-medium text-slate-300 mb-1.5">비밀번호 확인</label>
            <input
              v-model="form.password_confirmation"
              type="password"
              autocomplete="new-password"
              required
              placeholder="비밀번호를 다시 입력하세요"
              class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors text-sm"
              :class="{ 'border-red-500': form.errors.password_confirmation }"
            />
            <p v-if="form.errors.password_confirmation" class="mt-1.5 text-xs text-red-400">{{ form.errors.password_confirmation }}</p>
          </div>

          <!-- Terms -->
          <div class="space-y-2.5 py-2">
            <div class="flex items-start gap-2.5">
              <input
                id="agree_terms"
                v-model="form.agree_terms"
                type="checkbox"
                required
                class="mt-0.5 w-4 h-4 rounded border-slate-600 bg-slate-800 text-violet-500 focus:ring-violet-500"
              />
              <label for="agree_terms" class="text-sm text-slate-400 cursor-pointer leading-relaxed">
                <span class="text-violet-400 font-medium">이용약관</span>에 동의합니다 <span class="text-red-400">*</span>
              </label>
            </div>
            <div class="flex items-start gap-2.5">
              <input
                id="agree_privacy"
                v-model="form.agree_privacy"
                type="checkbox"
                required
                class="mt-0.5 w-4 h-4 rounded border-slate-600 bg-slate-800 text-violet-500 focus:ring-violet-500"
              />
              <label for="agree_privacy" class="text-sm text-slate-400 cursor-pointer leading-relaxed">
                <span class="text-violet-400 font-medium">개인정보 처리방침</span>에 동의합니다 <span class="text-red-400">*</span>
              </label>
            </div>
          </div>

          <!-- Submit -->
          <button
            type="submit"
            :disabled="form.processing"
            class="w-full bg-violet-600 hover:bg-violet-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-3 px-4 rounded-xl transition-colors text-sm"
          >
            <span v-if="form.processing">처리 중...</span>
            <span v-else>가입 후 성향 테스트 시작</span>
          </button>
        </form>
      </div>

      <!-- Login link -->
      <p class="text-center text-slate-500 text-sm mt-6">
        이미 계정이 있으신가요?
        <Link href="/login" class="text-violet-400 hover:text-violet-300 font-semibold transition-colors ml-1">
          로그인
        </Link>
      </p>
    </div>
  </div>
</template>
