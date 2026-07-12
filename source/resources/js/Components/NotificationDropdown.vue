<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { echo } from '@/echo'

// ── Props / Emits ──────────────────────────────────────────────
const props = defineProps({
  open: { type: Boolean, default: false },
})
const emit = defineEmits(['update:open', 'unread-change'])

// ── State ─────────────────────────────────────────────────────
const notifications  = ref([])
const unreadCount    = ref(0)
const loading        = ref(false)
const loaded         = ref(false)

// 토스트
const toast      = ref({ show: false, title: '', message: '', url: '' })
let toastTimer   = null
let prevCount    = null   // null = 초기화 전 (첫 폴링 시 토스트 표시 안 함)
let pollTimer    = null

// ── 실시간(WebSocket) 구독 ───────────────────────────────────
const page = usePage()
const userId = computed(() => page.props.auth?.user?.id)
let echoChannel = null
let realtimeConnected = false

// ── Computed ──────────────────────────────────────────────────
const hasUnread = computed(() => unreadCount.value > 0)
const badgeText = computed(() => unreadCount.value > 99 ? '99+' : String(unreadCount.value))

// ── 폴링: 읽지 않은 수 확인 ─────────────────────────────────
async function fetchUnreadCount() {
  try {
    const { data } = await axios.get('/api/notifications/unread-count')
    const newCount = data.count ?? 0

    // 이전 대비 증가 시 토스트 표시 (초기 로드 제외)
    if (prevCount !== null && newCount > prevCount) {
      await showLatestToast()
    }
    prevCount = newCount
    unreadCount.value = newCount
    emit('unread-change', newCount)
  } catch { /* 폴링 실패 무시 */ }
}

// 최신 알림 1건 가져와서 토스트 표시
async function showLatestToast() {
  try {
    const { data } = await axios.get('/api/notifications')
    const latest = (data.notifications ?? []).find(n => !n.read_at)
    if (latest) {
      triggerToast(latest.title, latest.message ?? '', latest.url ?? '')
    } else {
      triggerToast('새 알림이 있습니다', '', '')
    }
    // 패널이 열려 있으면 목록 갱신
    if (props.open) {
      notifications.value = data.notifications ?? []
      unreadCount.value   = data.unread_count  ?? 0
    }
  } catch {
    triggerToast('새 알림이 있습니다', '', '')
  }
}

// 실시간 알림 수신 처리 (UserNotificationSent 브로드캐스트)
function handleRealtimeNotification(payload) {
  const notif = payload?.notification
  if (!notif) return

  unreadCount.value = payload.unread_count ?? unreadCount.value + 1
  prevCount = unreadCount.value
  emit('unread-change', unreadCount.value)

  // 패널이 열려 있으면 목록에 즉시 반영
  if (props.open || loaded.value) {
    if (!notifications.value.some(n => n.id === notif.id)) {
      notifications.value.unshift(notif)
    }
  }

  triggerToast(notif.title, notif.message ?? '', notif.url ?? '')
}

function triggerToast(title, message, url) {
  toast.value = { show: true, title, message, url }
  if (toastTimer) clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { toast.value.show = false }, 5000)
}

function onToastClick() {
  toast.value.show = false
  if (toast.value.url) {
    if (toast.value.url.includes('#')) {
      window.location.href = toast.value.url
    } else {
      router.visit(toast.value.url)
    }
  } else {
    openPanel()
  }
}

// ── 패널 API ─────────────────────────────────────────────────
async function fetchNotifications() {
  if (loading.value) return
  loading.value = true
  try {
    const { data } = await axios.get('/api/notifications')
    notifications.value = data.notifications ?? []
    unreadCount.value   = data.unread_count  ?? 0
    loaded.value        = true
    prevCount = unreadCount.value
    emit('unread-change', unreadCount.value)
  } catch { /* ignore */ } finally {
    loading.value = false
  }
}

async function markRead(notif) {
  if (notif.read_at) return
  try {
    await axios.post(`/api/notifications/${notif.id}/read`)
    notif.read_at = new Date().toISOString()
    unreadCount.value = Math.max(0, unreadCount.value - 1)
    prevCount = unreadCount.value
    emit('unread-change', unreadCount.value)
  } catch { /* ignore */ }
}

