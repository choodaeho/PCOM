<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  run:           Object,
  faction_stats: Array,
  error_entries: Array,
})

// ── 탭 ───────────────────────────────────────────────
const activeTab   = ref('all')   // 'all' | 'errors'

// ── 항목 로드 (AJAX) ─────────────────────────────────
const entries       = ref(null)
const loadingEntries = ref(false)
const entryError    = ref(null)
const filterType    = ref('')
const filterFaction = ref('')
const filterStatus  = ref('')
const currentPage   = ref(1)

const loadEntries = async (page = 1) => {
  loadingEntries.value = true
  entryError.value     = null
  currentPage.value    = page

  const params = new URLSearchParams({ page })
  if (filterType.value)    params.set('type',    filterType.value)
  if (filterFaction.value) params.set('faction', filterFaction.value)
  if (filterStatus.value)  params.set('status',  filterStatus.value)

  try {
    const res = await window.axios.get(
      `/admin/auto-content/logs/${props.run.id}/entries?${params.toString()}`
    )
    entries.value = res.data.entries
  } catch (e) {
    entryError.value = '항목을 불러오지 못했습니다.'
  } finally {
    loadingEntries.value = false
  }
}

// ── 자동 새로고침 (실행 중 / 중지 중) ───────────────
const runData       = ref({ ...props.run })
const refreshTimer  = ref(null)

const refreshStats = async () => {
  try {
    const res = await window.axios.get(
      `/admin/auto-content/logs/${props.run.id}/entries?page=1&_stats_only=1`
    )
    runData.value = { ...runData.value, ...res.data.run }
  } catch (_) {}
}

const isLive = computed(() =>
  ['running', 'completed', 'stopping'].includes(runData.value.status) && !runData.value.is_stopped
)

onMounted(() => {
  loadEntries()
  if (isLive.value) {
    refreshTimer.value = setInterval(() => {
      refreshStats()
      loadEntries(currentPage.value)
    }, 15000)
  }
})

onUnmounted(() => {
  if (refreshTimer.value) clearInterval(refreshTimer.value)
})

// ── 중지 ─────────────────────────────────────────────
const stopping    = ref(false)
const stopMessage = ref('')

const requestStop = async () => {
  if (!confirm('이 실행을 중지하시겠습니까?\n현재 처리 중인 Job은 완료 후 이후 Job부터 건너뜁니다.')) return
  stopping.value = true
  try {
    const res = await window.axios.post(`/admin/auto-content/logs/${props.run.id}/stop`)
    stopMessage.value   = res.data.message
    runData.value.is_stopped = true
    runData.value.status     = 'stopping'
    if (refreshTimer.value) { clearInterval(refreshTimer.value); refreshTimer.value = null }
    setTimeout(() => loadEntries(currentPage.value), 1000)
  } catch (e) {
    stopMessage.value = e.response?.data?.message ?? '중지 요청 실패'
  } finally {
    stopping.value = false
  }
}

// ── 포맷 ─────────────────────────────────────────────
const fmtDate = (iso) => iso ? new Date(iso).toLocaleDateString('ko-KR') : '-'
const fmtDateTime = (iso) => {
  if (!iso) return '-'
  const d = new Date(iso)
  return d.toLocaleDateString('ko-KR', { month:'2-digit', day:'2-digit' })
    + ' ' + d.toLocaleTimeString('ko-KR', { hour:'2-digit', minute:'2-digit', second:'2-digit' })
}
const fmtTime = (iso) => iso
  ? new Date(iso).toLocaleTimeString('ko-KR', { hour:'2-digit', minute:'2-digit', second:'2-digit' })
  : '-'
const fmtElapsed = (secs) => {
  if (!secs && secs !== 0) return '-'
  const h = Math.floor(secs / 3600)
  const m = Math.floor((secs % 3600) / 60)
  const s = secs % 60
  if (h > 0) return `${h}시간 ${m}분`
  if (m > 0) return `${m}분 ${s}초`
  return s + '초'
}

const runTypeLabel = (t) => ({ scheduled:'자동(스케줄)', manual:'수동', dry_run:'DRY RUN' }[t] ?? t)

