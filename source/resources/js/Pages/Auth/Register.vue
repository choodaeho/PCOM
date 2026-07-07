<script setup lang="ts">
import { ref, watch } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps<{
  preselectedFaction?: string | null
}>()

// ── 진영 정의 ──────────────────────────────────────────────────────────────
const factions = [
  {
    value: 'conservative',
    label: '보수',
    emoji: '🦅',
    description: '전통과 질서, 자유 시장',
    keywords: ['#전통가치', '#강한안보', '#자유시장'],
    bgClass: 'bg-red-500/10',
    borderSelectedClass: 'border-red-500',
    textClass: 'text-red-400',
    dotClass: 'bg-red-500',
    badgeClass: 'bg-red-500/20 text-red-400',
  },
  {
    value: 'moderate',
    label: '중도',
    emoji: '⚖️',
    description: '균형과 실용적 합리주의',
    keywords: ['#실용주의', '#균형', '#합리'],
    bgClass: 'bg-violet-500/10',
    borderSelectedClass: 'border-violet-500',
    textClass: 'text-violet-400',
    dotClass: 'bg-violet-500',
    badgeClass: 'bg-violet-500/20 text-violet-400',
  },
  {
    value: 'progressive',
    label: '진보',
    emoji: '🕊️',
    description: '평등과 사회 정의 추구',
    keywords: ['#사회정의', '#복지', '#평등'],
    bgClass: 'bg-blue-500/10',
    borderSelectedClass: 'border-blue-500',
    textClass: 'text-blue-400',
    dotClass: 'bg-blue-500',
    badgeClass: 'bg-blue-500/20 text-blue-400',
  },
]

// ── Form ────────────────────────────────────────────────────────────────────
const form = useForm({
  nickname: '',
  email: '',
  password: '',
  password_confirmation: '',
  political_type: props.preselectedFaction ?? '',
  agree_terms: false,
  agree_privacy: false,
})

// 성향 테스트 결과 복귀 시 prop 반영
watch(() => props.preselectedFaction, (val) => {
  if (val) form.political_type = val
}, { immediate: false })

const showPassword = ref(false)

