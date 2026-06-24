<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

// ─────────────────────────────────────────────
// 🎰 로또번호생성기
// ─────────────────────────────────────────────

const lottoSets      = ref([])
const setCount       = ref(5)
const isAnimating    = ref(false)
const animatedSets   = ref([])

const LOTTO_COLORS = [
  { range: [1, 10],  bg: 'bg-yellow-400',  text: 'text-yellow-900' },
  { range: [11, 20], bg: 'bg-blue-500',    text: 'text-white' },
  { range: [21, 30], bg: 'bg-red-500',     text: 'text-white' },
  { range: [31, 40], bg: 'bg-slate-600',   text: 'text-white' },
  { range: [41, 45], bg: 'bg-green-500',   text: 'text-white' },
]

function lottoColor(num) {
  return LOTTO_COLORS.find(c => num >= c.range[0] && num <= c.range[1]) ?? LOTTO_COLORS[4]
}

function pickLotto() {
  const pool = Array.from({ length: 45 }, (_, i) => i + 1)
  const picked = []
  for (let i = 0; i < 6; i++) {
    const idx = Math.floor(Math.random() * pool.length)
    picked.push(pool.splice(idx, 1)[0])
  }
  return picked.sort((a, b) => a - b)
}

async function generateLotto() {
  if (isAnimating.value) return
  isAnimating.value = true

  const newSets = Array.from({ length: setCount.value }, () => pickLotto())
  animatedSets.value = []

  for (let i = 0; i < newSets.length; i++) {
    await new Promise(r => setTimeout(r, 120))
    animatedSets.value.push({ numbers: newSets[i], id: Date.now() + i })
  }

  lottoSets.value = [...animatedSets.value]
  isAnimating.value = false
}

// ─────────────────────────────────────────────
// 🔮 오늘의 운세
// ─────────────────────────────────────────────

const ZODIAC_ANIMALS = [
  { name: '🐭 쥐', years: '1948·1960·1972·1984·1996·2008·2020' },
  { name: '🐮 소', years: '1949·1961·1973·1985·1997·2009·2021' },
  { name: '🐯 호랑이', years: '1950·1962·1974·1986·1998·2010·2022' },
  { name: '🐰 토끼', years: '1951·1963·1975·1987·1999·2011·2023' },
  { name: '🐲 용', years: '1952·1964·1976·1988·2000·2012·2024' },
  { name: '🐍 뱀', years: '1953·1965·1977·1989·2001·2013·2025' },
  { name: '🐴 말', years: '1954·1966·1978·1990·2002·2014·2026' },
  { name: '🐑 양', years: '1955·1967·1979·1991·2003·2015·2027' },
  { name: '🐵 원숭이', years: '1956·1968·1980·1992·2004·2016·2028' },
  { name: '🐔 닭', years: '1957·1969·1981·1993·2005·2017·2029' },
  { name: '🐶 개', years: '1958·1970·1982·1994·2006·2018·2030' },
  { name: '🐷 돼지', years: '1959·1971·1983·1995·2007·2019·2031' },
]

const FORTUNES = [
  { score: 95, summary: '대길(大吉)', desc: '모든 일이 순조롭게 풀리는 최고의 날입니다. 중요한 결정을 내리기 좋은 날이니 망설이지 마세요. 금전운과 애정운 모두 상승 중.', lucky: { color: '금빛', number: 7, direction: '남쪽' } },
  { score: 85, summary: '대체로 길', desc: '에너지가 넘치고 주변 사람들과의 관계가 원만한 날입니다. 새로운 시작을 해보세요. 오후에 좋은 소식이 들어올 수 있습니다.', lucky: { color: '청색', number: 3, direction: '동쪽' } },
  { score: 78, summary: '소길(小吉)', desc: '작은 행운들이 곳곳에 숨어있는 날입니다. 꼼꼼하게 살펴보면 기회를 잡을 수 있습니다. 건강에 신경 쓰고 충분한 수면을 취하세요.', lucky: { color: '초록', number: 5, direction: '서쪽' } },
  { score: 65, summary: '평범', desc: '평소와 다름없이 무난한 하루입니다. 큰 변화보다는 현재 상태를 유지하는 것이 좋습니다. 저녁에 가까운 사람과 대화를 나눠보세요.', lucky: { color: '흰색', number: 9, direction: '북쪽' } },
  { score: 55, summary: '조심', desc: '예상치 못한 변수가 생길 수 있는 날입니다. 섣불리 새로운 일을 시작하지 말고, 기존에 하던 일에 집중하세요. 지출에 주의가 필요합니다.', lucky: { color: '주황', number: 2, direction: '북동쪽' } },
  { score: 45, summary: '소흉(小凶)', desc: '에너지가 다소 낮은 날입니다. 중요한 약속이나 투자 결정은 다음 날로 미루는 게 좋습니다. 충분한 휴식을 취하세요.', lucky: { color: '보라', number: 4, direction: '남서쪽' } },
]

