<script setup>
import { ref, computed, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import AdminLayout from '@/Layouts/AdminLayout.vue'
defineOptions({ layout: AdminLayout })

const props = defineProps({
  config: Object,
  boards: Array,
})

// ── 폼 상태 ────────────────────────────────────────────────────
const form = reactive({
  gemini_api_key:        '',
  pixabay_api_key:       '',
  is_enabled:            props.config.is_enabled,
  posts_per_faction:     props.config.posts_per_faction,
  comments_per_post_min: props.config.comments_per_post_min,
  comments_per_post_max: props.config.comments_per_post_max,
  start_hour:            props.config.start_hour,
  end_hour:              props.config.end_hour,
  include_images:        props.config.include_images,
  include_news_links:    props.config.include_news_links,
  include_youtube:       props.config.include_youtube,
  use_grounding:         props.config.use_grounding,
  target_boards:         JSON.parse(JSON.stringify(props.config.target_boards ?? {})),
  topics:                JSON.parse(JSON.stringify(props.config.topics ?? {})),
})

const saving    = ref(false)
const saveMsg   = ref('')
const saveMsgOk = ref(true)

// ── 즉시 실행 ──────────────────────────────────────────────────
const runDate   = ref(new Date().toISOString().slice(0, 10))
const isDryRun  = ref(false)
const running   = ref(false)
const runResult = ref('')
const runOk     = ref(true)

// ── 통계 ───────────────────────────────────────────────────────
const lastRunAt    = ref(props.config.last_run_at)
const lastRunStats = ref(props.config.last_run_stats)
const refreshing   = ref(false)

// ── 추정치 ─────────────────────────────────────────────────────
const estPosts    = computed(() => form.posts_per_faction * 3)
const avgComments = computed(() => (form.comments_per_post_min + form.comments_per_post_max) / 2)
const estComments = computed(() => Math.round(estPosts.value * avgComments.value))
const windowMins  = computed(() => (form.end_hour - form.start_hour) * 60)
const intervalMin = computed(() => windowMins.value / Math.max(estPosts.value, 1))
const totalApiCalls = computed(() => estPosts.value + estComments.value)
const rpmUsage = computed(() => (totalApiCalls.value / Math.max(windowMins.value, 1)).toFixed(2))

// ── 진영 설정 ──────────────────────────────────────────────────
const factions = [
  { value: 'conservative', label: '보수', color: '#E24B4A', emoji: '🔴' },
  { value: 'moderate',     label: '중도', color: '#7F77DD', emoji: '🟣' },
  { value: 'progressive',  label: '진보', color: '#378ADD', emoji: '🔵' },
]
const boardTypeLabel = { azit: '아지트', battle: '전쟁터', playground: '놀이터', notice: '공지' }

const topicInput = reactive({ conservative: '', moderate: '', progressive: '' })

function addTopic(faction) {
  const val = topicInput[faction].trim()
  if (!val) return
  if (!form.topics[faction]) form.topics[faction] = []
  if (!form.topics[faction].includes(val)) form.topics[faction].push(val)
  topicInput[faction] = ''
}
function removeTopic(faction, idx) { form.topics[faction]?.splice(idx, 1) }

function isBoardChecked(faction, slug) { return (form.target_boards[faction] ?? []).includes(slug) }
function toggleBoard(faction, slug) {
  if (!form.target_boards[faction]) form.target_boards[faction] = []
  const arr = form.target_boards[faction]
  const idx = arr.indexOf(slug)
  idx >= 0 ? arr.splice(idx, 1) : arr.push(slug)
}

// ── 저장 ───────────────────────────────────────────────────────
function save() {
  saving.value = true
  saveMsg.value = ''
  router.put('/admin/auto-content', { ...form }, {
    preserveState: true,
    onSuccess: () => {
      saveMsg.value = '✅ 저장되었습니다.'
      saveMsgOk.value = true
      form.gemini_api_key  = ''
      form.pixabay_api_key = ''
    },
    onError: (e) => {
      saveMsg.value = '❌ ' + Object.values(e).join(', ')
      saveMsgOk.value = false
    },
    onFinish: () => { saving.value = false },
  })
}

// ── 즉시 실행 ──────────────────────────────────────────────────
async function runNow() {
  running.value = true
  runResult.value = ''
  try {
    const { data } = await axios.post('/admin/auto-content/run-now', {
      dry_run: isDryRun.value,
      date:    runDate.value,
    })
    runResult.value = data.message
    runOk.value = data.success
    if (data.success) refreshStats()
  } catch (e) {
    runResult.value = e.response?.data?.message ?? '실행 오류'
    runOk.value = false
  } finally {
    running.value = false
  }
}

async function refreshStats() {
  refreshing.value = true
  try {
    const { data } = await axios.get('/admin/auto-content/stats')
    lastRunAt.value    = data.last_run_at
    lastRunStats.value = data.last_run_stats
  } finally { refreshing.value = false }
}

function formatDate(iso) {
  if (!iso) return '-'
  return new Date(iso).toLocaleString('ko-KR', { dateStyle: 'short', timeStyle: 'short' })
}

const hourOptions = Array.from({ length: 25 }, (_, i) => i)
</script>

<template>
  <div class="p-6 max-w-6xl mx-auto space-y-6">

    <!-- 헤더 -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-white">🤖 AI 자동 콘텐츠 생성</h1>
        <p class="text-slate-400 text-sm mt-1">Gemini + RSS 뉴스 컨텍스트로 최신 뉴스 기반 게시글을 자동 생성합니다</p>
      </div>
      <span :class="[
        'px-3 py-1 rounded-full text-xs font-bold',
        config.is_enabled ? 'bg-emerald-500/20 text-emerald-400 ring-1 ring-emerald-500/30' : 'bg-slate-700 text-slate-400'
      ]">{{ config.is_enabled ? '활성화' : '비활성화' }}</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      <!-- ── 왼쪽: 설정 ─────────────────────────────────────── -->
      <div class="lg:col-span-2 space-y-5">

        <!-- API 키 -->
        <div class="bg-slate-900 rounded-2xl border border-slate-800 p-5 space-y-4">
          <h2 class="text-sm font-bold text-slate-300 uppercase tracking-wider">API 키 설정</h2>

          <!-- Gemini -->
          <div>
            <label class="block text-xs text-slate-400 mb-1.5">
              Gemini API 키
              <span v-if="config.gemini_api_key_set" class="ml-2 text-emerald-400">✓ 설정됨 ({{ config.gemini_api_key }})</span>
            </label>
            <div class="flex gap-2">
              <input v-model="form.gemini_api_key" type="password"
                :placeholder="config.gemini_api_key_set ? '변경하려면 새 키 입력' : 'AIza...'"
                class="flex-1 bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-violet-500" />
              <a href="https://aistudio.google.com/app/apikey" target="_blank"
                class="shrink-0 px-3 py-2 text-xs bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 rounded-lg transition-colors">
                발급 →
              </a>
            </div>
            <p class="text-[11px] text-slate-500 mt-1">
              현재 사용 모델: <strong class="text-slate-400">gemini-2.5-flash (RPM=5 / RPD=20)</strong>
              <span class="mx-1 text-slate-600">·</span> fallback: gemini-2.5-flash-lite
              <span class="mx-1 text-slate-600">·</span>
              <span class="text-amber-500/80">⚠️ gemini-2.0-flash는 2026년 6월 1일 종료</span>
              <span class="mx-1 text-slate-600">·</span>
              <span class="text-sky-400/80">뉴스 컨텍스트: Google News + 언론사 RSS 무료 수집</span>
            </p>
          </div>

          <!-- Pixabay -->
          <div>
            <label class="block text-xs text-slate-400 mb-1.5">
              Pixabay API 키
              <span class="text-slate-500">(이미지 삽입 시 필요 · 없으면 Lorem Picsum 폴백)</span>
              <span v-if="config.pixabay_api_key_set" class="ml-2 text-emerald-400">✓ 설정됨 ({{ config.pixabay_api_key }})</span>
            </label>
            <div class="flex gap-2">
              <input v-model="form.pixabay_api_key" type="password"
                :placeholder="config.pixabay_api_key_set ? '변경하려면 새 키 입력' : 'Pixabay API 키...'"
                class="flex-1 bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-violet-500" />
              <a href="https://pixabay.com/api/docs/" target="_blank"
                class="shrink-0 px-3 py-2 text-xs bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 rounded-lg transition-colors">
                발급 →
              </a>
            </div>
            <p class="text-[11px] text-slate-500 mt-1">무료 · 100 req/hour · 고화질 사진 검색</p>
          </div>

          <!-- 활성화 -->
          <div class="flex items-center justify-between py-2 border-t border-slate-800">
            <div>
              <p class="text-sm text-white font-medium">자동 생성 활성화</p>
              <p class="text-xs text-slate-500 mt-0.5">매일 05:50 자동 실행</p>
            </div>
            <button @click="form.is_enabled = !form.is_enabled"
              :class="['relative w-12 h-6 rounded-full transition-colors', form.is_enabled ? 'bg-emerald-500' : 'bg-slate-700']">
              <span :class="['absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform', form.is_enabled ? 'translate-x-6' : '']"/>
            </button>
          </div>
        </div>

        <!-- 미디어 옵션 -->
        <div class="bg-slate-900 rounded-2xl border border-slate-800 p-5 space-y-3">
          <h2 class="text-sm font-bold text-slate-300 uppercase tracking-wider">게시글 포함 요소</h2>

          <div class="grid grid-cols-2 gap-3">
            <!-- 뉴스 컨텍스트 사용 -->
            <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-colors"
              :class="form.use_grounding ? 'border-violet-600/60 bg-violet-600/10' : 'border-slate-700 bg-slate-800/50'">
              <input type="checkbox" v-model="form.use_grounding" class="mt-0.5 accent-violet-500" />
              <div>
                <p class="text-sm font-semibold text-white">📡 뉴스 컨텍스트 사용</p>
                <p class="text-[11px] text-slate-400 mt-0.5">RSS로 오늘의 실제 뉴스 수집 → Gemini 프롬프트에 주입</p>
              </div>
            </label>

            <!-- 이미지 -->
            <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-colors"
              :class="form.include_images ? 'border-violet-600/60 bg-violet-600/10' : 'border-slate-700 bg-slate-800/50'">
              <input type="checkbox" v-model="form.include_images" class="mt-0.5 accent-violet-500" />
              <div>
                <p class="text-sm font-semibold text-white">🖼 이미지 삽입</p>
                <p class="text-[11px] text-slate-400 mt-0.5">Pixabay 키워드 검색 → picsum 폴백</p>
              </div>
            </label>

            <!-- 뉴스 링크 -->
            <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-colors"
              :class="form.include_news_links ? 'border-violet-600/60 bg-violet-600/10' : 'border-slate-700 bg-slate-800/50'">
              <input type="checkbox" v-model="form.include_news_links" class="mt-0.5 accent-violet-500" />
              <div>
                <p class="text-sm font-semibold text-white">📰 뉴스 출처 링크</p>
                <p class="text-[11px] text-slate-400 mt-0.5">RSS 참고 기사 최대 3개 본문 하단 첨부</p>
              </div>
            </label>

            <!-- YouTube -->
            <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-colors"
              :class="form.include_youtube ? 'border-violet-600/60 bg-violet-600/10' : 'border-slate-700 bg-slate-800/50'">
              <input type="checkbox" v-model="form.include_youtube" class="mt-0.5 accent-violet-500" />
              <div>
                <p class="text-sm font-semibold text-white">▶ YouTube 검색 링크</p>
                <p class="text-[11px] text-slate-400 mt-0.5">관련 검색어 YouTube 링크 본문 하단 첨부</p>
              </div>
            </label>
          </div>

          <!-- 법적 안전 안내 -->
          <div class="bg-amber-950/30 border border-amber-800/40 rounded-xl p-3 text-xs text-amber-300/80 space-y-1">
            <p class="font-semibold text-amber-200">⚖️ 법적 안전 조치 (자동 적용)</p>
            <p>• 뉴스 내용 직접 복사 금지 — Gemini가 원본 의견으로 재작성</p>
            <p>• 특정인 명예훼손·허위사실·혐오표현 방지 프롬프트 내장</p>
            <p>• Gemini Safety Settings: BLOCK_ONLY_HIGH 적용</p>
            <p>• 출처 링크 명시로 정보 출처 투명성 확보</p>
          </div>
        </div>

        <!-- 수량/시간 설정 -->
        <div class="bg-slate-900 rounded-2xl border border-slate-800 p-5 space-y-4">
          <h2 class="text-sm font-bold text-slate-300 uppercase tracking-wider">생성 수량 · 시간 배분</h2>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs text-slate-400 mb-1.5">진영별 일일 게시글</label>
              <div class="flex items-center gap-2">
                <input type="number" v-model.number="form.posts_per_faction" min="1" max="500"
                  class="w-20 bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white text-center focus:outline-none focus:border-violet-500" />
                <span class="text-slate-400 text-xs">× 3진영 = <span class="text-violet-400 font-bold">{{ estPosts }}</span>개</span>
              </div>
              <!-- RPD 할당량 경고 (무료 RPD=20 기준) -->
              <p class="text-[11px] mt-1.5"
                :class="estPosts + estComments > 20 ? 'text-red-400' : estPosts + estComments > 15 ? 'text-amber-400' : 'text-slate-500'">
                예상 일일 API 콜: {{ estPosts + estComments }}회
                <template v-if="estPosts + estComments > 20"> ⚠️ 무료 RPD=20 초과 — flash-lite fallback 사용</template>
                <template v-else-if="estPosts + estComments > 15"> ⚠️ RPD 75% 이상 사용 (주의)</template>
                <template v-else> ✓ 무료 RPD=20 이내 안전</template>
              </p>
            </div>

            <div>
              <label class="block text-xs text-slate-400 mb-1.5">게시글당 댓글 (min~max)</label>
              <div class="flex items-center gap-2">
                <input type="number" v-model.number="form.comments_per_post_min" min="0" max="10"
                  class="w-14 bg-slate-800 border border-slate-700 rounded-lg px-2 py-2 text-sm text-white text-center focus:outline-none focus:border-violet-500" />
                <span class="text-slate-500 text-xs">~</span>
                <input type="number" v-model.number="form.comments_per_post_max" min="0" max="10"
                  class="w-14 bg-slate-800 border border-slate-700 rounded-lg px-2 py-2 text-sm text-white text-center focus:outline-none focus:border-violet-500" />
                <span class="text-slate-400 text-xs">≈ <span class="text-violet-400 font-bold">{{ estComments }}</span>개</span>
              </div>
            </div>

            <div>
              <label class="block text-xs text-slate-400 mb-1.5">시작 시각</label>
              <select v-model.number="form.start_hour"
                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-violet-500">
                <option v-for="h in hourOptions.slice(0, 24)" :key="h" :value="h">{{ String(h).padStart(2,'0') }}:00</option>
              </select>
            </div>

            <div>
              <label class="block text-xs text-slate-400 mb-1.5">종료 시각</label>
              <select v-model.number="form.end_hour"
                class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-violet-500">
                <option v-for="h in hourOptions.slice(1)" :key="h" :value="h">
                  {{ h === 24 ? '24:00 (자정)' : String(h).padStart(2,'0') + ':00' }}
                </option>
              </select>
            </div>
          </div>

          <div class="bg-slate-800/60 rounded-xl p-3 text-xs text-slate-400 grid grid-cols-2 gap-2">
            <p>⏱ 배포 윈도우: <span class="text-white">{{ windowMins }}분</span></p>
            <p>🕐 게시글 간격: <span class="text-white">약 {{ intervalMin.toFixed(1) }}분</span></p>
            <p>🤖 총 API 호출: <span class="text-white">{{ totalApiCalls }}회/일</span></p>
            <p>📊 예상 사용량: <span :class="parseFloat(rpmUsage) < 14 ? 'text-emerald-400' : 'text-amber-400'">{{ rpmUsage }} RPM</span> <span class="text-slate-600">(한도 15 RPM)</span></p>
          </div>
        </div>

        <!-- 타겟 게시판 -->
        <div class="bg-slate-900 rounded-2xl border border-slate-800 p-5 space-y-4">
          <h2 class="text-sm font-bold text-slate-300 uppercase tracking-wider">타겟 게시판</h2>
          <div v-for="f in factions" :key="f.value" class="space-y-2">
            <p class="text-xs font-semibold" :style="{ color: f.color }">{{ f.emoji }} {{ f.label }}</p>
            <div class="flex flex-wrap gap-2">
              <label v-for="board in boards.filter(b => ['azit','battle'].includes(b.board_type))"
                :key="board.slug"
                class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border cursor-pointer text-xs transition-colors"
                :class="isBoardChecked(f.value, board.slug)
                  ? 'bg-violet-600/20 border-violet-500 text-violet-300'
                  : 'border-slate-700 text-slate-400 hover:border-slate-600'">
                <input type="checkbox" :checked="isBoardChecked(f.value, board.slug)"
                  @change="toggleBoard(f.value, board.slug)" class="hidden" />
                <span class="text-[10px] bg-slate-700 rounded px-1 py-0.5">{{ boardTypeLabel[board.board_type] }}</span>
                {{ board.name }}
              </label>
            </div>
          </div>
        </div>

        <!-- 주제 키워드 -->
        <div class="bg-slate-900 rounded-2xl border border-slate-800 p-5 space-y-4">
          <div class="flex items-start justify-between">
            <h2 class="text-sm font-bold text-slate-300 uppercase tracking-wider">진영별 주제 키워드</h2>
            <p class="text-[11px] text-slate-500">뉴스 컨텍스트 ON: RSS에서 관련 기사 자동 수집</p>
          </div>
          <div v-for="f in factions" :key="f.value" class="space-y-2">
            <p class="text-xs font-semibold" :style="{ color: f.color }">{{ f.emoji }} {{ f.label }}</p>
            <div class="flex flex-wrap gap-1.5 mb-2">
              <span v-for="(topic, i) in (form.topics[f.value] ?? [])" :key="i"
                class="flex items-center gap-1 bg-slate-800 border border-slate-700 text-slate-300 rounded-full px-2.5 py-1 text-xs">
                {{ topic }}
                <button @click="removeTopic(f.value, i)" class="text-slate-500 hover:text-red-400 transition-colors">×</button>
              </span>
            </div>
            <div class="flex gap-2">
              <input v-model="topicInput[f.value]" @keyup.enter="addTopic(f.value)"
                type="text" placeholder="주제 추가 후 Enter"
                class="flex-1 bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-white placeholder-slate-600 focus:outline-none focus:border-violet-500" />
              <button @click="addTopic(f.value)"
                class="px-3 py-1.5 text-xs bg-violet-600 hover:bg-violet-500 text-white rounded-lg transition-colors">추가</button>
            </div>
          </div>
        </div>

        <!-- 저장 -->
        <div class="flex items-center gap-3">
          <button @click="save" :disabled="saving"
            class="px-6 py-2.5 bg-violet-600 hover:bg-violet-500 disabled:opacity-50 text-white text-sm font-semibold rounded-xl transition-colors">
            {{ saving ? '저장 중...' : '설정 저장' }}
          </button>
          <span v-if="saveMsg" :class="saveMsgOk ? 'text-emerald-400' : 'text-red-400'" class="text-sm">{{ saveMsg }}</span>
        </div>
      </div>

      <!-- ── 오른쪽: 실행 패널 ─────────────────────────────────── -->
      <div class="space-y-5">

        <!-- 즉시 실행 -->
        <div class="bg-slate-900 rounded-2xl border border-slate-800 p-5 space-y-4">
          <h2 class="text-sm font-bold text-slate-300 uppercase tracking-wider">즉시 실행</h2>

          <div>
            <label class="block text-xs text-slate-400 mb-1.5">대상 날짜</label>
            <input type="date" v-model="runDate"
              class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-violet-500" />
          </div>

          <label class="flex items-center gap-2.5 cursor-pointer">
            <input type="checkbox" v-model="isDryRun" class="w-4 h-4 rounded accent-violet-500" />
            <span class="text-sm text-slate-300">DRY RUN <span class="text-slate-500 text-xs">(계획만 출력, 실제 Job 없음)</span></span>
          </label>

          <button @click="runNow" :disabled="running || !config.gemini_api_key_set"
            class="w-full py-2.5 rounded-xl text-sm font-bold transition-colors disabled:opacity-40"
            :class="isDryRun
              ? 'bg-amber-600/30 hover:bg-amber-600/50 text-amber-400 border border-amber-600/40'
              : 'bg-emerald-600 hover:bg-emerald-500 text-white'">
            {{ running ? '큐 등록 중...' : isDryRun ? '🔍 DRY RUN 실행' : '▶ 지금 실행' }}
          </button>

          <p v-if="!config.gemini_api_key_set" class="text-xs text-red-400 text-center">Gemini API 키 설정 필요</p>

          <div v-if="runResult" :class="runOk
              ? 'bg-emerald-900/30 border-emerald-700/40 text-emerald-300'
              : 'bg-red-900/30 border-red-700/40 text-red-300'"
            class="rounded-xl border p-3 text-xs leading-relaxed">
            {{ runResult }}
          </div>
        </div>

        <!-- 마지막 실행 -->
        <div class="bg-slate-900 rounded-2xl border border-slate-800 p-5 space-y-3">
          <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-300 uppercase tracking-wider">마지막 실행</h2>
            <button @click="refreshStats" :disabled="refreshing"
              class="text-xs text-slate-500 hover:text-slate-300 transition-colors disabled:opacity-40">
              {{ refreshing ? '...' : '↻' }}
            </button>
          </div>
          <div class="text-xs text-slate-400 space-y-1.5">
            <p>📅 <span class="text-white">{{ formatDate(lastRunAt) }}</span></p>
            <template v-if="lastRunStats">
              <p>🗓 대상일: <span class="text-white">{{ lastRunStats.target_date ?? '-' }}</span></p>
              <p>📋 게시글: <span class="text-violet-400 font-bold text-sm">{{ lastRunStats.posts_dispatched ?? 0 }}</span>개 예약</p>
              <p>💬 댓글: <span class="text-violet-400 font-bold text-sm">{{ lastRunStats.comments_dispatched ?? 0 }}</span>개 예약</p>
              <span v-if="lastRunStats.dry_run" class="inline-block px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-[10px]">DRY RUN</span>
            </template>
            <p v-else class="text-slate-600 italic">실행 이력 없음</p>
          </div>
        </div>

        <!-- 일일 추정 -->
        <div class="bg-slate-900 rounded-2xl border border-slate-800 p-5 space-y-3">
          <h2 class="text-sm font-bold text-slate-300 uppercase tracking-wider">일일 추정</h2>
          <div class="space-y-2">
            <div v-for="f in factions" :key="f.value" class="flex items-center gap-2 text-xs">
              <span>{{ f.emoji }}</span>
              <div class="flex-1">
                <div class="flex justify-between mb-1">
                  <span class="text-slate-400">{{ f.label }}</span>
                  <span :style="{ color: f.color }" class="font-bold">{{ form.posts_per_faction }}개</span>
                </div>
                <div class="h-1.5 bg-slate-800 rounded-full overflow-hidden">
                  <div class="h-full rounded-full transition-all" :style="{ width: (form.posts_per_faction / 500 * 100) + '%', backgroundColor: f.color }" />
                </div>
              </div>
            </div>
          </div>
          <div class="border-t border-slate-800 pt-3 space-y-1 text-xs">
            <div class="flex justify-between text-slate-400">
              <span>총 게시글</span><span class="text-white font-bold">{{ estPosts }}개</span>
            </div>
            <div class="flex justify-between text-slate-400">
              <span>총 댓글(추정)</span><span class="text-white font-bold">{{ estComments }}개</span>
            </div>
          </div>
        </div>

        <!-- 사용 안내 -->
        <div class="bg-blue-950/40 rounded-2xl border border-blue-900/40 p-4 space-y-2 text-xs text-blue-300/80">
          <p class="font-bold text-blue-200">📌 동작 구조</p>
          <div class="space-y-1.5 leading-relaxed">
            <p>① <span class="text-blue-200">05:50</span> 스케줄러 → Job 예약 (300개)</p>
            <p>② 06:00~24:00 사이 <span class="text-blue-200">3.6분 간격</span> 자동 발행</p>
            <p>③ Gemini가 <span class="text-blue-200">최신 뉴스 검색</span> 후 진영별 논평 작성</p>
            <p>④ <span class="text-blue-200">Pixabay 이미지</span> 자동 삽입 (본문 내)</p>
            <p>⑤ <span class="text-blue-200">뉴스 출처</span> + YouTube 링크 본문 하단 첨부</p>
            <p>⑥ 발행 5~20분 후 <span class="text-blue-200">댓글 자동 추가</span></p>
          </div>
          <p class="text-[11px] text-blue-400/50 mt-2">로그: storage/logs/generate-daily-content.log</p>
        </div>
      </div>
    </div>
  </div>
</template>
