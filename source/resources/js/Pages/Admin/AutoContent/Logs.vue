<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  runs:    Object,   // paginated
  summary: Object,
})

// ── 중지 처리 ─────────────────────────────────────────
const stoppingId  = ref(null)
const stopError   = ref(null)

const requestStop = async (run) => {
  if (!confirm(`실행 #${run.id} (${fmtDate(run.run_date)})를 중지하시겠습니까?\n\n현재 처리 중인 Job은 완료 후 이후 Job부터 건너뜁니다.`)) return
  stoppingId.value = run.id
  stopError.value  = null
  try {
    await window.axios.post(`/admin/auto-content/logs/${run.id}/stop`)
    router.reload({ only: ['runs', 'summary'] })
  } catch (e) {
    stopError.value = e.response?.data?.message ?? '중지 요청에 실패했습니다.'
  } finally {
    stoppingId.value = null
  }
}

// ── 로그 정리 ─────────────────────────────────────────
const showCleanup   = ref(false)
const cleanupDays   = ref(30)
const cleaningUp    = ref(false)
const cleanupResult = ref(null)

const doCleanup = async () => {
  const isAll = cleanupDays.value === 0
  const label = isAll ? '모든 로그' : `${cleanupDays.value}일 이전 로그`
  if (!confirm(`${label}를 삭제하시겠습니까?\n이 작업은 되돌릴 수 없습니다.`)) return

  cleaningUp.value    = true
  cleanupResult.value = null
  try {
    const res = await window.axios.delete('/admin/auto-content/logs/cleanup', {
      data: { days: cleanupDays.value }
    })
    cleanupResult.value = res.data
    router.reload({ only: ['runs', 'summary'] })
  } catch (e) {
    cleanupResult.value = { success: false, message: e.response?.data?.message ?? '오류 발생' }
  } finally {
    cleaningUp.value = false
  }
}

// ── 포맷 헬퍼 ─────────────────────────────────────────
const fmtDate = (iso) => iso ? new Date(iso).toLocaleDateString('ko-KR', { year:'2-digit', month:'2-digit', day:'2-digit' }) : '-'
const fmtTime = (iso) => {
  if (!iso) return '-'
  return new Date(iso).toLocaleTimeString('ko-KR', { hour: '2-digit', minute: '2-digit' })
}
const fmtElapsed = (secs) => {
  if (!secs && secs !== 0) return '-'
  if (secs < 60)  return secs + '초'
  const h = Math.floor(secs / 3600)
  const m = Math.floor((secs % 3600) / 60)
  const s = secs % 60
  if (h > 0) return `${h}시간 ${m}분`
  return `${m}분 ${s}초`
}

const runTypeLabel = (t) => ({ scheduled:'자동(스케줄)', manual:'수동', dry_run:'DRY RUN' }[t] ?? t)

const statusConfig = (run) => {
  if (run.is_stopped)          return { label:'중지됨',   cls:'bg-orange-900/50 text-orange-300 border border-orange-700/50', dot:'bg-orange-400' }
  if (run.status==='stopping') return { label:'중지 중',  cls:'bg-orange-900/50 text-orange-300 border border-orange-700/50', dot:'bg-orange-400 animate-pulse' }
  if (run.status==='running')  return { label:'실행 중',  cls:'bg-emerald-900/40 text-emerald-300 border border-emerald-700/40', dot:'bg-emerald-400 animate-pulse' }
  if (run.status==='completed')return { label:'완료',     cls:'bg-slate-700/60 text-slate-300 border border-slate-600/50', dot:'bg-slate-400' }
  if (run.status==='failed')   return { label:'실패',     cls:'bg-red-900/40 text-red-300 border border-red-700/40', dot:'bg-red-400' }
  return { label: run.status, cls:'bg-slate-700 text-slate-400', dot:'bg-slate-500' }
}

// 진행률 퍼센트 (posts_succeeded / posts_dispatched)
const postProgress = (run) => {
  if (!run.posts_dispatched) return 0
  return Math.min(100, Math.round(run.posts_succeeded / run.posts_dispatched * 100))
}

// 오늘 여부
const isToday = (dateStr) => {
  const today = new Date().toLocaleDateString('ko-KR', { year:'2-digit', month:'2-digit', day:'2-digit' })
  return fmtDate(dateStr) === today
}