const statusConfig = computed(() => {
  if (runData.value.is_stopped)          return { label:'중지됨',  cls:'bg-orange-900/50 text-orange-300 border-orange-700/50', pulse:false }
  if (runData.value.status==='stopping') return { label:'중지 중', cls:'bg-orange-900/50 text-orange-300 border-orange-700/50', pulse:true  }
  if (runData.value.status==='running')  return { label:'실행 중', cls:'bg-emerald-900/40 text-emerald-300 border-emerald-700/40', pulse:true }
  if (runData.value.status==='completed')return { label:'완료',    cls:'bg-slate-700/60 text-slate-300 border-slate-600/50', pulse:false }
  if (runData.value.status==='failed')   return { label:'실패',    cls:'bg-red-900/40 text-red-300 border-red-700/40', pulse:false }
  return { label: runData.value.status, cls:'bg-slate-700 text-slate-400', pulse:false }
})

// 진행률
const postPct    = computed(() => runData.value.posts_dispatched    ? Math.min(100, Math.round(runData.value.posts_succeeded    / runData.value.posts_dispatched    * 100)) : 0)
const commentPct = computed(() => runData.value.comments_dispatched ? Math.min(100, Math.round(runData.value.comments_succeeded / runData.value.comments_dispatched * 100)) : 0)

// 진영별 집계 가공
const factionSummary = computed(() => {
  const map = {}
  for (const row of props.faction_stats) {
    if (!map[row.faction]) map[row.faction] = { post:{}, comment:{} }
    if (!map[row.faction][row.entry_type]) map[row.faction][row.entry_type] = {}
    map[row.faction][row.entry_type][row.status] = row.cnt
  }
  return Object.entries(map).map(([faction, types]) => ({
    faction,
    label:           { conservative:'보수', moderate:'중도', progressive:'진보' }[faction] ?? faction,
    post_success:    types.post?.success  ?? 0,
    post_failed:     types.post?.failed   ?? 0,
    comment_success: types.comment?.success ?? 0,
    comment_failed:  types.comment?.failed  ?? 0,
  }))
})

const factionBadge = (f) => ({
  conservative: 'bg-red-900/40 text-red-300 border-red-800/40',
  moderate:     'bg-violet-900/40 text-violet-300 border-violet-800/40',
  progressive:  'bg-blue-900/40 text-blue-300 border-blue-800/40',
}[f] ?? 'bg-slate-700 text-slate-400 border-slate-600')

const entryStatusStyle = (s) => ({
  success: { icon:'✓', cls:'text-emerald-400', row:'' },
  failed:  { icon:'✗', cls:'text-red-400',     row:'bg-red-900/8' },
  skipped: { icon:'⏭', cls:'text-slate-500',   row:'' },
}[s] ?? { icon:'?', cls:'text-slate-500', row:'' })

// 필터 변경
const applyFilter = () => loadEntries(1)

// 오류 탭 진입 시
const switchTab = (tab) => {
  activeTab.value = tab
  if (tab === 'all') {
    filterStatus.value = ''
    loadEntries(1)
  }
}

// 항목 페이지네이션
const entryGoPage = (url) => {
  if (!url) return
  const page = parseInt(new URL(url).searchParams.get('page') ?? '1')
  loadEntries(page)
}
</script>