const submit = () => {
  form.post('/register', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}

const goToTest = () => {
  router.visit('/political-test?source=register')
}

const selectedFaction = (value: string) => factions.find(f => f.value === value) ?? null
</script>

<template>
<Head title="회원가입">
  <meta name="robots" content="noindex, nofollow" />
</Head>
  <div class="min-h-[calc(100vh-10rem)] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-lg">

      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-3xl font-black text-slate-900 dark:text-white mb-2">진영에 합류하라</h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm">진영을 선택하고 커뮤니티에 참여하세요</p>
      </div>

      <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-8 space-y-6">

        <!-- ── 소셜 가입 ─────────────────────────────────────────────────── -->
        <div class="space-y-3">
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
        <div class="flex items-center gap-4">
          <div class="flex-1 h-px bg-gray-300 dark:bg-slate-700"></div>
          <span class="text-slate-400 dark:text-slate-500 text-xs">또는 이메일로 가입</span>
          <div class="flex-1 h-px bg-gray-300 dark:bg-slate-700"></div>
        </div>

        <!-- ── 이메일 폼 ─────────────────────────────────────────────────── -->
        <form @submit.prevent="submit" class="space-y-5">

          <!-- 닉네임 -->
          <div>
            <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1.5">닉네임</label>
            <input
              v-model="form.nickname"
              type="text"
              autocomplete="nickname"
              required
              placeholder="2~20자, 한글/영문/숫자/_"
              class="w-full bg-gray-100 dark:bg-slate-800 border border-gray-300 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors text-sm"
              :class="{ 'border-red-500': form.errors.nickname }"
            />
            <p v-if="form.errors.nickname" class="mt-1.5 text-xs text-red-400">{{ form.errors.nickname }}</p>
          </div>

          <!-- 이메일 -->
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

          <!-- 비밀번호 -->
          <div>
            <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1.5">비밀번호</label>
            <div class="relative">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="new-password"
                required
                placeholder="8자 이상, 영문+숫자 포함"
                class="w-full bg-gray-100 dark:bg-slate-800 border border-gray-300 dark:border-slate-700 rounded-xl px-4 py-3 pr-11 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors text-sm"
                :class="{ 'border-red-500': form.errors.password }"
              />
              <button type="button" @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-600 dark:hover:text-slate-300">
                <svg v-if="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
              </button>
            </div>
            <p v-if="form.errors.password" class="mt-1.5 text-xs text-red-400">{{ form.errors.password }}</p>
          </div>

          <!-- 비밀번호 확인 -->
          <div>
            <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1.5">비밀번호 확인</label>
            <input
              v-model="form.password_confirmation"
              type="password"
              autocomplete="new-password"
              required
              placeholder="비밀번호를 다시 입력하세요"
              class="w-full bg-gray-100 dark:bg-slate-800 border border-gray-300 dark:border-slate-700 rounded-xl px-4 py-3 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors text-sm"
              :class="{ 'border-red-500': form.errors.password_confirmation }"
            />
            <p v-if="form.errors.password_confirmation" class="mt-1.5 text-xs text-red-400">{{ form.errors.password_confirmation }}</p>
          </div>

          <!-- ── 성향 선택 ────────────────────────────────────────────────── -->
          <div>
            <div class="flex items-center justify-between mb-3">
              <label class="text-sm font-medium text-slate-600 dark:text-slate-300">
                나의 진영 선택 <span class="text-red-400">*</span>
              </label>
              <button
                type="button"
                @click="goToTest"
                class="text-xs text-violet-500 dark:text-violet-400 hover:text-violet-600 dark:hover:text-violet-300 transition-colors flex items-center gap-1 font-medium"
              >
                🧭 성향 테스트로 선택
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
              </button>
            </div>

            <!-- 진영 카드 3개 -->
            <div class="grid grid-cols-3 gap-2.5">
              <button
                v-for="f in factions"
                :key="f.value"
                type="button"
                @click="form.political_type = f.value"
                :class="[
                  'relative rounded-xl p-3 border-2 transition-all text-center cursor-pointer',
                  form.political_type === f.value
                    ? [f.bgClass, f.borderSelectedClass, 'shadow-md']
                    : 'bg-gray-100/50 dark:bg-slate-800/50 border-gray-300 dark:border-slate-700 hover:border-gray-400 dark:hover:border-slate-500 hover:bg-gray-100 dark:hover:bg-slate-800'
                ]"
              >
                <!-- 선택 체크 아이콘 -->
                <div v-if="form.political_type === f.value"
                  class="absolute top-1.5 right-1.5 w-4 h-4 rounded-full flex items-center justify-center"
                  :class="f.dotClass">
                  <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                </div>
                <div class="text-2xl mb-1.5">{{ f.emoji }}</div>
                <div :class="['text-sm font-bold', form.political_type === f.value ? f.textClass : 'text-slate-600 dark:text-slate-300']">
                  {{ f.label }}
                </div>
                <div class="text-xs text-slate-400 dark:text-slate-500 mt-0.5 leading-tight">{{ f.description }}</div>
              </button>
            </div>

            <!-- 선택된 진영 키워드 태그 -->
            <div v-if="form.political_type && selectedFaction(form.political_type)"
              class="mt-2.5 flex flex-wrap gap-1.5">
              <span
                v-for="kw in selectedFaction(form.political_type)!.keywords"
                :key="kw"
                :class="['text-xs px-2 py-0.5 rounded-full font-medium', selectedFaction(form.political_type)!.badgeClass]"
              >{{ kw }}</span>
            </div>

            <p v-if="form.errors.political_type" class="mt-1.5 text-xs text-red-400">
              {{ form.errors.political_type }}
            </p>
            <p v-else-if="!form.political_type" class="mt-1.5 text-xs text-slate-400 dark:text-slate-500">
              진영을 직접 선택하거나, 성향 테스트를 통해 확인할 수 있습니다
            </p>
          </div>

          <!-- 약관 -->
          <div class="space-y-2.5 py-1">
            <div class="flex items-start gap-2.5">
              <input id="agree_terms" v-model="form.agree_terms" type="checkbox" required
                class="mt-0.5 w-4 h-4 rounded border-gray-400 dark:border-slate-600 bg-gray-100 dark:bg-slate-800 text-violet-500 focus:ring-violet-500" />
              <label for="agree_terms" class="text-sm text-slate-500 dark:text-slate-400 cursor-pointer leading-relaxed">
                <span class="text-violet-500 dark:text-violet-400 font-medium">이용약관</span>에 동의합니다 <span class="text-red-400">*</span>
              </label>
            </div>
            <div class="flex items-start gap-2.5">
              <input id="agree_privacy" v-model="form.agree_privacy" type="checkbox" required
                class="mt-0.5 w-4 h-4 rounded border-gray-400 dark:border-slate-600 bg-gray-100 dark:bg-slate-800 text-violet-500 focus:ring-violet-500" />
              <label for="agree_privacy" class="text-sm text-slate-500 dark:text-slate-400 cursor-pointer leading-relaxed">
                <span class="text-violet-500 dark:text-violet-400 font-medium">개인정보 처리방침</span>에 동의합니다 <span class="text-red-400">*</span>
              </label>
            </div>
          </div>

          <!-- 제출 버튼 -->
          <button
            type="submit"
            :disabled="form.processing || !form.political_type"
            class="w-full bg-violet-600 hover:bg-violet-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-3 px-4 rounded-xl transition-colors text-sm"
          >
            <span v-if="form.processing">처리 중...</span>
            <span v-else-if="!form.political_type">진영을 먼저 선택해주세요</span>
            <span v-else>
              {{ selectedFaction(form.political_type)?.emoji }}
              {{ selectedFaction(form.political_type)?.label }} 진영으로 가입하기
            </span>
          </button>
        </form>
      </div>

      <!-- 로그인 링크 -->
      <p class="text-center text-slate-400 dark:text-slate-500 text-sm mt-6">
        이미 계정이 있으신가요?
        <Link href="/login" class="text-violet-500 dark:text-violet-400 hover:text-violet-600 dark:hover:text-violet-300 font-semibold transition-colors ml-1">
          로그인
        </Link>
      </p>
    </div>
  </div>
</template>
