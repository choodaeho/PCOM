<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
defineOptions({ layout: AdminLayout })
defineProps({ stats: Object })
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold text-white mb-6">대시보드</h1>

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <div class="bg-slate-900 rounded-xl p-5 border border-slate-800">
        <div class="flex items-center justify-between mb-3">
          <p class="text-slate-400 text-sm">전체 사용자</p>
          <div class="w-8 h-8 rounded-lg bg-violet-500/20 flex items-center justify-center">
            <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          </div>
        </div>
        <p class="text-3xl font-bold text-white">{{ stats?.users?.total?.toLocaleString() ?? 0 }}</p>
        <p class="text-emerald-400 text-xs mt-1.5">+{{ stats?.users?.today_new ?? 0 }} 오늘 신규</p>
      </div>

      <div class="bg-slate-900 rounded-xl p-5 border border-slate-800">
        <div class="flex items-center justify-between mb-3">
          <p class="text-slate-400 text-sm">미처리 신고</p>
          <div class="w-8 h-8 rounded-lg bg-red-500/20 flex items-center justify-center">
            <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
        </div>
        <p class="text-3xl font-bold text-red-400">{{ stats?.reports_pending ?? 0 }}</p>
        <p class="text-slate-500 text-xs mt-1.5">즉시 처리 필요</p>
      </div>

      <div class="bg-slate-900 rounded-xl p-5 border border-slate-800">
        <div class="flex items-center justify-between mb-3">
          <p class="text-slate-400 text-sm">정지 계정</p>
          <div class="w-8 h-8 rounded-lg bg-orange-500/20 flex items-center justify-center">
            <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
            </svg>
          </div>
        </div>
        <p class="text-3xl font-bold text-orange-400">{{ stats?.users?.suspended ?? 0 }}</p>
        <p class="text-slate-500 text-xs mt-1.5">일시 정지 중</p>
      </div>

      <div class="bg-slate-900 rounded-xl p-5 border border-slate-800">
        <div class="flex items-center justify-between mb-3">
          <p class="text-slate-400 text-sm">총 게시글</p>
          <div class="w-8 h-8 rounded-lg bg-slate-700 flex items-center justify-center">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
        </div>
        <p class="text-3xl font-bold text-white">{{ stats?.posts?.total?.toLocaleString() ?? 0 }}</p>
        <p class="text-slate-500 text-xs mt-1.5">오늘 +{{ stats?.posts?.today ?? 0 }}</p>
      </div>
    </div>

    <!-- Faction Scores -->
    <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-widest mb-3">진영별 활동 점수</h2>
    <div class="grid grid-cols-3 gap-4 mb-8">
      <div v-for="score in (stats?.faction_scores ?? [])" :key="score.faction_type"
        :class="['rounded-xl p-5 border', {
          'bg-red-500/10 border-red-500/30': score.faction_type === 'conservative',
          'bg-violet-500/10 border-violet-500/30': score.faction_type === 'moderate',
          'bg-blue-500/10 border-blue-500/30': score.faction_type === 'progressive',
        }]">
        <div class="flex items-center gap-2 mb-3">
          <span :class="['w-2.5 h-2.5 rounded-full', {
            'bg-red-400': score.faction_type === 'conservative',
            'bg-violet-400': score.faction_type === 'moderate',
            'bg-blue-400': score.faction_type === 'progressive',
          }]"></span>
          <p class="text-slate-300 text-sm font-medium">{{ score.label }}</p>
        </div>
        <p class="text-3xl font-bold text-white">{{ score.normalized_score?.toFixed(1) ?? '-' }}</p>
        <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-400">
          <div>
            <span class="block text-slate-500">게시글</span>
            <span class="font-medium text-slate-300">{{ score.post_count ?? 0 }}</span>
          </div>
          <div>
            <span class="block text-slate-500">추천</span>
            <span class="font-medium text-slate-300">{{ score.vote_count ?? 0 }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Reports -->
    <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-widest mb-3">최근 신고 (빠른 액세스)</h2>
    <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
      <div v-if="!stats?.recent_reports?.length" class="py-10 text-center text-slate-500 text-sm">
        미처리 신고가 없습니다.
      </div>
      <table v-else class="w-full text-sm">
        <thead class="bg-slate-800/50">
          <tr class="text-slate-400 text-left">
            <th class="px-4 py-3 font-medium">신고 유형</th>
            <th class="px-4 py-3 font-medium">대상</th>
            <th class="px-4 py-3 font-medium">신고자</th>
            <th class="px-4 py-3 font-medium">일시</th>
            <th class="px-4 py-3 font-medium">바로가기</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
          <tr v-for="report in stats.recent_reports" :key="report.id" class="hover:bg-slate-800/30">
            <td class="px-4 py-3">
              <span class="text-xs bg-red-500/20 text-red-400 px-2 py-0.5 rounded-full">{{ report.reason }}</span>
            </td>
            <td class="px-4 py-3 text-slate-300 max-w-xs truncate">{{ report.target_title }}</td>
            <td class="px-4 py-3 text-slate-400">{{ report.reporter_nickname }}</td>
            <td class="px-4 py-3 text-slate-500 text-xs">{{ new Date(report.created_at).toLocaleString('ko') }}</td>
            <td class="px-4 py-3">
              <a :href="`/admin/reports?id=${report.id}`" class="text-violet-400 hover:text-violet-300 text-xs">처리하기 →</a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
