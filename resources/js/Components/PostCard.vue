<script setup>
import { Link } from '@inertiajs/vue3'
import FactionBadge from '@/Components/FactionBadge.vue'

const props = defineProps({
  post: Object,
  // post: { id, title, user, faction, faction_label, faction_emoji, created_at, views, vote_count, comment_count, is_anonymous }
  boardId: [Number, String],
  showFaction: {
    type: Boolean,
    default: false,
  },
})

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  const now = new Date()
  const diffMs = now - date
  const diffMin = Math.floor(diffMs / 60000)
  const diffHr = Math.floor(diffMin / 60)
  const diffDay = Math.floor(diffHr / 24)

  if (diffMin < 1) return '방금 전'
  if (diffMin < 60) return `${diffMin}분 전`
  if (diffHr < 24) return `${diffHr}시간 전`
  if (diffDay < 7) return `${diffDay}일 전`
  return date.toLocaleDateString('ko-KR', { month: '2-digit', day: '2-digit' })
}
</script>

<template>
  <Link
    :href="`/posts/${post.id}`"
    class="group flex items-start gap-4 bg-slate-900 hover:bg-slate-800/80 border border-slate-800 hover:border-slate-700 rounded-xl px-5 py-4 transition-all"
  >
    <!-- Left: main content -->
    <div class="flex-1 min-w-0">
      <div class="flex items-start gap-2 mb-2 flex-wrap">
        <!-- Title -->
        <h3 class="text-sm font-semibold text-slate-200 group-hover:text-white transition-colors line-clamp-1 flex-1">
          {{ post.title }}
        </h3>
      </div>

      <!-- Meta row -->
      <div class="flex items-center gap-3 flex-wrap text-xs text-slate-500">
        <!-- Author + badge -->
        <div class="flex items-center gap-1.5">
          <span>{{ post.is_anonymous ? '익명' : (post.user?.name ?? '알 수 없음') }}</span>
          <FactionBadge
            v-if="showFaction && post.faction && !post.is_anonymous"
            :type="post.faction"
            :label="post.faction_label"
            :emoji="post.faction_emoji"
          />
        </div>

        <span class="text-slate-700">·</span>
        <span>{{ formatDate(post.created_at) }}</span>
      </div>
    </div>

    <!-- Right: stats -->
    <div class="flex items-center gap-3 text-xs text-slate-500 flex-shrink-0 mt-0.5">
      <!-- Vote -->
      <span
        :class="[
          'flex items-center gap-1',
          (post.vote_count ?? 0) > 0 ? 'text-emerald-500' : '',
          (post.vote_count ?? 0) < 0 ? 'text-red-500' : '',
        ]"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
        </svg>
        {{ post.vote_count ?? 0 }}
      </span>

      <!-- Comments -->
      <span class="flex items-center gap-1">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        {{ post.comment_count ?? 0 }}
      </span>

      <!-- Views -->
      <span class="flex items-center gap-1 hidden sm:flex">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
        {{ post.views ?? 0 }}
      </span>
    </div>
  </Link>
</template>
