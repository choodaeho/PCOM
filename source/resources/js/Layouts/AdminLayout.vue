<script setup>
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const user = computed(() => page.props.auth?.user)
const flash = computed(() => page.props.flash ?? {})

const sidebarOpen = ref(true)

const navItems = [
  { href: '/admin', label: '대시보드', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
  { href: '/admin/users', label: '사용자 관리', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
  { href: '/admin/reports', label: '신고 관리', icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' },
  { href: '/admin/posts', label: '게시글 관리', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
  { href: '/admin/boards', label: '게시판 관리', icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' },
  { href: '/admin/polls', label: '투표 관리', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' },
  { href: '/admin/score-weights', label: '점수 가중치', icon: 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3' },
]

const currentPath = computed(() => page.url)
const isActive = (href) => {
  if (href === '/admin') return currentPath.value === '/admin' || currentPath.value === '/admin/'
  return currentPath.value.startsWith(href)
}
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 flex" style="font-family: 'Noto Sans KR', sans-serif;">
    <!-- Sidebar -->
    <aside :class="['bg-slate-900 border-r border-slate-800 flex flex-col transition-all duration-200', sidebarOpen ? 'w-60' : 'w-16']">
      <!-- Logo -->
      <div class="h-14 flex items-center px-4 border-b border-slate-800 shrink-0">
        <Link href="/admin" class="flex items-center gap-2 overflow-hidden">
          <span class="text-lg font-black text-white shrink-0">P<span class="text-violet-400">.</span></span>
          <span v-if="sidebarOpen" class="text-sm font-bold text-slate-300 whitespace-nowrap">관리자 패널</span>
        </Link>
        <button @click="sidebarOpen = !sidebarOpen" class="ml-auto text-slate-500 hover:text-white transition-colors shrink-0">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
      </div>

      <!-- Nav -->
      <nav class="flex-1 py-4 overflow-y-auto">
        <ul class="space-y-0.5 px-2">
          <li v-for="item in navItems" :key="item.href">
            <Link :href="item.href"
              :class="['flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors group',
                isActive(item.href)
                  ? 'bg-violet-600/20 text-violet-400 font-medium'
                  : 'text-slate-400 hover:bg-slate-800 hover:text-white']">
              <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="item.icon" />
              </svg>
              <span v-if="sidebarOpen" class="whitespace-nowrap">{{ item.label }}</span>
            </Link>
          </li>
        </ul>
      </nav>

      <!-- User info -->
      <div v-if="sidebarOpen" class="border-t border-slate-800 p-4 shrink-0">
        <div class="flex items-center gap-2">
          <div class="w-7 h-7 rounded-full bg-violet-600 flex items-center justify-center text-xs font-bold text-white shrink-0">
            {{ user?.name?.[0]?.toUpperCase() ?? 'A' }}
          </div>
          <div class="overflow-hidden">
            <p class="text-xs text-slate-300 font-medium truncate">{{ user?.name ?? '관리자' }}</p>
            <p class="text-xs text-slate-500 truncate">{{ user?.email }}</p>
          </div>
        </div>
        <Link href="/logout" method="post" as="button"
          class="mt-3 w-full text-left text-xs text-slate-500 hover:text-red-400 transition-colors">
          로그아웃
        </Link>
      </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Top bar -->
      <header class="h-14 bg-slate-900 border-b border-slate-800 flex items-center px-6 shrink-0">
        <div class="flex items-center gap-2 ml-auto">
          <Link href="/" target="_blank"
            class="text-xs text-slate-500 hover:text-violet-400 transition-colors flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
            사이트 보기
          </Link>
        </div>
      </header>

      <!-- Flash -->
      <transition name="flash">
        <div v-if="flash.success" class="bg-emerald-900/40 border-b border-emerald-700/50 text-emerald-300 text-sm px-6 py-2.5 flex items-center gap-2">
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          {{ flash.success }}
        </div>
      </transition>
      <transition name="flash">
        <div v-if="flash.error" class="bg-red-900/40 border-b border-red-700/50 text-red-300 text-sm px-6 py-2.5 flex items-center gap-2">
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
          {{ flash.error }}
        </div>
      </transition>

      <!-- Content -->
      <main class="flex-1 overflow-auto">
        <slot />
      </main>
    </div>
  </div>
</template>

<style scoped>
.flash-enter-active, .flash-leave-active { transition: all 0.3s ease; }
.flash-enter-from, .flash-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
