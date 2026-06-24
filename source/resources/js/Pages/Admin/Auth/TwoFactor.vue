<template>
  <div class="min-h-screen bg-slate-950 flex items-center justify-center p-4">
    <!-- 배경 -->
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-slate-800/30 via-slate-950 to-slate-950 pointer-events-none" />

    <div class="relative w-full max-w-sm">
      <!-- 헤더 -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-emerald-600/10 border border-emerald-600/30 mb-4">
          <svg class="w-8 h-8 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 8.25h3m-3 3.75h3m-3 3.75h3" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-white tracking-tight">
          2단계 인증
        </h1>
        <p class="mt-1 text-sm text-slate-400">
          {{ isSetup ? 'Google Authenticator 앱을 등록해주세요' : 'OTP 코드를 입력해주세요' }}
        </p>
        <p class="mt-0.5 text-xs text-slate-600">{{ props.email }}</p>
      </div>

      <!-- ─── Setup 모드: QR 코드 등록 ─────────────────────────────────── -->
      <template v-if="isSetup">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-8 space-y-6">
          <!-- Step 1: 앱 다운로드 안내 -->
          <div>
            <div class="flex items-center gap-2 mb-3">
              <span class="flex items-center justify-center w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-bold">1</span>
              <p class="text-sm font-medium text-slate-200">Google Authenticator 앱 설치</p>
            </div>
            <div class="flex gap-3 pl-7">
              <a href="https://apps.apple.com/app/google-authenticator/id388497605" target="_blank"
                 class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-lg text-xs text-slate-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                App Store
              </a>
              <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank"
                 class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-lg text-xs text-slate-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M3.18 23.76a2 2 0 001.93-.21l11.04-6.37-2.44-2.44-10.53 9.02zM.64 1.23A2 2 0 000 2.68v18.64a2 2 0 00.64 1.45l.07.07 10.44-10.44v-.25L.71 1.16l-.07.07zm20.41 9.33l-2.95-1.7-2.72 2.72 2.72 2.73 2.97-1.71c.85-.49.85-1.56-.02-2.04zM5.11.45L16.15 6.82l-2.44 2.44L3.18.24a2 2 0 001.93.21z"/></svg>
                Play Store
              </a>
            </div>
          </div>

          <!-- Step 2: QR 코드 스캔 -->
          <div>
            <div class="flex items-center gap-2 mb-3">
              <span class="flex items-center justify-center w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-bold">2</span>
              <p class="text-sm font-medium text-slate-200">QR 코드 스캔</p>
            </div>
            <div class="pl-7">
              <div class="flex justify-center mb-3">
                <div class="p-3 bg-white rounded-xl shadow-lg">
                  <img
                    :src="qrCodeImgUrl"
                    alt="Google Authenticator QR 코드"
                    width="160"
                    height="160"
                    class="block"
                  />
                </div>
              </div>
              <!-- 수동 입력용 시크릿 -->
              <details class="group">
                <summary class="text-xs text-slate-500 cursor-pointer hover:text-slate-400 list-none flex items-center gap-1">
                  <svg class="w-3.5 h-3.5 transition-transform group-open:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                  QR 코드 스캔이 안 되나요? (수동 입력)
                </summary>
                <div class="mt-2 p-3 bg-slate-800 rounded-lg">
                  <p class="text-xs text-slate-400 mb-1">앱에서 '직접 입력'을 선택 후 아래 코드를 입력하세요:</p>
                  <code class="block text-xs text-emerald-400 font-mono break-all tracking-widest select-all">
                    {{ props.secret }}
                  </code>
                </div>
              </details>
            </div>
          </div>

          <!-- Step 3: OTP 입력 (설정 확인) -->
          <form @submit.prevent="submitOtp">
            <div class="flex items-center gap-2 mb-3">
              <span class="flex items-center justify-center w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-bold">3</span>
              <p class="text-sm font-medium text-slate-200">앱에서 생성된 6자리 코드 입력</p>
            </div>
            <div class="pl-7 space-y-3">
              <input
                v-model="form.otp"
                type="text"
                inputmode="numeric"
                pattern="[0-9]{6}"
                maxlength="6"
                autocomplete="one-time-code"
                placeholder="000000"
                class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-lg
                       text-white text-center text-2xl font-mono tracking-[0.5em]
                       placeholder-slate-600
                       focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50
                       transition-colors"
              />
              <p v-if="errors.otp" class="text-xs text-red-400 text-center">{{ errors.otp }}</p>

              <button
                type="submit"
                :disabled="form.processing || form.otp.length !== 6"
                class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-500
                       disabled:bg-slate-700 disabled:text-slate-500 disabled:cursor-not-allowed
                       text-white text-sm font-semibold rounded-lg
                       transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2
                       focus:ring-offset-slate-900"
              >
                <span v-if="form.processing" class="flex items-center justify-center gap-2">
                  <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  검증 중...
                </span>
                <span v-else>등록 완료 및 로그인</span>
              </button>
            </div>
          </form>
        </div>
      </template>

      <!-- ─── Verify 모드: OTP 입력 ──────────────────────────────────────── -->
      <template v-else>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-8">
          <form @submit.prevent="submitOtp" class="space-y-5">
            <!-- 안내 메시지 -->
            <div class="flex items-start gap-3 p-3.5 bg-slate-800/50 rounded-lg border border-slate-700">
              <svg class="w-5 h-5 text-emerald-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.955 11.955 0 003 12c0 3.859 1.825 7.296 4.67 9.5M21 12c0-3.859-1.825-7.296-4.67-9.5" />
              </svg>
              <p class="text-xs text-slate-300 leading-relaxed">
                Google Authenticator 앱을 열어<br>
                <span class="font-semibold text-white">Polit 관리자</span> 항목의 6자리 코드를 입력하세요.
              </p>
            </div>

            <!-- OTP 입력 -->
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1.5 text-center">
                인증 코드 (6자리)
              </label>
              <input
                v-model="form.otp"
                ref="otpInput"
                type="text"
                inputmode="numeric"
                pattern="[0-9]{6}"
                maxlength="6"
                autocomplete="one-time-code"
                placeholder="000000"
                class="w-full px-4 py-3.5 bg-slate-800 border border-slate-700 rounded-lg
                       text-white text-center text-3xl font-mono tracking-[0.6em]
                       placeholder-slate-600
                       focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50
                       transition-colors"
              />
              <p v-if="errors.otp" class="mt-1.5 text-xs text-red-400 text-center">{{ errors.otp }}</p>
            </div>

            <button
              type="submit"
              :disabled="form.processing || form.otp.length !== 6"
              class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-500
                     disabled:bg-slate-700 disabled:text-slate-500 disabled:cursor-not-allowed
                     text-white text-sm font-semibold rounded-lg
                     transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2
                     focus:ring-offset-slate-900"
            >
              <span v-if="form.processing" class="flex items-center justify-center gap-2">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                인증 중...
              </span>
              <span v-else>관리자 패널 입장 →</span>
            </button>
          </form>
        </div>

        <!-- 코드 만료 안내 -->
        <div class="mt-4 flex items-center justify-center gap-1.5 text-xs text-slate-600">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          코드는 30초마다 갱신됩니다
        </div>
      </template>

      <!-- 뒤로가기 -->
      <div class="mt-6 text-center">
        <a href="/admin/login"
           class="text-xs text-slate-600 hover:text-slate-400 transition-colors">
          ← 로그인 페이지로 돌아가기
        </a>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'

interface Props {
  mode:        'setup' | 'verify'
  email:       string
  otpauthUrl?: string
  secret?:     string
}

const props = defineProps<Props>()

const isSetup   = computed(() => props.mode === 'setup')
const otpInput  = ref<HTMLInputElement | null>(null)

// QR 코드 이미지 URL (api.qrserver.com 무료 API 사용)
const qrCodeImgUrl = computed(() => {
  if (!props.otpauthUrl) return ''
  return `https://api.qrserver.com/v1/create-qr-code/?data=${encodeURIComponent(props.otpauthUrl)}&size=160x160&ecc=M`
})

const form = useForm({ otp: '' })
const errors = form.errors

function submitOtp(): void {
  form.post('/admin/login/2fa', {
    onError: () => {
      form.reset('otp')
      otpInput.value?.focus()
    },
  })
}

// 숫자만 입력 허용
function onInput(e: Event): void {
  const target = e.target as HTMLInputElement
  form.otp = target.value.replace(/\D/g, '').slice(0, 6)
}

onMounted(() => {
  // verify 모드에서 자동 포커스
  if (!isSetup.value) {
    otpInput.value?.focus()
  }
})
</script>
               