// 오늘 게시글 진행률
const todayProgress = computed(() => {
  if (!props.summary.today_posts_dispatched) return 0
  return Math.round(props.summary.today_posts_succeeded / props.summary.today_posts_dispatched * 100)
})

// 페이지네이션
const goPage = (url) => { if (url) router.get(url, {}, { preserveState: true }) }
</script>

<template>
  <AdminLayout>
    <div class="p-6 max-w-screen-xl mx-auto">

      <!-- 페이지 헤더 -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-xl font-bold text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            AI 생성 로그
          </h1>
          <p class="text-sm text-slate-400 mt-0.5">일별 게시글·댓글 자동 생성 실행 이력</p>
        </div>
        <div class="flex items-center gap-3">
          <button @click="showCleanup = !showCleanup"
            class="text-xs text-slate-500 hover:text-red-400 transition-colors flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            로그 정리
          </button>
          <Link href="/admin/auto-content"
            class="text-xs bg-slate-700 hover:bg-slate-600 text-slate-300 px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1">
            ← 설정
          </Link>
        </div>
      </div>

      <!-- 오류 알림 -->
      <div v-if="stopError" class="mb-4 bg-red-900/40 border border-red-700/50 rounded-lg px-4 py-2.5 text-sm text-red-300 flex items-center gap-2">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ stopError }}
        <button @click="stopError=null" class="ml-auto text-red-400 hover:text-red-200">✕</button>
      </div>

      <!-- 로그 정리 패널 -->
      <transition name="fade">
        <div v-if="showCleanup" class="mb-5 bg-slate-800/80 border border-slate-700 rounded-xl p-4">
          <p class="text-xs font-semibold text-slate-300 mb-3">🗑 로그 정리</p>
          <div class="flex flex-wrap items-center gap-3">
            <select v-model="cleanupDays"
              class="bg-slate-700 border border-slate-600 rounded-lg text-xs text-white px-3 py-1.5 focus:outline-none focus:border-violet-500">
              <option :value="1">1일 이전</option>
              <option :value="3">3일 이전</option>
              <option :value="5">5일 이전</option>
              <option :value="7">7일 이전</option>
              <option :value="14">14일 이전</option>
              <option :value="30">30일 이전</option>
              <option :value="60">60일 이전</option>
              <option :value="90">90일 이전</option>
              <option :value="0">⚠️ 모든 로그</option>
            </select>
            <button @click="doCleanup" :disabled="cleaningUp"
              :class="cleanupDays === 0
                ? 'bg-red-900 hover:bg-red-800 border border-red-600'
                : 'bg-red-700 hover:bg-red-600'"
              class="text-xs disabled:opacity-50 text-white px-4 py-1.5 rounded-lg transition-colors">
              {{ cleaningUp ? '삭제 중...' : (cleanupDays === 0 ? '⚠️ 전체 삭제' : '삭제 실행') }}
            </button>
            <span v-if="cleanupResult"
              :class="cleanupResult.success ? 'text-emerald-400' : 'text-red-400'"
              class="text-xs">
              {{ cleanupResult.message }}
            </span>
          </div>
          <!-- 전체 삭제 선택 시 경고 -->
          <p v-if="cleanupDays === 0" class="mt-2 text-xs text-red-400">
            ⚠️ 모든 실행 이력과 상세 로그가 영구 삭제됩니다.
          </p>
        </div>
      </transition>

      <!-- 오늘 요약 카드 -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <!-- 오늘 게시글 -->
        <div class="bg-slate-800/60 border border-slate-700/80 rounded-xl p-4">
          <p class="text-xs text-slate-500 mb-1.5">오늘 게시글</p>
          <div class="flex items-end justify-between mb-2">
            <span class="text-2xl font-bold text-white">{{ summary.today_posts_succeeded.toLocaleString() }}</span>
            <span class="text-xs text-slate-500">/ {{ summary.today_posts_dispatched }}</span>
          </div>
          <div class="h-1.5 bg-slate-700 rounded-full overflow-hidden">
            <div class="h-full bg-emerald-500 rounded-full transition-all duration-500"
              :style="{ width: todayProgress + '%' }"></div>
          </div>
          <p class="text-xs text-slate-500 mt-1.5">{{ todayProgress }}% 완료</p>
        </div>

        <!-- 오늘 댓글 -->
        <div class="bg-slate-800/60 border border-slate-700/80 rounded-xl p-4">
          <p class="text-xs text-slate-500 mb-1.5">오늘 댓글</p>
          <p class="text-2xl font-bold text-white mb-1">{{ summary.today_comments_succeeded.toLocaleString() }}</p>
          <span v-if="summary.today_comments_failed > 0" class="text-xs text-red-400">
            ✗ {{ summary.today_comments_failed }}건 실패
          </span>
          <span v-else class="text-xs text-slate-500">오류 없음</span>
        </div>

        <!-- 7일 실행 수 -->
        <div class="bg-slate-800/60 border border-slate-700/80 rounded-xl p-4">
          <p class="text-xs text-slate-500 mb-1.5">최근 7일 실행</p>
          <p class="text-2xl font-bold text-white mb-1">{{ summary.total_runs_7days }}</p>
          <p class="text-xs text-slate-500">회 실행됨</p>
        </div>

        <!-- 7일 오류 -->
        <div class="bg-slate-800/60 border border-slate-700/80 rounded-xl p-4">
          <p class="text-xs text-slate-500 mb-1.5">최근 7일 오류</p>
          <p class="text-2xl font-bold mb-1"
            :class="summary.total_errors_7days > 0 ? 'text-red-400' : 'text-emerald-400'">
            {{ summary.total_errors_7days }}
          </p>
          <p class="text-xs text-slate-500">건 실패</p>
        </div>
      </div>

      <!-- 실행 이력 테이블 -->
      <div class="bg-slate-800/60 border border-slate-700/80 rounded-xl overflow-hidden">

        <div class="px-5 py-3.5 border-b border-slate-700 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-white">실행 이력</h2>
          <span class="text-xs text-slate-500">총 {{ runs.total }}건</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-slate-700 text-xs text-slate-400">
                <th class="text-left px-5 py-3 font-medium">실행일</th>
                <th class="text-left px-4 py-3 font-medium">유형</th>
                <th class="text-left px-4 py-3 font-medium">상태</th>
                <th class="px-4 py-3 font-medium">
                  <div class="flex flex-col">
                    <span>게시글</span>
                    <span class="font-normal text-slate-500">성공 / 실패 / 예약</span>
                  </div>
                </th>
                <th class="px-4 py-3 font-medium">
                  <div class="flex flex-col">
                    <span>댓글</span>
                    <span class="font-normal text-slate-500">성공 / 실패 / 예약</span>
                  </div>
                </th>
                <th class="text-center px-4 py-3 font-medium">오류</th>
                <th class="text-left px-4 py-3 font-medium">시작</th>
                <th class="text-left px-4 py-3 font-medium">경과</th>
                <th class="text-left px-4 py-3 font-medium">실행자</th>
                <th class="px-5 py-3 font-medium text-right">액션</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!runs.data.length">
                <td colspan="10" class="px-5 py-12 text-center text-slate-500 text-sm">
                  실행 이력이 없습니다.
                </td>
              </tr>

              <tr v-for="run in runs.data" :key="run.id"
                :class="[
                  'border-b border-slate-700/50 transition-colors text-sm',
                  isToday(run.run_date) ? 'bg-violet-900/10' : 'hover:bg-slate-700/20'
                ]">

                <!-- 날짜 -->
                <td class="px-5 py-3.5">
                  <div class="flex items-center gap-2">
                    <span v-if="isToday(run.run_date)"
                      class="text-xs bg-violet-600/30 text-violet-300 px-1.5 py-0.5 rounded font-medium">오늘</span>
                    <span class="text-slate-200 font-medium">{{ fmtDate(run.run_date) }}</span>
                    <span class="text-slate-500 text-xs">#{{ run.id }}</span>
                  </div>
                </td>

                <!-- 유형 -->
                <td class="px-4 py-3.5">
                  <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                    :class="{
                      'bg-blue-900/40 text-blue-300':   run.run_type === 'scheduled',
                      'bg-violet-900/40 text-violet-300': run.run_type === 'manual',
                      'bg-slate-700 text-slate-400':    run.run_type === 'dry_run',
                    }">
                    {{ runTypeLabel(run.run_type) }}
                  </span>
                </td>

                <!-- 상태 -->
                <td class="px-4 py-3.5">
                  <span :class="['text-xs px-2 py-0.5 rounded-full font-medium flex items-center gap-1 w-fit', statusConfig(run).cls]">
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="statusConfig(run).dot"></span>
                    {{ statusConfig(run).label }}
                  </span>
                </td>

                <!-- 게시글 -->
                <td class="px-4 py-3.5">
                  <div class="flex items-center gap-2">
                    <div class="text-xs whitespace-nowrap">
                      <span class="text-emerald-400 font-medium">{{ run.posts_succeeded }}</span>
                      <span class="text-slate-600 mx-0.5">/</span>
                      <span :class="run.posts_failed > 0 ? 'text-red-400' : 'text-slate-500'">{{ run.posts_failed }}</span>
                      <span class="text-slate-600 mx-0.5">/</span>
                      <span class="text-slate-500">{{ run.posts_dispatched }}</span>
                    </div>
                  </div>
                  <!-- 진행률 미니 바 -->
                  <div class="mt-1.5 h-1 w-20 bg-slate-700 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all"
                      :class="run.posts_failed > 0 ? 'bg-gradient-to-r from-emerald-500 to-red-500' : 'bg-emerald-500'"
                      :style="{ width: postProgress(run) + '%' }"></div>
                  </div>
                </td>

                <!-- 댓글 -->
                <td class="px-4 py-3.5 text-xs whitespace-nowrap">
                  <span class="text-emerald-400 font-medium">{{ run.comments_succeeded }}</span>
                  <span class="text-slate-600 mx-0.5">/</span>
                  <span :class="run.comments_failed > 0 ? 'text-red-400' : 'text-slate-500'">{{ run.comments_failed }}</span>
                  <span class="text-slate-600 mx-0.5">/</span>
                  <span class="text-slate-500">{{ run.comments_dispatched }}</span>
                </td>

                <!-- 오류 -->
                <td class="px-4 py-3.5 text-center">
                  <span v-if="run.total_errors > 0"
                    class="text-xs bg-red-900/40 text-red-400 border border-red-800/40 px-2 py-0.5 rounded-full font-bold">
                    ✗ {{ run.total_errors }}
                  </span>
                  <span v-else class="text-xs text-slate-600">—</span>
                </td>

                <!-- 시작 -->
                <td class="px-4 py-3.5 text-xs text-slate-400 whitespace-nowrap">{{ fmtTime(run.started_at) }}</td>

                <!-- 경과 -->
                <td class="px-4 py-3.5 text-xs text-slate-400 whitespace-nowrap">{{ fmtElapsed(run.elapsed_seconds) }}</td>

                <!-- 실행자 -->
                <td class="px-4 py-3.5 text-xs text-slate-400">{{ run.triggered_by ?? '스케줄러' }}</td>

                <!-- 액션 -->
                <td class="px-5 py-3.5">
                  <div class="flex items-center gap-2 justify-end">
                    <!-- 중지 버튼 (실행 중일 때만) -->
                    <button v-if="!run.is_stopped && (run.status === 'running' || run.status === 'completed')"
                      @click="requestStop(run)"
                      :disabled="stoppingId === run.id"
                      class="text-xs bg-red-800/60 hover:bg-red-700/80 disabled:opacity-50 text-red-300 border border-red-700/50 px-2.5 py-1 rounded-lg transition-colors flex items-center gap-1 whitespace-nowrap">
                      <svg v-if="stoppingId !== run.id" class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                        <rect x="6" y="6" width="12" height="12" rx="1"/>
                      </svg>
                      <svg v-else class="w-3 h-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8v8z"/>
                      </svg>
                      {{ stoppingId === run.id ? '처리 중' : '중지' }}
                    </button>

                    <!-- 중지됨 배지 -->
                    <span v-else-if="run.is_stopped"
                      class="text-xs text-orange-400 border border-orange-700/40 px-2.5 py-1 rounded-lg whitespace-nowrap">
                      ⛔ 중지됨
                    </span>

                    <!-- 상세보기 -->
                    <Link :href="`/admin/auto-content/logs/${run.id}`"
                      class="text-xs bg-violet-700/50 hover:bg-violet-600/70 text-violet-300 border border-violet-600/40 px-2.5 py-1 rounded-lg transition-colors flex items-center gap-1 whitespace-nowrap">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                      </svg>
                      상세보기
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- 페이지네이션 -->
        <div v-if="runs.last_page > 1"
          class="px-5 py-3 border-t border-slate-700 flex items-center justify-between">
          <span class="text-xs text-slate-500">
            {{ runs.from }}–{{ runs.to }} / {{ runs.total }}건
          </span>
          <div class="flex items-center gap-1">
            <button v-for="link in runs.links" :key="link.label"
              @click="goPage(link.url)"
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
  </AdminLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: all 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