async function markAllRead() {
  try {
    await axios.post('/api/notifications/read-all')
    notifications.value.forEach(n => { if (!n.read_at) n.read_at = new Date().toISOString() })
    unreadCount.value = 0
    prevCount = 0
    emit('unread-change', 0)
  } catch { /* ignore */ }
}

async function deleteNotif(notif, e) {
  e.stopPropagation()
  try {
    await axios.delete(`/api/notifications/${notif.id}`)
    const idx = notifications.value.indexOf(notif)
    if (idx !== -1) {
      if (!notif.read_at) {
        unreadCount.value = Math.max(0, unreadCount.value - 1)
        prevCount = unreadCount.value
        emit('unread-change', unreadCount.value)
      }
      notifications.value.splice(idx, 1)
    }
  } catch { /* ignore */ }
}

// ── 패널 열기 / 닫기 ─────────────────────────────────────────
function openPanel() {
  emit('update:open', true)
  fetchNotifications()
}

function closePanel() {
  emit('update:open', false)
}

function toggle(e) {
  e.stopPropagation()
  props.open ? closePanel() : openPanel()
}

// ── 알림 클릭 ─────────────────────────────────────────────────
function handleClick(notif) {
  markRead(notif)
  closePanel()
  if (notif.url) {
    notif.url.includes('#') ? (window.location.href = notif.url) : router.visit(notif.url)
  }
}

// ── 유틸 ─────────────────────────────────────────────────────
function relativeTime(isoStr) {
  if (!isoStr) return ''
  const diff = Date.now() - new Date(isoStr).getTime()
  const m = Math.floor(diff / 60000)
  if (m < 1)  return '방금 전'
  if (m < 60) return `${m}분 전`
  const h = Math.floor(m / 60)
  if (h < 24) return `${h}시간 전`
  const d = Math.floor(h / 24)
  if (d < 30) return `${d}일 전`
  return new Date(isoStr).toLocaleDateString('ko-KR')
}

function typeIcon(type) {
  return { comment: '💬', reply: '↩️', hot: '🔥' }[type] ?? '🔔'
}

// ── Lifecycle ─────────────────────────────────────────────────
onMounted(() => {
  fetchUnreadCount()

  // 실시간 WebSocket 구독 (Reverb). 연결되면 폴링은 백업 용도로만 저빈도 유지.
  if (userId.value) {
    try {
      echoChannel = echo.private(`users.${userId.value}`)
      echoChannel
        .listen('.UserNotification', handleRealtimeNotification)
        .subscribed(() => { realtimeConnected = true })
        .error(() => { realtimeConnected = false })
    } catch {
      realtimeConnected = false
    }
  }

  // 폴링: 실시간 구독이 백업 역할. 연결 성공 시 저빈도(2분)로 전환, 실패 시 30초 유지.
  pollTimer = setInterval(() => {
    fetchUnreadCount()
  }, 30000)
  setTimeout(() => {
    if (realtimeConnected && pollTimer) {
      clearInterval(pollTimer)
      pollTimer = setInterval(() => fetchUnreadCount(), 120000)
    }
  }, 5000)
})
onBeforeUnmount(() => {
  if (pollTimer) clearInterval(pollTimer)
  if (toastTimer) clearTimeout(toastTimer)
  if (userId.value) {
    echo.leave(`users.${userId.value}`)
  }
})
</script>