const selectedZodiac = ref(null)
const fortuneResult  = ref(null)
const isRevealingFortune = ref(false)

async function revealFortune(idx) {
  selectedZodiac.value = idx
  isRevealingFortune.value = true
  fortuneResult.value = null

  await new Promise(r => setTimeout(r, 600))

  // 날짜 + 띠 인덱스를 시드로 삼아 오늘 운세 고정
  const today = new Date()
  const seed  = today.getFullYear() * 1000 + today.getMonth() * 100 + today.getDate() + idx
  const fortuneIdx = seed % FORTUNES.length
  fortuneResult.value = FORTUNES[fortuneIdx]
  isRevealingFortune.value = false
}

const fortuneScoreColor = computed(() => {
  if (!fortuneResult.value) return 'text-slate-400'
  const s = fortuneResult.value.score
  if (s >= 85) return 'text-yellow-400'
  if (s >= 70) return 'text-emerald-400'
  if (s >= 55) return 'text-blue-400'
  return 'text-orange-400'
})

// ─────────────────────────────────────────────
// 🔜 Coming Soon 기능 카드
// ─────────────────────────────────────────────

const comingSoon = [
  { emoji: '💘', title: '이상형 월드컵', desc: '당신만의 이상형을 찾아보세요. 1:1 토너먼트 방식', color: 'border-pink-500/30 bg-pink-500/5' },
  { emoji: '📰', title: '시사 퀴즈', desc: '오늘의 뉴스로 만든 5문제 퀴즈. 매일 업데이트', color: 'border-violet-500/30 bg-violet-500/5' },
  { emoji: '🧬', title: '닮은꼴 정치인 찾기', desc: '나의 성향과 가장 비슷한 정치인은 누구?', color: 'border-emerald-500/30 bg-emerald-500/5' },
  { emoji: '📊', title: '나의 성향 공유카드', desc: '테스트 결과를 카드로 만들어 SNS에 공유하기', color: 'border-blue-500/30 bg-blue-500/5' },
]
</script>