<template>
  <AdminLayout>
    <div class="p-6 max-w-screen-xl mx-auto">

      <!-- 헤더 -->
      <div class="flex items-start justify-between mb-6">
        <div class="flex items-start gap-4">
          <Link href="/admin/auto-content/logs"
            class="mt-0.5 text-slate-400 hover:text-white transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
          </Link>
          <div>
            <div class="flex items-center gap-3 flex-wrap">
              <h1 class="text-xl font-bold text-white">실행 #{{ run.id }}</h1>
              <span class="text-slate-400 text-sm">{{ fmtDate(run.run_date) }}</span>
              <span class="text-xs px-2 py-0.5 rounded-full border font-medium"
                :class="statusConfig.cls">
                <span v-if="statusConfig.pulse" class="inline-block w-1.5 h-1.5 rounded-full bg-current animate-pulse mr-1"></span>
                {{ statusConfig.label }}
              </span>
              <span class="text-xs px-2 py-0.5 rounded bg-slate-700 text-slate-300">{{ runTypeLabel(run.run_type) }}</span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
              시작: {{ fmtDateTime(run.started_at) }}
              <span v-if="run.triggered_by"> · {{ run.triggered_by }}</span>
              <span v-if="run.elapsed_seconds"> · 경과: {{ fmtElapsed(run.elapsed_seconds) }}</span>
            </p>
          </div>
        </div>

        <!-- 중지 버튼 -->
        <div class="flex flex-col items-end gap-2">
          <button v-if="runData.is_stoppable ?? run.is_stoppable"
            @click="requestStop" :disabled="stopping"
            class="flex items-center gap-1.5 text-sm bg-red-800/60 hover:bg-red-700/80 disabled:opacity-50 text-red-300 border border-red-700/50 px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
              <rect x="6" y="6" width="12" height="12" rx="1.5"/>
            </svg>
            {{ stopping ? '처리 중...' : '실행 중지' }}
          </button>
          <span v-if="stopMessage" class="text-xs text-orange-300 max-w-xs text-right">{{ stopMessage }}</span>
          <span v-if="isLive" class="text-xs text-emerald-400 flex items-center gap-1">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            15초마다 자동 갱신
          </span>
        </div>
      </div>

      <!-- 진행률 카드 -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
        <!-- 게시글 -->
        <div class="bg-slate-800/60 border border-slate-700 rounded-xl p-4">
          <p class="text-xs text-slate-500 mb-2">📝 게시글</p>
          <div class="flex items-baseline gap-1 mb-2">
            <span class="text-2xl font-bold text-emerald-400">{{ runData.posts_succeeded }}</span>
            <span class="text-slate-500 text-sm">/ {{ runData.posts_dispatched }}</span>
          </div>
          <div class="h-2 bg-slate-700 rounded-full overflow-hidden mb-1.5">
            <div class="h-full bg-emerald-500 rounded-full transition-all duration-700"
              :style="{ width: postPct + '%' }"></div>
          </div>
          <div class="flex justify-between text-xs">
            <span class="text-slate-500">{{ postPct }}%</span>
            <span v-if="runData.posts_failed > 0" class="text-red-400">✗ {{ runData.posts_failed }} 실패</span>
            <span v-if="runData.posts_skipped > 0" class="text-orange-400">⏭ {{ runData.posts_skipped }} 건너뜀</span>
          </div>
        </div>

        <!-- 댓글 -->
        <div class="bg-slate-800/60 border border-slate-700 rounded-xl p-4">
          <p class="text-xs text-slate-500 mb-2">💬 댓글</p>
          <div class="flex items-baseline gap-1 mb-2">
            <span class="text-2xl font-bold text-blue-400">{{ runData.comments_succeeded }}</span>
            <span class="text-slate-500 text-sm">/ {{ runData.comments_dispatched }}</span>
          </div>
          <div class="h-2 bg-slate-700 rounded-full overflow-hidden mb-1.5">
            <div class="h-full bg-blue-500 rounded-full transition-all duration-700"
              :style="{ width: commentPct + '%' }"></div>
          </div>
          <div class="flex justify-between text-xs">
            <span class="text-slate-500">{{ commentPct }}%</span>
            <span v-if="runData.comments_failed > 0" class="text-red-400">✗ {{ runData.comments_failed }} 실패</span>
          </div>
        </div>

        <!-- 오류 -->
        <div class="bg-slate-800/60 border border-slate-700 rounded-xl p-4">
          <p class="text-xs text-slate-500 mb-2">⚠️ 오류</p>
          <p class="text-2xl font-bold mb-1"
            :class="(runData.posts_failed + runData.comments_failed) > 0 ? 'text-red-400' : 'text-slate-400'">
            {{ runData.posts_failed + runData.comments_failed }}
          </p>
          <p class="text-xs text-slate-500">건 실패</p>
        </div>

        <!-- 소요 시간 -->
        <div class="bg-slate-800/60 border border-slate-700 rounded-xl p-4">
          <p class="text-xs text-slate-500 mb-2">⏱ 소요 시간</p>
          <p class="text-2xl font-bold text-slate-200 mb-1">{{ fmtElapsed(run.elapsed_seconds) }}</p>
          <p class="text-xs text-slate-500">
            완료: {{ fmtTime(run.last_activity_at) }}
          </p>
        </div>
      </div>

      <!-- 진영별 통계 -->
      <div v-if="factionSummary.length" class="bg-slate-800/60 border border-slate-700 rounded-xl p-4 mb-5">
        <h3 class="text-xs font-semibold text-slate-400 mb-3 uppercase tracking-wider">진영별 현황</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div v-for="fs in factionSummary" :key="fs.faction">
            <div class="flex items-center gap-2 mb-2">
              <span :class="['text-xs px-2 py-0.5 rounded border font-medium', factionBadge(fs.faction)]">
                {{ fs.label }}
              </span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs">
              <div class="bg-slate-900/40 rounded-lg p-2">
                <p class="text-slate-500 mb-0.5">게시글</p>
                <span class="text-emerald-400 font-bold">{{ fs.post_success }}</span>
                <span v-if="fs.post_failed > 0" class="text-red-400 ml-1">/ {{ fs.post_failed }}✗</span>
              </div>
              <div class="bg-slate-900/40 rounded-lg p-2">
                <p class="text-slate-500 mb-0.5">댓글</p>
                <span class="text-blue-400 font-bold">{{ fs.comment_success }}</span>
                <span v-if="fs.comment_failed > 0" class="text-red-400 ml-1">/ {{ fs.comment_failed }}✗</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 탭 -->
      <div class="flex items-center gap-1 mb-4 border-b border-slate-700">
        <button @click="switchTab('all')"
          :class="['px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px',
            activeTab==='all'
              ? 'border-violet-500 text-violet-400'
              : 'border-transparent text-slate-400 hover:text-white']">
          전체 로그
          <span v-if="entries" class="ml-1.5 text-xs bg-slate-700 text-slate-400 px-1.5 py-0.5 rounded-full">
            {{ entries.total }}
          </span>
        </button>
        <button @click="switchTab('errors')"
          :class="['px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px',
            activeTab==='errors'
              ? 'border-red-500 text-red-400'
              : 'border-transparent text-slate-400 hover:text-white']">
          오류 로그
          <span v-if="error_entries.length > 0"
            class="ml-1.5 text-xs bg-red-900/50 text-red-300 px-1.5 py-0.5 rounded-full font-bold">
            {{ error_entries.length }}
          </span>
        </button>
      </div>

      <!-- ══ 전체 로그 탭 ══════════════════════════════ -->
      <div v-if="activeTab === 'all'">
        <!-- 필터 -->
        <div class="flex flex-wrap items-center gap-2 mb-3">
          <select v-model="filterType" @change="applyFilter"
            class="bg-slate-800 border border-slate-600 rounded-lg text-xs text-white px-3 py-1.5 focus:outline-none focus:border-violet-500">
            <option value="">전체 유형</option>
            <option value="post">📝 게시글</option>
            <option value="comment">💬 댓글</option>
          </select>
          <select v-model="filterFaction" @change="applyFilter"
            class="bg-slate-800 border border-slate-600 rounded-lg text-xs text-white px-3 py-1.5 focus:outline-none focus:border-violet-500">
            <option value="">전체 진영</option>
            <option value="conservative">🔴 보수</option>
            <option value="moderate">🟣 중도</option>
            <option value="progressive">🔵 진보</option>
          </select>
          <select v-model="filterStatus" @change="applyFilter"
            class="bg-slate-800 border border-slate-600 rounded-lg text-xs text-white px-3 py-1.5 focus:outline-none focus:border-violet-500">
            <option value="">전체 결과</option>
            <option value="success">✓ 성공</option>
            <option value="failed">✗ 실패</option>
            <option value="skipped">⏭ 건너뜀</option>
          </select>
          <button @click="loadEntries(currentPage)"
            class="text-xs text-slate-400 hover:text-white border border-slate-600 px-2.5 py-1.5 rounded-lg transition-colors flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            새로고침
          </button>
          <span v-if="loadingEntries" class="text-xs text-slate-500 flex items-center gap-1">
            <svg class="w-3 h-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
            로딩 중...
          </span>
        </div>

        <!-- 항목 테이블 -->
        <div class="bg-slate-800/60 border border-slate-700 rounded-xl overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-xs">
              <thead>
                <tr class="border-b border-slate-700 text-slate-400">
                  <th class="text-left px-4 py-2.5 font-medium">시각</th>
                  <th class="text-left px-4 py-2.5 font-medium">유형</th>
                  <th class="text-left px-4 py-2.5 font-medium">진영</th>
                  <th class="text-left px-4 py-2.5 font-medium">닉네임</th>
                  <th class="text-left px-4 py-2.5 font-medium">게시판</th>
                  <th class="text-left px-4 py-2.5 font-medium max-w-[200px]">주제 / 제목</th>
                  <th class="text-center px-4 py-2.5 font-medium">결과</th>
                  <th class="text-right px-4 py-2.5 font-medium">소요</th>
                  <th class="px-4 py-2.5 font-medium">오류 메시지</th>
                  <th class="px-4 py-2.5"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loadingEntries && !entries">
                  <td colspan="10" class="px-4 py-10 text-center text-slate-500">
                    <svg class="w-5 h-5 animate-spin mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    불러오는 중...
                  </td>
                </tr>
                <tr v-else-if="entryError">
                  <td colspan="10" class="px-4 py-8 text-center text-red-400">{{ entryError }}</td>
                </tr>
                <tr v-else-if="!entries?.data?.length">
                  <td colspan="10" class="px-4 py-8 text-center text-slate-500">항목이 없습니다.</td>
                </tr>
                <tr v-for="e in entries?.data" :key="e.id"
                  :class="['border-b border-slate-700/30 transition-colors',
                    e.status === 'failed' ? 'bg-red-950/20 hover:bg-red-950/30' : 'hover:bg-slate-700/20']">
                  <td class="px-4 py-2.5 text-slate-400 whitespace-nowrap tabular-nums">{{ fmtTime(e.executed_at) }}</td>
                  <td class="px-4 py-2.5 whitespace-nowrap">
                    <span class="text-slate-300">{{ e.entry_type === 'post' ? '📝' : '💬' }}</span>
                    <span class="text-slate-500 ml-1">{{ e.entry_type === 'post' ? '게시글' : '댓글' }}</span>
                  </td>
                  <td class="px-4 py-2.5">
                    <span :class="['px-1.5 py-0.5 rounded border text-xs font-medium', factionBadge(e.faction)]">
                      {{ e.faction_label }}
                    </span>
                  </td>
                  <td class="px-4 py-2.5 text-slate-300 whitespace-nowrap">{{ e.nickname ?? '-' }}</td>
                  <td class="px-4 py-2.5 text-slate-400 whitespace-nowrap">{{ e.board_name ?? '-' }}</td>
                  <td class="px-4 py-2.5 max-w-[200px]">
                    <p v-if="e.title" class="text-slate-200 truncate" :title="e.title">{{ e.title }}</p>
                    <p v-else-if="e.topic" class="text-slate-500 truncate italic" :title="e.topic">{{ e.topic }}</p>
                    <span v-else class="text-slate-600">-</span>
                  </td>
                  <td class="px-4 py-2.5 text-center">
                    <span :class="['font-bold text-sm', entryStatusStyle(e.status).cls]">
                      {{ entryStatusStyle(e.status).icon }}
                    </span>
                  </td>
                  <td class="px-4 py-2.5 text-right text-slate-400 whitespace-nowrap tabular-nums">{{ e.duration_fmt ?? '-' }}</td>
                  <td class="px-4 py-2.5 text-red-400 max-w-[240px]">
                    <span v-if="e.error_message" class="truncate block" :title="e.error_message">
                      {{ e.error_message }}
                    </span>
                    <span v-else class="text-slate-600">-</span>
                  </td>
                  <td class="px-4 py-2.5">
                    <a v-if="e.post_id && e.board_slug"
                      :href="`/boards/${e.board_slug}/posts/${e.post_id}`" target="_blank"
                      class="text-violet-400 hover:text-violet-300 transition-colors text-xs">→</a>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- 항목 페이지네이션 -->
          <div v-if="entries?.last_page > 1"
            class="px-4 py-3 border-t border-slate-700 flex items-center justify-between">
            <span class="text-xs text-slate-500">
              {{ entries.from }}–{{ entries.to }} / {{ entries.total }}건
            </span>
            <div class="flex items-center gap-1">
              <button v-for="link in entries.links" :key="link.label"
                @click="entryGoPage(link.url)"
                :disabled="!link.url || link.active"
                v-html="link.label"
                :class="[
                  'px-2.5 py-1 rounded text-xs transition-colors',
                  link.active   ? 'bg-violet-600 text-white' :
                  link.url      ? 'text-slate-400 hover:bg-slate-700' :
                                  'text-slate-600 cursor-not-allowed'
                ]">
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ══ 오류 로그 탭 ═══════════════════════════════ -->
      <div v-if="activeTab === 'errors'">
        <div v-if="!error_entries.length"
          class="bg-slate-800/60 border border-slate-700 rounded-xl p-10 text-center">
          <p class="text-2xl mb-2">✅</p>
          <p class="text-slate-400 text-sm">오류 로그가 없습니다.</p>
        </div>

        <div v-else class="bg-slate-950 border border-slate-700 rounded-xl overflow-hidden">
          <!-- 터미널 헤더 -->
          <div class="flex items-center gap-2 px-4 py-2.5 bg-slate-900 border-b border-slate-700">
            <div class="w-2.5 h-2.5 rounded-full bg-red-500"></div>
            <div class="w-2.5 h-2.5 rounded-full bg-yellow-500"></div>
            <div class="w-2.5 h-2.5 rounded-full bg-green-500"></div>
            <span class="text-xs text-slate-400 ml-2 font-mono">error.log — {{ error_entries.length }}건의 오류</span>
          </div>

          <!-- 오류 항목들 -->
          <div class="p-4 space-y-0 font-mono text-xs max-h-[800px] overflow-y-auto">
            <div v-for="(e, idx) in error_entries" :key="e.id"
              :class="['py-3 border-b border-slate-800/60 last:border-0', idx % 2 === 0 ? 'bg-red-950/5' : '']">

              <!-- 타임스탬프 + 레벨 -->
              <div class="flex items-start gap-3 flex-wrap">
                <span class="text-slate-500 whitespace-nowrap shrink-0">{{ fmtTime(e.executed_at) }}</span>
                <span class="text-red-500 font-bold shrink-0">ERROR</span>
                <span :class="['shrink-0', {
                  'text-red-300':    e.entry_type === 'post',
                  'text-orange-300': e.entry_type === 'comment',
                }]">
                  [{{ e.entry_type === 'post' ? 'GenerateAIPostJob' : 'GenerateAICommentJob' }}]
                </span>
              </div>

              <!-- 컨텍스트 정보 -->
              <div class="mt-1.5 ml-0 pl-4 border-l-2 border-red-900/50 space-y-0.5 text-slate-400">
                <div class="flex flex-wrap gap-x-4 gap-y-0.5">
                  <span>진영: <span :class="{
                    'text-red-300':    e.faction === 'conservative',
                    'text-violet-300': e.faction === 'moderate',
                    'text-blue-300':   e.faction === 'progressive',
                  }">{{ e.faction_label }}</span></span>
                  <span v-if="e.nickname">닉네임: <span class="text-slate-300">{{ e.nickname }}</span></span>
                  <span v-if="e.board_name">게시판: <span class="text-slate-300">{{ e.board_name }}</span></span>
                  <span v-if="e.duration_fmt">소요: <span class="text-slate-300">{{ e.duration_fmt }}</span></span>
                </div>
                <div v-if="e.topic || e.title" class="text-slate-500">
                  {{ e.entry_type === 'post' ? '주제' : '게시글' }}:
                  <span class="text-slate-300">{{ e.title || e.topic }}</span>
                </div>
              </div>

              <!-- 오류 메시지 -->
              <div class="mt-1.5 flex items-start gap-2">
                <span class="text-red-600 shrink-0">→</span>
                <span class="text-red-300 break-all">{{ e.error_message ?? '(오류 메시지 없음)' }}</span>
              </div>

              <!-- 게시글 링크 (있으면) -->
              <div v-if="e.parent_post_id && e.board_slug" class="mt-1 ml-4">
                <a :href="`/boards/${e.board_slug}/posts/${e.parent_post_id}`" target="_blank"
                  class="text-violet-400 hover:underline text-xs">
                  → 원본 게시글 #{{ e.parent_post_id }} 보기
                </a>
              </div>
            </div>
          </div>

          <!-- 푸터 -->
          <div class="px-4 py-2.5 bg-slate-900 border-t border-slate-700 text-xs text-slate-500 font-mono flex items-center justify-between">
            <span>총 {{ error_entries.length }}건 (최대 200건 표시)</span>
            <span v-if="run.stopped_at">중지 시각: {{ fmtTime(run.stopped_at) }}</span>
          </div>
        </div>
      </div>

    </div>
  </AdminLayout>
</template>