<template>
  <!-- 벨 버튼 -->
  <button
    @click="toggle"
    class="relative w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors"
    aria-label="알림"
  >
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
    </svg>
    <span
      v-if="hasUnread"
      class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center leading-none"
    >{{ badgeText }}</span>
  </button>

  <!-- 투명 백드롭: 패널 외부 클릭 시 닫힘 -->
  <div v-if="open" class="fixed inset-0" style="z-index:48" @click="closePanel" />

  <!-- 드롭다운 패널 -->
  <transition name="dropdown">
    <div
      v-if="open"
      class="absolute right-0 top-full mt-1 w-80 sm:w-96 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden"
      style="z-index:50"
      @click.stop
    >
      <!-- 헤더 -->
      <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-slate-800">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">알림</h3>
        <button
          v-if="hasUnread"
          @click="markAllRead"
          class="text-xs text-violet-500 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300 font-medium transition-colors"
        >모두 읽음</button>
      </div>

      <!-- 목록 -->
      <div class="max-h-[420px] overflow-y-auto">
        <div v-if="loading && !loaded" class="flex items-center justify-center py-10 text-slate-400 dark:text-slate-600 text-sm">
          <svg class="w-5 h-5 animate-spin mr-2 text-violet-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
          </svg>
          불러오는 중...
        </div>

        <div v-else-if="loaded && notifications.length === 0"
          class="flex flex-col items-center justify-center py-12 text-center px-4">
          <span class="text-3xl mb-2">🔔</span>
          <p class="text-sm text-slate-500 dark:text-slate-400">새 알림이 없습니다</p>
        </div>

        <div v-else>
          <div
            v-for="notif in notifications"
            :key="notif.id"
            @click="handleClick(notif)"
            :class="[
              'group flex items-start gap-3 px-4 py-3 cursor-pointer transition-colors border-b border-gray-50 dark:border-slate-800/60 last:border-0',
              notif.read_at
                ? 'hover:bg-gray-50 dark:hover:bg-slate-800/50'
                : 'bg-violet-50 dark:bg-violet-900/10 hover:bg-violet-100 dark:hover:bg-violet-900/20'
            ]"
          >
            <div class="flex-shrink-0 w-9 h-9 rounded-full bg-gray-100 dark:bg-slate-800 flex items-center justify-center text-lg mt-0.5">
              {{ typeIcon(notif.type) }}
            </div>
            <div class="flex-1 min-w-0">
              <p :class="['text-sm leading-snug truncate', notif.read_at ? 'text-slate-600 dark:text-slate-400' : 'text-slate-900 dark:text-white font-semibold']">
                {{ notif.title }}
              </p>
              <p v-if="notif.message" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-2 leading-relaxed">
                {{ notif.message }}
              </p>
              <p class="text-[11px] text-slate-400 dark:text-slate-600 mt-1">{{ relativeTime(notif.created_at) }}</p>
            </div>
            <div class="flex-shrink-0 flex flex-col items-end gap-2 pt-0.5">
              <span v-if="!notif.read_at" class="w-2 h-2 rounded-full bg-violet-500"/>
              <button
                @click="deleteNotif(notif, $event)"
                class="opacity-0 group-hover:opacity-100 w-5 h-5 flex items-center justify-center text-slate-400 hover:text-red-500 dark:hover:text-red-400 transition-all"
                title="삭제"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="loaded && notifications.length > 0"
        class="px-4 py-2.5 border-t border-gray-100 dark:border-slate-800 text-center">
        <span class="text-xs text-slate-400 dark:text-slate-600">최근 30건 표시</span>
      </div>
    </div>
  </transition>

  <!-- 🔔 자동 토스트 팝업 (새 알림 도착 시) -->
  <Teleport to="body">
    <transition name="toast-slide">
      <div
        v-if="toast.show"
        class="fixed right-4 top-20 z-[300] w-80 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-2xl shadow-2xl overflow-hidden cursor-pointer"
        @click="onToastClick"
      >
        <div class="flex items-start gap-3 px-4 py-3.5">
          <div class="w-9 h-9 rounded-full bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center text-lg flex-shrink-0 mt-0.5">
            🔔
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-bold text-slate-900 dark:text-white leading-snug truncate">{{ toast.title }}</p>
            <p v-if="toast.message" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-2 leading-relaxed">{{ toast.message }}</p>
          </div>
          <button
            @click.stop="toast.show = false"
            class="flex-shrink-0 w-6 h-6 flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors mt-0.5"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        <!-- 자동 닫힘 프로그레스 바 -->
        <div class="h-0.5 bg-violet-500 toast-progress"/>
      </div>
    </transition>
  </Teleport>
</template>

<style scoped>
.toast-slide-enter-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.toast-slide-leave-active {
  transition: all 0.2s ease-in;
}
.toast-slide-enter-from {
  transform: translateX(calc(100% + 1rem));
  opacity: 0;
}
.toast-slide-leave-to {
  transform: translateX(calc(100% + 1rem));
  opacity: 0;
}

/* 5초 프로그레스 바 */
.toast-progress {
  animation: toast-shrink 5s linear forwards;
  transform-origin: left;
}
@keyframes toast-shrink {
  from { transform: scaleX(1); }
  to   { transform: scaleX(0); }
}
</style>
