/**
 * 진영 pill 스타일 컴포저블 — 레벨 티어(10단계)에 따라 시각적 강도 증가
 *
 * Tier 0 (Lv  1~ 5) — 매우 연한 배경·얇은 테두리
 * Tier 9 (Lv 46~50) — 진한 배경·굵은 테두리·링 글로우
 *
 * 사용법:
 *   import { getFactionPillClass, getGrayPillClass } from '@/composables/useFactionPill'
 *   const cls = getFactionPillClass('conservative', 15)   // → Tier 2 red
 *   const cls = getGrayPillClass(30)                      // → Tier 5 gray
 */

const FACTION_TIERS = {
    conservative: [
        'bg-red-500/10 border border-red-400/30 text-red-500 dark:text-red-400',
        'bg-red-500/15 border border-red-400/40 text-red-600 dark:text-red-400',
        'bg-red-500/20 border border-red-500/50 text-red-700 dark:text-red-300',
        'bg-red-500/25 border border-red-500/60 text-red-700 dark:text-red-300',
        'bg-red-500/30 border-2 border-red-500/60 text-red-800 dark:text-red-200',
        'bg-red-500/40 border-2 border-red-500/70 text-red-800 dark:text-red-200 shadow-sm',
        'bg-red-500/50 border-2 border-red-500/80 text-red-900 dark:text-red-100 shadow',
        'bg-red-600/55 border-2 border-red-400 text-red-900 dark:text-white shadow-md ring-1 ring-red-400/30',
        'bg-red-600/65 border-2 border-red-300 text-red-950 dark:text-white shadow-lg ring-2 ring-red-300/40',
        'bg-red-700/70 border-2 border-red-200 text-white dark:text-white shadow-xl ring-2 ring-red-200/50',
    ],
    moderate: [
        'bg-violet-500/10 border border-violet-400/30 text-violet-500 dark:text-violet-400',
        'bg-violet-500/15 border border-violet-400/40 text-violet-600 dark:text-violet-400',
        'bg-violet-500/20 border border-violet-500/50 text-violet-700 dark:text-violet-300',
        'bg-violet-500/25 border border-violet-500/60 text-violet-700 dark:text-violet-300',
        'bg-violet-500/30 border-2 border-violet-500/60 text-violet-800 dark:text-violet-200',
        'bg-violet-500/40 border-2 border-violet-500/70 text-violet-800 dark:text-violet-200 shadow-sm',
        'bg-violet-500/50 border-2 border-violet-500/80 text-violet-900 dark:text-violet-100 shadow',
        'bg-violet-600/55 border-2 border-violet-400 text-violet-900 dark:text-white shadow-md ring-1 ring-violet-400/30',
        'bg-violet-600/65 border-2 border-violet-300 text-violet-950 dark:text-white shadow-lg ring-2 ring-violet-300/40',
        'bg-violet-700/70 border-2 border-violet-200 text-white dark:text-white shadow-xl ring-2 ring-violet-200/50',
    ],
    progressive: [
        'bg-blue-500/10 border border-blue-400/30 text-blue-500 dark:text-blue-400',
        'bg-blue-500/15 border border-blue-400/40 text-blue-600 dark:text-blue-400',
        'bg-blue-500/20 border border-blue-500/50 text-blue-700 dark:text-blue-300',
        'bg-blue-500/25 border border-blue-500/60 text-blue-700 dark:text-blue-300',
        'bg-blue-500/30 border-2 border-blue-500/60 text-blue-800 dark:text-blue-200',
        'bg-blue-500/40 border-2 border-blue-500/70 text-blue-800 dark:text-blue-200 shadow-sm',
        'bg-blue-500/50 border-2 border-blue-500/80 text-blue-900 dark:text-blue-100 shadow',
        'bg-blue-600/55 border-2 border-blue-400 text-blue-900 dark:text-white shadow-md ring-1 ring-blue-400/30',
        'bg-blue-600/65 border-2 border-blue-300 text-blue-950 dark:text-white shadow-lg ring-2 ring-blue-300/40',
        'bg-blue-700/70 border-2 border-blue-200 text-white dark:text-white shadow-xl ring-2 ring-blue-200/50',
    ],
}

const GRAY_TIERS = [
    'bg-slate-100 border border-slate-200/80 text-slate-500 dark:bg-slate-800/40 dark:border-slate-700/60 dark:text-slate-400',
    'bg-slate-100 border border-slate-300 text-slate-600 dark:bg-slate-800/50 dark:border-slate-600 dark:text-slate-400',
    'bg-slate-200/70 border border-slate-300 text-slate-700 dark:bg-slate-700/50 dark:border-slate-600 dark:text-slate-300',
    'bg-slate-200/90 border border-slate-400/60 text-slate-700 dark:bg-slate-700/60 dark:border-slate-500 dark:text-slate-300',
    'bg-slate-300/60 border-2 border-slate-400/60 text-slate-800 dark:bg-slate-600/50 dark:border-slate-500 dark:text-slate-200',
    'bg-slate-300/70 border-2 border-slate-400/70 text-slate-800 dark:bg-slate-600/60 dark:border-slate-400 dark:text-slate-200 shadow-sm',
    'bg-slate-400/40 border-2 border-slate-500/70 text-slate-900 dark:bg-slate-600/70 dark:border-slate-400 dark:text-slate-100 shadow',
    'bg-slate-400/50 border-2 border-slate-500/80 text-slate-900 dark:bg-slate-500/60 dark:border-slate-300 dark:text-white shadow-md ring-1 ring-slate-400/30',
    'bg-slate-500/45 border-2 border-slate-400 text-slate-900 dark:bg-slate-500/70 dark:border-slate-300 dark:text-white shadow-lg ring-2 ring-slate-400/30',
    'bg-slate-600/45 border-2 border-slate-300 text-slate-900 dark:bg-slate-400/70 dark:border-slate-200 dark:text-white shadow-xl ring-2 ring-slate-300/40',
]

/**
 * 진영 + 레벨에 따른 pill Tailwind 클래스 반환
 * @param {'conservative'|'moderate'|'progressive'} faction
 * @param {number} level  1~50
 */
export function getFactionPillClass(faction, level) {
    const lv   = Math.max(1, Math.min(50, level ?? 1))
    const tier = Math.min(Math.floor((lv - 1) / 5), 9)
    return FACTION_TIERS[faction]?.[tier]
        ?? 'bg-slate-500/10 border border-slate-400/30 text-slate-600 dark:text-slate-400'
}

/**
 * 놀이터(무채색) pill — 레벨 티어 반영
 * @param {number} level  1~50
 */
export function getGrayPillClass(level) {
    const lv   = Math.max(1, Math.min(50, level ?? 1))
    const tier = Math.min(Math.floor((lv - 1) / 5), 9)
    return GRAY_TIERS[tier] ?? GRAY_TIERS[0]
}
