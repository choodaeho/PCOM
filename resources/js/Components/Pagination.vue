<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  links: Array,
  // Laravel paginator links format:
  // [{ url, label, active }]
})

// Filter out prev/next labels if you want icon versions separately
const prevLink = props.links?.[0]
const nextLink = props.links?.[props.links.length - 1]
const pageLinks = props.links?.slice(1, -1) ?? []

const isEllipsis = (label) => label === '...'
</script>

<template>
  <nav v-if="links?.length > 3" class="flex items-center justify-center gap-1 mt-6" aria-label="페이지네이션">
    <!-- Previous -->
    <template v-if="prevLink?.url">
      <Link
        :href="prevLink.url"
        class="flex items-center gap-1 px-3 py-2 rounded-lg bg-slate-900 border border-slate-800 hover:border-slate-600 text-slate-400 hover:text-white text-sm transition-colors"
        preserve-scroll
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        이전
      </Link>
    </template>
    <span
      v-else
      class="flex items-center gap-1 px-3 py-2 rounded-lg bg-slate-900 border border-slate-800 text-slate-600 text-sm cursor-not-allowed"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
      이전
    </span>

    <!-- Page numbers -->
    <template v-for="link in pageLinks" :key="link.label">
      <!-- Ellipsis -->
      <span
        v-if="isEllipsis(link.label)"
        class="px-3 py-2 text-slate-600 text-sm"
      >...</span>

      <!-- Active page -->
      <span
        v-else-if="link.active"
        class="px-3.5 py-2 rounded-lg bg-violet-600 text-white font-bold text-sm min-w-[2.25rem] text-center"
      >
        {{ link.label }}
      </span>

      <!-- Linked page -->
      <Link
        v-else-if="link.url"
        :href="link.url"
        class="px-3.5 py-2 rounded-lg bg-slate-900 border border-slate-800 hover:border-slate-600 text-slate-400 hover:text-white text-sm transition-colors min-w-[2.25rem] text-center"
        preserve-scroll
      >
        {{ link.label }}
      </Link>

      <!-- Disabled -->
      <span
        v-else
        class="px-3.5 py-2 rounded-lg bg-slate-900 border border-slate-800 text-slate-600 text-sm min-w-[2.25rem] text-center cursor-not-allowed"
      >
        {{ link.label }}
      </span>
    </template>

    <!-- Next -->
    <template v-if="nextLink?.url">
      <Link
        :href="nextLink.url"
        class="flex items-center gap-1 px-3 py-2 rounded-lg bg-slate-900 border border-slate-800 hover:border-slate-600 text-slate-400 hover:text-white text-sm transition-colors"
        preserve-scroll
      >
        다음
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </Link>
    </template>
    <span
      v-else
      class="flex items-center gap-1 px-3 py-2 rounded-lg bg-slate-900 border border-slate-800 text-slate-600 text-sm cursor-not-allowed"
    >
      다음
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
      </svg>
    </span>
  </nav>
</template>