<template>
  <div class="max-w-5xl mx-auto px-4 py-10">

    <!-- 페이지 헤더 -->
    <div class="mb-10">
      <h1 class="text-3xl font-black text-slate-900 dark:text-white mb-2">🧰 툴박스</h1>
      <p class="text-slate-500 dark:text-slate-400">로그인 없이 누구나 이용할 수 있는 재미있는 기능 모음</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

      <!-- ──────────────────────────────────────────
           🎰 로또번호생성기
      ─────────────────────────────────────────── -->
      <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-5 border-b border-gray-200 dark:border-slate-800 flex items-center gap-3">
          <span class="text-2xl">🎰</span>
          <div>
            <h2 class="text-lg font-black text-slate-900 dark:text-white">로또번호생성기</h2>
            <p class="text-xs text-slate-400 dark:text-slate-500">6/45 랜덤 번호 자동 생성</p>
          </div>
        </div>

        <div class="p-6">
          <!-- 세트 수 선택 -->
          <div class="flex items-center gap-3 mb-5">
            <span class="text-sm text-slate-500 dark:text-slate-400 flex-shrink-0">생성 세트</span>
            <div class="flex gap-2">
              <button
                v-for="n in [1, 3, 5, 10]"
                :key="n"
                @click="setCount = n"
                :class="[
                  'w-9 h-9 rounded-lg text-sm font-bold transition-all',
                  setCount === n
                    ? 'bg-violet-600 text-white shadow-lg shadow-violet-500/20'
                    : 'bg-gray-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-gray-200 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white'
                ]"
              >{{ n }}</button>
            </div>
          </div>

          <!-- 생성 버튼 -->
          <button
            @click="generateLotto"
            :disabled="isAnimating"
            :class="[
              'w-full py-3.5 rounded-xl font-black text-lg transition-all mb-6',
              isAnimating
                ? 'bg-gray-200 dark:bg-slate-700 text-slate-400 dark:text-slate-500 cursor-not-allowed'
                : 'bg-gradient-to-r from-violet-600 to-violet-500 hover:from-violet-500 hover:to-violet-400 text-white shadow-lg shadow-violet-500/20 hover:shadow-violet-500/40 hover:-translate-y-0.5'
            ]"
          >
            {{ isAnimating ? '생성 중...' : '🎲 번호 생성' }}
          </button>

          <!-- 결과 -->
          <div v-if="animatedSets.length" class="space-y-3">
            <div
              v-for="(set, si) in animatedSets"
              :key="set.id"
              class="bg-gray-100 dark:bg-slate-800/60 rounded-xl p-3 flex items-center gap-2"
            >
              <span class="text-xs text-slate-400 dark:text-slate-500 w-5 font-bold">{{ si + 1 }}</span>
              <div class="flex gap-1.5 flex-wrap">
                <span
                  v-for="num in set.numbers"
                  :key="num"
                  :class="[
                    'w-9 h-9 rounded-full flex items-center justify-center text-sm font-black transition-all',
                    lottoColor(num).bg,
                    lottoColor(num).text,
                    'shadow-sm'
                  ]"
                >{{ num }}</span>
              </div>
            </div>
          </div>

          <!-- 빈 상태 -->
          <div v-else class="text-center py-8 text-slate-400 dark:text-slate-600">
            <p class="text-4xl mb-2">🎱</p>
            <p class="text-sm">버튼을 눌러 번호를 생성하세요</p>
          </div>

          <!-- 숫자 색상 안내 -->
          <div class="mt-5 pt-4 border-t border-gray-200 dark:border-slate-800 flex flex-wrap gap-2 justify-center">
            <div v-for="c in LOTTO_COLORS" :key="c.range[0]" class="flex items-center gap-1">
              <div :class="['w-5 h-5 rounded-full text-xs flex items-center justify-center font-bold', c.bg, c.text]">
                {{ c.range[0] }}
              </div>
              <span class="text-xs text-slate-400 dark:text-slate-600">~{{ c.range[1] }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ──────────────────────────────────────────
           🔮 오늘의 운세
      ─────────────────────────────────────────── -->
      <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-5 border-b border-gray-200 dark:border-slate-800 flex items-center gap-3">
          <span class="text-2xl">🔮</span>
          <div>
            <h2 class="text-lg font-black text-slate-900 dark:text-white">오늘의 운세</h2>
            <p class="text-xs text-slate-400 dark:text-slate-500">나의 띠를 선택하면 오늘 운세를 알려드립니다</p>
          </div>
        </div>

        <div class="p-6">
          <!-- 띠 선택 -->
          <div class="grid grid-cols-4 gap-2 mb-5">
            <button
              v-for="(z, idx) in ZODIAC_ANIMALS"
              :key="idx"
              @click="revealFortune(idx)"
              :class="[
                'rounded-xl py-2 px-1 text-center transition-all text-xs',
                selectedZodiac === idx
                  ? 'bg-violet-600 text-white shadow-lg shadow-violet-500/20 scale-105'
                  : 'bg-gray-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white'
              ]"
            >
              <div class="text-lg mb-0.5">{{ z.name.split(' ')[0] }}</div>
              <div class="font-semibold">{{ z.name.split(' ')[1] }}</div>
            </button>
          </div>

          <!-- 결과 -->
          <transition name="fade">
            <div v-if="isRevealingFortune" class="text-center py-8">
              <div class="text-4xl animate-spin-slow mb-3">🔮</div>
              <p class="text-slate-400 text-sm">운세를 불러오는 중...</p>
            </div>

            <div v-else-if="fortuneResult" class="bg-gray-100 dark:bg-slate-800/60 rounded-xl p-5">
              <div class="flex items-center justify-between mb-4">
                <div>
                  <span class="text-xl font-black text-slate-900 dark:text-white">{{ ZODIAC_ANIMALS[selectedZodiac].name }}</span>
                  <span class="text-xs text-slate-400 dark:text-slate-500 ml-2">{{ ZODIAC_ANIMALS[selectedZodiac].years }}</span>
                </div>
                <span :class="['text-2xl font-black', fortuneScoreColor]">
                  {{ fortuneResult.score }}점
                </span>
              </div>

              <!-- 운세 점수 바 -->
              <div class="h-2 bg-gray-200 dark:bg-slate-700 rounded-full mb-3 overflow-hidden">
                <div
                  class="h-full rounded-full bg-gradient-to-r from-violet-500 to-yellow-400 transition-all duration-700"
                  :style="{ width: fortuneResult.score + '%' }"
                ></div>
              </div>

              <div :class="['text-sm font-bold mb-2', fortuneScoreColor]">
                ✨ {{ fortuneResult.summary }}
              </div>
              <p class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed mb-4">{{ fortuneResult.desc }}</p>

              <div class="grid grid-cols-3 gap-2 pt-3 border-t border-gray-300 dark:border-slate-700">
                <div class="text-center">
                  <p class="text-xs text-slate-400 dark:text-slate-500 mb-1">행운의 색</p>
                  <p class="text-sm font-bold text-slate-900 dark:text-white">{{ fortuneResult.lucky.color }}</p>
                </div>
                <div class="text-center border-x border-gray-300 dark:border-slate-700">
                  <p class="text-xs text-slate-400 dark:text-slate-500 mb-1">행운의 숫자</p>
                  <p class="text-sm font-bold text-slate-900 dark:text-white">{{ fortuneResult.lucky.number }}</p>
                </div>
                <div class="text-center">
                  <p class="text-xs text-slate-400 dark:text-slate-500 mb-1">행운의 방향</p>
                  <p class="text-sm font-bold text-slate-900 dark:text-white">{{ fortuneResult.lucky.direction }}</p>
                </div>
              </div>
            </div>

            <div v-else class="text-center py-8 text-slate-400 dark:text-slate-600">
              <p class="text-4xl mb-2">🐭</p>
              <p class="text-sm">위에서 나의 띠를 선택하세요</p>
            </div>
          </transition>
        </div>
      </div>
    </div>

    <!-- ──────────────────────────────────────────
         🔜 출시 예정 기능
    ─────────────────────────────────────────── -->
    <div class="mt-10">
      <div class="flex items-center gap-3 mb-5">
        <h2 class="text-xl font-black text-slate-900 dark:text-white">🔜 출시 예정</h2>
        <span class="text-xs text-slate-500 dark:text-slate-400 bg-gray-100 dark:bg-slate-800 px-2 py-0.5 rounded-full">Coming Soon</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div
          v-for="item in comingSoon"
          :key="item.title"
          :class="['border rounded-2xl p-5 relative overflow-hidden opacity-80', item.color]"
        >
          <div class="absolute top-3 right-3 text-xs bg-gray-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 px-2 py-0.5 rounded-full">
            준비 중
          </div>
          <div class="text-3xl mb-3">{{ item.emoji }}</div>
          <h3 class="font-black text-slate-900 dark:text-white mb-1">{{ item.title }}</h3>
          <p class="text-slate-500 dark:text-slate-400 text-xs leading-relaxed">{{ item.desc }}</p>
        </div>
      </div>
    </div>

    <!-- ──────────────────────────────────────────
         놀이터 바로가기
    ─────────────────────────────────────────── -->
    <div class="mt-10 bg-gradient-to-r from-violet-50 via-purple-50/60 to-violet-50 dark:from-slate-900 dark:via-slate-800/60 dark:to-slate-900 border border-violet-200 dark:border-slate-700 rounded-2xl p-8 text-center">
      <p class="text-3xl mb-3">🎡</p>
      <h2 class="text-xl font-black text-slate-900 dark:text-white mb-2">정치 말고 딴 얘기도 하고 싶다면?</h2>
      <p class="text-slate-500 dark:text-slate-400 text-sm mb-5">게임·스포츠·연예·주식 등 다양한 주제의 놀이터 게시판으로</p>
      <Link href="/boards" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-500 text-white font-bold px-6 py-3 rounded-xl text-sm transition-all shadow-lg shadow-violet-500/20 hover:-translate-y-0.5">
        놀이터 구경하기 →
      </Link>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@keyframes spin-slow {
  from { transform: rotate(0deg); }
  to   { transform: rotate(360deg); }
}
.animate-spin-slow {
  animation: spin-slow 2s linear infinite;
}
</style>
