<script setup>
import { ref, computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const page     = usePage()
const authUser = computed(() => page.props.auth?.user ?? null)

// ────────────────────── 🎰 로또번호생성기 ──────────────────────
const lottoSets    = ref([])
const setCount     = ref(5)
const isAnimating  = ref(false)
const animatedSets = ref([])

const LOTTO_COLORS = [
  { range: [1,  10], bg: 'bg-yellow-400', text: 'text-yellow-900' },
  { range: [11, 20], bg: 'bg-blue-500',   text: 'text-white'      },
  { range: [21, 30], bg: 'bg-red-500',    text: 'text-white'      },
  { range: [31, 40], bg: 'bg-slate-600',  text: 'text-white'      },
  { range: [41, 45], bg: 'bg-green-500',  text: 'text-white'      },
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

// ────────────────────── 🔮 오늘의 운세 ──────────────────────
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
  { score: 95, summary: '대길(大吉)', desc: '모든 일이 순조롭게 풀리는 최고의 날입니다. 중요한 결정을 내리기 좋은 날이니 망설이지 마세요.', lucky: { color: '금빛', number: 7, direction: '남쪽' } },
  { score: 85, summary: '대체로 길', desc: '에너지가 넘치고 주변 사람들과의 관계가 원만한 날입니다. 새로운 시작을 해보세요.', lucky: { color: '청색', number: 3, direction: '동쪽' } },
  { score: 78, summary: '소길(小吉)', desc: '작은 행운들이 곳곳에 숨어있는 날입니다. 꼼꼼하게 살펴보면 기회를 잡을 수 있습니다.', lucky: { color: '초록', number: 5, direction: '서쪽' } },
  { score: 65, summary: '평범', desc: '평소와 다름없이 무난한 하루입니다. 큰 변화보다는 현재 상태를 유지하는 것이 좋습니다.', lucky: { color: '흰색', number: 9, direction: '북쪽' } },
  { score: 55, summary: '조심', desc: '예상치 못한 변수가 생길 수 있는 날입니다. 지출에 주의하고 기존 일에 집중하세요.', lucky: { color: '주황', number: 2, direction: '북동쪽' } },
  { score: 45, summary: '소흉(小凶)', desc: '에너지가 다소 낮은 날입니다. 중요한 결정은 다음 날로 미루고 충분한 휴식을 취하세요.', lucky: { color: '보라', number: 4, direction: '남서쪽' } },
]
const selectedZodiac     = ref(null)
const fortuneResult      = ref(null)
const isRevealingFortune = ref(false)

async function revealFortune(idx) {
  selectedZodiac.value = idx
  isRevealingFortune.value = true
  fortuneResult.value = null
  await new Promise(r => setTimeout(r, 600))
  const today = new Date()
  const seed = today.getFullYear() * 1000 + today.getMonth() * 100 + today.getDate() + idx
  fortuneResult.value = FORTUNES[seed % FORTUNES.length]
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

// ────────────────────── 💘 이상형 월드컵 ──────────────────────
const WC_ITEMS = [
  { id:  1, emoji: '🏥', title: '복지 확대',       desc: '더 두꺼운 사회 안전망' },
  { id:  2, emoji: '📈', title: '경제 성장',       desc: '기업 투자 촉진, GDP 우선' },
  { id:  3, emoji: '🌿', title: '환경 보호',       desc: '탄소중립·기후위기 대응' },
  { id:  4, emoji: '⚡', title: '에너지 자립',     desc: '원전·신재생으로 에너지 독립' },
  { id:  5, emoji: '📚', title: '교육 개혁',       desc: '공정한 교육 기회 보장' },
  { id:  6, emoji: '🛡️', title: '안보 강화',       desc: '국방력 강화, 동맹 공고화' },
  { id:  7, emoji: '🤝', title: '남북 교류',       desc: '평화적 남북 협력 추진' },
  { id:  8, emoji: '🏗️', title: '규제 완화',       desc: '기업 자유도 확대, 혁신 촉진' },
  { id:  9, emoji: '💊', title: '의료 공공화',     desc: '모두에게 평등한 의료 서비스' },
  { id: 10, emoji: '💰', title: '세금 감면',       desc: '감세로 경제 활력 회복' },
  { id: 11, emoji: '📺', title: '언론 개혁',       desc: '공정한 언론 환경 조성' },
  { id: 12, emoji: '⚖️', title: '사법 개혁',       desc: '독립적이고 투명한 사법부' },
  { id: 13, emoji: '🏠', title: '부동산 안정',     desc: '집값 잡기, 주거 안정 보장' },
  { id: 14, emoji: '👥', title: '청년 지원',       desc: '청년 취업·주거·출산 지원' },
  { id: 15, emoji: '👴', title: '고령화 대응',     desc: '노인 복지, 연금 개혁' },
  { id: 16, emoji: '💻', title: '디지털 혁신',     desc: 'AI·반도체 산업 육성' },
  { id: 17, emoji: '🌏', title: '외교 다변화',     desc: '미·중 균형 외교 추구' },
  { id: 18, emoji: '🏛️', title: '검찰 개혁',       desc: '검찰 권한 분산·감시 강화' },
  { id: 19, emoji: '🌱', title: '기후 행동',       desc: '탄소세 도입, 그린뉴딜' },
  { id: 20, emoji: '🏢', title: '재벌 개혁',       desc: '대기업 지배구조 투명화' },
  { id: 21, emoji: '⚕️', title: '의대 정원 확대', desc: '지역·필수 의료 인력 확충' },
  { id: 22, emoji: '🚆', title: '지역 균형 발전', desc: '서울 집중 완화, 지역 투자' },
  { id: 23, emoji: '👩‍💼', title: '성평등 정책',   desc: '성별 격차 해소, 여성 권익' },
  { id: 24, emoji: '🚔', title: '범죄 강경 대응', desc: '처벌 강화로 치안 확립' },
  { id: 25, emoji: '🌾', title: '농업 보호',       desc: '식량 안보, 농촌 경제 살리기' },
  { id: 26, emoji: '🤖', title: 'AI 규제',         desc: 'AI 윤리·안전 법제화' },
  { id: 27, emoji: '🏄', title: '이민 개방',       desc: '저출산 해결위한 이민 확대' },
  { id: 28, emoji: '🏫', title: '대학 서열 해소', desc: '입시 개혁으로 교육 평등' },
  { id: 29, emoji: '🚇', title: '공공교통 확대', desc: '무상·저가 대중교통' },
  { id: 30, emoji: '💵', title: '기본소득',         desc: '보편적 기본소득 도입' },
  { id: 31, emoji: '🔐', title: '개인정보 보호', desc: '빅테크 규제, 데이터 주권' },
  { id: 32, emoji: '🎓', title: '등록금 인하',     desc: '반값 등록금, 교육비 경감' },
]

const wcPool     = ref([])
const wcNext     = ref([])
const wcPairIdx  = ref(0)
const wcChampion = ref(null)
const wcStarted  = ref(false)
const wcChosenId = ref(null)

const wcRoundLabel = computed(() => {
  const n = wcPool.value.length
  if (n === 16) return '16강'
  if (n ===  8) return '8강'
  if (n ===  4) return '4강'
  if (n ===  2) return '결승'
  return ''
})
const wcTotalMatches = computed(() => wcPool.value.length / 2)
const wcCurrentMatch = computed(() => wcPairIdx.value + 1)
const wcPair = computed(() => {
  if (!wcStarted.value || wcChampion.value) return null
  const a = wcPool.value[wcPairIdx.value * 2]
  const b = wcPool.value[wcPairIdx.value * 2 + 1]
  return (a && b) ? [a, b] : null
})

function startWorldCup() {
  // 32개 중 16개를 무작위로 선택
  const shuffled   = [...WC_ITEMS].sort(() => Math.random() - 0.5)
  wcPool.value     = shuffled.slice(0, 16)
  wcNext.value     = []
  wcPairIdx.value  = 0
  wcChampion.value = null
  wcStarted.value  = true
  wcChosenId.value = null
}
async function pickWcWinner(item) {
  if (wcChosenId.value !== null) return
  wcChosenId.value = item.id
  await new Promise(r => setTimeout(r, 320))
  wcNext.value.push(item)
  wcChosenId.value = null
  const nextIdx = wcPairIdx.value + 1
  if (nextIdx >= wcTotalMatches.value) {
    if (wcNext.value.length === 1) {
      wcChampion.value = wcNext.value[0]
    } else {
      wcPool.value   = wcNext.value
      wcNext.value   = []
      wcPairIdx.value = 0
    }
  } else {
    wcPairIdx.value = nextIdx
  }
}
function resetWorldCup() {
  wcPool.value     = []
  wcNext.value     = []
  wcPairIdx.value  = 0
  wcChampion.value = null
  wcStarted.value  = false
  wcChosenId.value = null
}

// ────────────────────── 📰 시사 퀴즈 ──────────────────────
const QUIZ_BANK = [
  // ── 기존 20문제 ──
  { q: '대한민국 헌법 제1조 1항에서 정의한 국가 형태는?', opts: ['연방공화국','민주공화국','입헌군주국','사회주의 공화국'], ans: 1 },
  { q: '대통령의 임기와 헌법 규정은?', opts: ['4년 1회 연임','5년 단임','6년 단임','4년 2회 연임'], ans: 1 },
  { q: '국회의원의 임기는?', opts: ['3년','4년','5년','6년'], ans: 1 },
  { q: '헌법재판소 재판관의 정원은?', opts: ['7명','9명','11명','13명'], ans: 1 },
  { q: '법률안 거부권을 행사할 수 있는 기관은?', opts: ['국무총리','국회의장','대통령','대법원장'], ans: 2 },
  { q: '대통령이 거부한 법률안을 국회가 재의결하려면?', opts: ['재적 과반수','재적 2/3 이상','재적 3/4 이상','만장일치'], ans: 1 },
  { q: '"자유권"에 해당하지 않는 것은?', opts: ['신체의 자유','표현의 자유','교육받을 권리','종교의 자유'], ans: 2 },
  { q: '국무총리 임명 절차는?', opts: ['국회에서 선출','대통령 단독 임명','국회 동의 후 대통령 임명','국무위원 호선'], ans: 2 },
  { q: '선거관리위원회의 역할로 옳지 않은 것은?', opts: ['선거 관리','국민투표 관리','정당 사무 관리','법률안 심사'], ans: 3 },
  { q: '최고 사법기관으로 최종 판결을 내리는 곳은?', opts: ['헌법재판소','대법원','고등법원','서울중앙지법'], ans: 1 },
  { q: '국회 국정감사는 매년 정기적으로 몇 일간 진행되나?', opts: ['10일','20일','30일','60일'], ans: 1 },
  { q: '지방자치단체장의 임기는?', opts: ['3년','4년','5년','6년'], ans: 1 },
  { q: '국회 상임위원회의 주요 역할은?', opts: ['법률 최종 의결','예산안 최종 확정','분야별 법률안 심사','헌법 해석'], ans: 2 },
  { q: '대통령 선거에서 당선인이 되기 위한 조건은?', opts: ['절대 다수결','상대 다수결(최다 득표)','2/3 이상 득표','결선 투표제'], ans: 1 },
  { q: '국민이 직접 헌법 개정안에 투표하는 제도는?', opts: ['국민 발안','국민 소환','국민 투표','국민 청원'], ans: 2 },
  { q: '감사원의 주요 역할은?', opts: ['사법부 감시','국가 결산 검사 및 행정기관 감찰','국회 예산 심의','외교 정책 수립'], ans: 1 },
  { q: '헌법기관이 아닌 것은?', opts: ['국무조정실','국회','대법원','헌법재판소'], ans: 0 },
  { q: '비례대표제의 장점으로 옳은 것은?', opts: ['지역 대표성이 강하다','사표를 줄이고 다양한 민의를 반영','유권자와의 직접 연결이 강하다','선거 비용이 적게 든다'], ans: 1 },
  { q: '대통령 탄핵 소추의 의결 요건은?', opts: ['재적 과반수','재적 2/3 이상','재적 3/4 이상','재적 전원'], ans: 1 },
  { q: '행정부 최고 심의기관(대통령·국무총리·각 장관 구성)은?', opts: ['국가안전보장회의','감사원','국무회의','경제사회노동위원회'], ans: 2 },
  // ── 추가 30문제 ──
  { q: '국무위원의 해임을 건의할 수 있는 사람은?', opts: ['대통령','국무총리','국회의장','감사원장'], ans: 1 },
  { q: '선거권(투표권)을 행사할 수 있는 최소 연령은?', opts: ['만 17세','만 18세','만 19세','만 20세'], ans: 1 },
  { q: '비례대표 국회의원 정수는 총 300석 중 몇 석인가?', opts: ['44석','47석','54석','60석'], ans: 1 },
  { q: '대법원 대법관(대법원장 포함) 총 정원은?', opts: ['11명','13명','15명','17명'], ans: 1 },
  { q: '국회 교섭단체 구성에 필요한 최소 의원 수는?', opts: ['10명','20명','30명','40명'], ans: 1 },
  { q: '헌법재판소가 위헌 결정을 내리려면 재판관 몇 명 이상 찬성이 필요한가?', opts: ['5명','6명','7명','전원'], ans: 1 },
  { q: '국가인권위원회의 성격으로 옳은 것은?', opts: ['정부 부처 소속','대통령 직속','독립적 국가기관','국회 소속'], ans: 2 },
  { q: '예산안은 회계연도 개시 며칠 전까지 국회에 제출해야 하나?', opts: ['60일','90일','120일','180일'], ans: 1 },
  { q: '국회에서 의결된 법률안은 정부 이송 후 며칠 이내에 공포해야 하나?', opts: ['10일','15일','20일','30일'], ans: 1 },
  { q: '헌법재판소 재판관의 임기는?', opts: ['4년','5년','6년','9년'], ans: 2 },
  { q: '정당 해산 심판을 할 수 있는 기관은?', opts: ['대법원','헌법재판소','국회','선거관리위원회'], ans: 1 },
  { q: '국민이 직접 법률안을 제안할 수 있는 제도는?', opts: ['국민 소환','국민 발안','국민 투표','국민 청원'], ans: 1 },
  { q: '검찰총장의 임기는?', opts: ['2년','3년','4년','5년'], ans: 0 },
  { q: '광역자치단체에 해당하지 않는 것은?', opts: ['특별시','광역시','도','군'], ans: 3 },
  { q: '지방의회 의원의 임기는?', opts: ['2년','3년','4년','5년'], ans: 2 },
  { q: '대한민국 임시정부가 수립된 해는?', opts: ['1917년','1919년','1921년','1923년'], ans: 1 },
  { q: '4·19 혁명이 일어난 해는?', opts: ['1958년','1960년','1961년','1963년'], ans: 1 },
  { q: '대통령 탄핵 심판을 결정하는 기관은?', opts: ['대법원','국회','헌법재판소','선거관리위원회'], ans: 2 },
  { q: '국회의원이 원내 직무상 발언에 대해 갖는 특권은?', opts: ['불체포 특권','면책 특권','국정감사권','예산심의권'], ans: 1 },
  { q: '국무회의 의장을 맡는 사람은?', opts: ['국회의장','국무총리','대통령','감사원장'], ans: 2 },
  { q: '대한민국 헌법이 최초로 제정된 해는?', opts: ['1945년','1946년','1948년','1950년'], ans: 2 },
  { q: '지방교육청 교육감은 어떻게 선출되나?', opts: ['대통령이 임명','도지사가 임명','주민 직접 선거','교육부장관 임명'], ans: 2 },
  { q: '감사원장의 임기는?', opts: ['3년','4년','5년','6년'], ans: 1 },
  { q: '국회가 계엄 해제를 요구하면 대통령은?', opts: ['거부할 수 있다','30일 내 해제해야 한다','즉시 해제해야 한다','국무회의 의결이 필요하다'], ans: 2 },
  { q: '기본권 침해에 대한 최후 구제 수단으로 헌법에 규정된 것은?', opts: ['행정소송','헌법소원','민사소송','국민청원'], ans: 1 },
  { q: '헌법 개정의 발의 주체로 옳은 것은?', opts: ['대통령만 가능','국회 재적 과반수 또는 대통령','국회 재적 2/3 이상','국민 10만 명 이상'], ans: 1 },
  { q: '대통령 권한대행 시 첫 번째 순서는?', opts: ['부총리','국무총리','국회의장','대법원장'], ans: 1 },
  { q: '헌법 제1조 2항에서 대한민국의 주권은 누구에게 있다고 규정하나?', opts: ['국회','대통령','국민','정부'], ans: 2 },
  { q: '다음 중 헌법에 규정된 의무가 아닌 것은?', opts: ['납세의 의무','국방의 의무','근로의 의무','자원봉사의 의무'], ans: 3 },
  { q: '국회의원의 면책 특권이 적용되는 범위는?', opts: ['국회 내 직무상 발언·표결에만','국회 밖 발언에도 적용','모든 공식 발언에 적용','민사소송만 면제'], ans: 0 },
]

const quizQuestions  = ref([])
const quizStarted    = ref(false)
const quizCurrentIdx = ref(0)
const quizSelected   = ref(null)
const quizAnswered   = ref(false)
const quizResults    = ref([])
const quizFinished   = ref(false)
const quizScore      = computed(() => quizResults.value.filter(r => r.correct).length)

function startQuiz() {
  const shuffled       = [...QUIZ_BANK].sort(() => Math.random() - 0.5)
  quizQuestions.value  = shuffled.slice(0, 10)
  quizStarted.value    = true
  quizCurrentIdx.value = 0
  quizSelected.value   = null
  quizAnswered.value   = false
  quizResults.value    = []
  quizFinished.value   = false
}
function selectQuizAnswer(optIdx) {
  if (quizAnswered.value) return
  quizSelected.value = optIdx
  quizAnswered.value = true
  const q = quizQuestions.value[quizCurrentIdx.value]
  quizResults.value.push({ correct: optIdx === q.ans, selected: optIdx, answer: q.ans })
}
function nextQuiz() {
  const next = quizCurrentIdx.value + 1
  if (next >= quizQuestions.value.length) { quizFinished.value = true }
  else { quizCurrentIdx.value = next; quizSelected.value = null; quizAnswered.value = false }
}
function resetQuiz() {
  quizStarted.value = false; quizCurrentIdx.value = 0; quizSelected.value = null
  quizAnswered.value = false; quizResults.value = []; quizFinished.value = false
  quizQuestions.value = []
}
function quizGradeMsg(score) {
  if (score === 10) return '🏆 완벽! 정치·헌법 박사 인증!'
  if (score >= 8)   return '🎉 우수! 꽤 높은 정치 상식이에요.'
  if (score >= 6)   return '👍 보통 수준, 조금만 더 공부해요!'
  if (score >= 4)   return '😅 아직 갈 길이 멀어요. 복습 필수!'
  return '😱 정치 상식 긴급 처방이 필요합니다!'
}
function quizOptClass(optIdx) {
  if (!quizAnswered.value)
    return 'border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:border-violet-400 hover:bg-violet-50 dark:hover:bg-violet-900/20'
  const q = quizQuestions.value[quizCurrentIdx.value]
  if (optIdx === q.ans)              return 'border-2 border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 font-bold'
  if (optIdx === quizSelected.value) return 'border-2 border-red-400 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 line-through'
  return 'border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-400 opacity-60'
}

// ────────────────────── 🧬 닮은꼴 정치인 찾기 ──────────────────────
const POL_POOL = [
  { q: '최저임금 정책에 대한 생각은?', opts: [
    { text: '대폭 인상이 필요하다', score: -2 }, { text: '점진적으로 올려야 한다', score: -1 },
    { text: '현 수준이 적당하다', score: 1 },    { text: '오히려 낮춰야 한다', score: 2 } ] },
  { q: '원자력 발전소 정책에 대한 입장은?', opts: [
    { text: '즉각 탈핵을 추진해야 한다', score: -2 }, { text: '단계적으로 줄여나가야 한다', score: -1 },
    { text: '현 수준을 유지해야 한다', score: 1 },    { text: '원전을 더 늘려야 한다', score: 2 } ] },
  { q: '남북관계 개선 방향은?', opts: [
    { text: '무조건적 대화와 지원으로 교류 확대', score: -2 }, { text: '조건부 대화, 단계적 교류', score: -1 },
    { text: '상호주의 원칙에 따라 신중하게', score: 1 },       { text: '비핵화 없이는 대화 없다', score: 2 } ] },
  { q: '부동산 정책의 우선순위는?', opts: [
    { text: '강력한 규제·세금으로 집값 안정', score: -2 }, { text: '공공임대 확대와 재건축 규제', score: -1 },
    { text: '공급 확대와 규제 완화 병행', score: 1 },      { text: '시장 자율, 규제 최소화', score: 2 } ] },
  { q: '경제 성장의 핵심 동력은?', opts: [
    { text: '노동자 임금 인상과 내수 확대', score: -2 }, { text: '중소기업 지원과 균형 성장', score: -1 },
    { text: '규제 완화와 투자 유치', score: 1 },          { text: '대기업 중심 수출 강화', score: 2 } ] },
  { q: '안보 정책 기조는?', opts: [
    { text: '평화 협정으로 한반도 긴장 완화', score: -2 }, { text: '대화와 억지력을 균형 있게 유지', score: -1 },
    { text: '한미동맹 강화와 자주국방 병행', score: 1 },   { text: '강력한 군사력·동맹 강화 최우선', score: 2 } ] },
  { q: '교육 정책의 방향은?', opts: [
    { text: '수능 폐지, 공교육 완전 강화', score: -2 }, { text: '사교육비 경감, 입시 다양화', score: -1 },
    { text: '자사고 등 다양한 선택권 보장', score: 1 }, { text: '경쟁을 통한 교육 수월성 강화', score: 2 } ] },
  { q: '세금과 복지에 대한 입장은?', opts: [
    { text: '부자 증세로 복지를 대폭 확대', score: -2 }, { text: '중간 수준 증세로 복지 강화', score: -1 },
    { text: '현 세율 유지, 복지 효율화', score: 1 },     { text: '감세로 경제 활성화, 복지 민간 활용', score: 2 } ] },
  { q: '검찰 권력에 대한 입장은?', opts: [
    { text: '공수처를 강화하고 검찰 권한을 대폭 축소해야 한다', score: -2 },
    { text: '검찰 권한을 분산하고 민주적 통제를 강화해야 한다', score: -1 },
    { text: '검찰의 정치적 독립성을 강화해야 한다', score: 1 },
    { text: '검찰이 강력한 수사권을 유지해야 안정이 된다', score: 2 } ] },
  { q: '언론 정책에 대한 생각은?', opts: [
    { text: '가짜뉴스 엄격 규제와 공영방송 독립성 강화가 필요하다', score: -2 },
    { text: '언론 다양성 보장과 공정 보도 의무를 강화해야 한다', score: -1 },
    { text: '언론은 시장에 맡기고 정부 개입을 최소화해야 한다', score: 1 },
    { text: '언론 자유가 최우선이며 규제는 불필요하다', score: 2 } ] },
  { q: '노동시장 유연화에 대한 입장은?', opts: [
    { text: '해고를 엄격히 제한하고 노동자 보호를 강화해야 한다', score: -2 },
    { text: '노동자 보호를 유지하면서 합리적인 고용 유연성은 필요하다', score: -1 },
    { text: '기업이 고용과 해고를 자유롭게 결정해야 경쟁력이 생긴다', score: 1 },
    { text: '노동 유연성을 대폭 확대해 기업 활력을 높여야 한다', score: 2 } ] },
  { q: '의료 서비스에 대한 생각은?', opts: [
    { text: '의료는 국가가 책임지는 완전한 공공재여야 한다', score: -2 },
    { text: '건강보험 확대로 의료 안전망을 강화해야 한다', score: -1 },
    { text: '의료 선택권과 민간 의료시장을 활성화해야 한다', score: 1 },
    { text: '의료도 시장 원리에 따라 경쟁해야 질이 높아진다', score: 2 } ] },
  { q: '한미동맹에 대한 입장은?', opts: [
    { text: '주한미군 철수를 포함한 자주 외교로 전환해야 한다', score: -2 },
    { text: '동맹은 유지하되 대화를 통한 균형 외교를 추구해야 한다', score: -1 },
    { text: '한미동맹을 강화하면서 방위비는 합리적으로 협상해야 한다', score: 1 },
    { text: '한미동맹 강화와 주한미군 역할 확대가 안보의 핵심이다', score: 2 } ] },
  { q: '세금 정책에 대한 입장은?', opts: [
    { text: '고소득자와 대기업을 대상으로 대폭 증세해야 한다', score: -2 },
    { text: '중간 수준의 증세로 복지와 공공서비스를 강화해야 한다', score: -1 },
    { text: '현 세율을 유지하며 세금의 효율적 집행이 중요하다', score: 1 },
    { text: '법인세·소득세 감세로 투자와 소비를 촉진해야 한다', score: 2 } ] },
  { q: '정부의 역할과 규모에 대한 생각은?', opts: [
    { text: '정부가 적극적으로 시장에 개입하고 공공 영역을 확대해야 한다', score: -2 },
    { text: '시장 실패 분야에 한해 정부가 보완적 역할을 해야 한다', score: -1 },
    { text: '정부 규모를 줄이고 민간의 창의성과 자율에 맡겨야 한다', score: 1 },
    { text: '작은 정부, 낮은 세금이 경제 발전의 핵심이다', score: 2 } ] },
  { q: '기후변화 대응에 대한 입장은?', opts: [
    { text: '경제 성장을 희생하더라도 강력한 탄소 규제가 필요하다', score: -2 },
    { text: '탄소세와 재생에너지 전환으로 점진적으로 대응해야 한다', score: -1 },
    { text: '원전을 포함한 현실적 에너지 믹스로 대응해야 한다', score: 1 },
    { text: '기후 규제가 산업 경쟁력을 해치므로 신중해야 한다', score: 2 } ] },
  { q: '이민 정책에 대한 생각은?', opts: [
    { text: '저출산 해결을 위해 이민 문호를 대폭 개방해야 한다', score: -2 },
    { text: '필요 인력 중심으로 선별적 이민을 단계적으로 확대해야 한다', score: -1 },
    { text: '사회 통합을 고려해 이민을 신중하게 제한적으로 받아야 한다', score: 1 },
    { text: '국가 정체성 보호를 위해 이민을 강력히 제한해야 한다', score: 2 } ] },
  { q: '사형제도에 대한 입장은?', opts: [
    { text: '인권 침해이므로 즉각 폐지해야 한다', score: -2 },
    { text: '단계적 폐지를 추진하면서 대안을 마련해야 한다', score: -1 },
    { text: '흉악 범죄 억제를 위해 현행 유지가 필요하다', score: 1 },
    { text: '흉악 범죄에 적극 집행해 범죄를 억제해야 한다', score: 2 } ] },
  { q: '성평등 정책에 대한 생각은?', opts: [
    { text: '여성 할당제 등 적극적 우대 조치를 강화해야 한다', score: -2 },
    { text: '구조적 성차별을 해소하기 위한 제도적 지원이 필요하다', score: -1 },
    { text: '개인의 능력과 실력 중심으로 공정하게 경쟁해야 한다', score: 1 },
    { text: '성별 할당제는 역차별이며 능력 위주 선발이 옳다', score: 2 } ] },
  { q: '재벌·대기업 정책에 대한 입장은?', opts: [
    { text: '재벌 해체 수준의 강력한 지배구조 개혁이 필요하다', score: -2 },
    { text: '순환출자 해소와 지배구조 투명화를 강화해야 한다', score: -1 },
    { text: '대기업이 국가 경쟁력의 핵심이므로 규제를 완화해야 한다', score: 1 },
    { text: '대기업이 더 자유롭게 투자하도록 규제를 최소화해야 한다', score: 2 } ] },
]

// 점수 범위: 10문제 × ±2 = -20 ~ +20
const ARCHETYPES = [
  {
    range: [-20, -12], emoji: '🌊', name: '심상정·조국형', badge: '적극적 진보', color: '#1d4ed8',
    politicians: [
      { name: '심상정', party: '정의당', role: '前 대선후보·대표' },
      { name: '조국',   party: '조국혁신당', role: '대표' },
    ],
    desc: '강력한 복지 확대, 노동권 강화, 재벌 개혁을 최우선으로 삼는 적극적 진보형입니다. 사회적 약자와 함께하는 근본적 변화를 추구합니다.',
    style: '기득권에 맞서 아래로부터의 변화를 이끌며, 원칙과 소신에 입각한 강한 추진력을 가집니다.',
  },
  {
    range: [-11, -5], emoji: '🌱', name: '이재명형', badge: '실용적 진보', color: '#2563eb',
    politicians: [
      { name: '이재명', party: '더불어민주당', role: '대표' },
      { name: '박찬대', party: '더불어민주당', role: '前 원내대표' },
    ],
    desc: '민생 경제 개선과 복지 강화를 동시에 추구하는 실용적 진보형입니다. 강한 추진력으로 변화를 실현하려 합니다.',
    style: '현실적인 방법으로 사회를 개선하고, 민생을 최우선에 두는 강력한 리더십을 발휘합니다.',
  },
  {
    range: [-4, -1], emoji: '🕊️', name: '이낙연형', badge: '온건 진보', color: '#4f46e5',
    politicians: [
      { name: '이낙연', party: '前 더불어민주당', role: '前 국무총리·대표' },
    ],
    desc: '협치와 통합을 중시하는 온건한 진보형입니다. 점진적인 개혁과 사회적 대화를 통한 변화를 추구합니다.',
    style: '갈등보다 화합을 선택하며, 합리적인 대화로 사회적 합의를 이끌어냅니다.',
  },
  {
    range: [0, 0], emoji: '⚖️', name: '안철수·이준석형', badge: '합리적 중도', color: '#7c3aed',
    politicians: [
      { name: '안철수', party: '국민의힘', role: '의원' },
      { name: '이준석', party: '개혁신당', role: '대표' },
    ],
    desc: '이념보다 실용, 갈등보다 통합을 선택하는 합리적 중도형입니다. 양측의 장점을 취해 최선의 해법을 찾습니다.',
    style: '협치와 타협에 능하며 극단을 지양하고 중심을 잡는 균형 잡힌 리더십을 발휘합니다.',
  },
  {
    range: [1, 4], emoji: '🏗️', name: '오세훈·홍준표형', badge: '실용적 보수', color: '#ea580c',
    politicians: [
      { name: '오세훈', party: '국민의힘', role: '서울특별시장' },
      { name: '홍준표', party: '국민의힘', role: '대구광역시장' },
    ],
    desc: '경제 성장과 시장 자유를 중시하면서도 변화의 필요성을 인정하는 실용적 보수형입니다.',
    style: '성과와 효율을 중시하며 강력한 실행력으로 목표를 달성하는 스타일입니다.',
  },
  {
    range: [5, 11], emoji: '🛡️', name: '한동훈·나경원형', badge: '정통 보수', color: '#dc2626',
    politicians: [
      { name: '한동훈', party: '국민의힘', role: '前 대표' },
      { name: '나경원', party: '국민의힘', role: '의원' },
      { name: '원희룡', party: '국민의힘', role: '前 장관' },
    ],
    desc: '법질서 확립, 안보 강화, 시장경제 원리를 핵심 가치로 삼는 정통 보수형입니다.',
    style: '원칙에 충실하고 강한 신념으로 보수 가치를 수호하는 리더십을 발휘합니다.',
  },
  {
    range: [12, 20], emoji: '🦅', name: '윤석열형', badge: '강경 보수', color: '#b91c1c',
    politicians: [
      { name: '윤석열', party: '국민의힘', role: '前 대통령' },
      { name: '권성동', party: '국민의힘', role: '의원' },
    ],
    desc: '국가 안보와 법질서를 최우선으로 하는 강경 보수형입니다. 강력한 원칙과 단호한 결단으로 국가를 이끌려 합니다.',
    style: '타협보다 원칙, 인기보다 소신을 선택하며 강한 실행력으로 정책을 추진합니다.',
  },
]

const polStarted   = ref(false)
const polCurrentQ  = ref(0)
const polScore     = ref(0)
const polResult    = ref(null)
const polPending   = ref(false)
const polQuestions = ref([])
const polProgress  = computed(() => Math.round((polCurrentQ.value / (polQuestions.value.length || 10)) * 100))

function startPolTest() {
  polQuestions.value = [...POL_POOL].sort(() => Math.random() - 0.5).slice(0, 10)
  polStarted.value   = true
  polCurrentQ.value  = 0
  polScore.value     = 0
  polResult.value    = null
  polPending.value   = false
}
async function answerPol(score) {
  if (polPending.value) return
  polPending.value = true
  polScore.value += score
  await new Promise(r => setTimeout(r, 220))
  const next = polCurrentQ.value + 1
  if (next >= polQuestions.value.length) {
    polResult.value = ARCHETYPES.find(a => polScore.value >= a.range[0] && polScore.value <= a.range[1]) ?? ARCHETYPES[3]
  } else { polCurrentQ.value = next }
  polPending.value = false
}
function resetPolTest() {
  polStarted.value   = false
  polCurrentQ.value  = 0
  polScore.value     = 0
  polResult.value    = null
  polPending.value   = false
  polQuestions.value = []
}

// ────────────────────── 📊 나의 성향 공유카드 ──────────────────────
const FACTION_MAP = {
  conservative: { label: '보수', emoji: '🔴', bg: '#E24B4A', bar: 0.82 },
  moderate:     { label: '중도', emoji: '🟣', bg: '#7F77DD', bar: 0.50 },
  progressive:  { label: '진보', emoji: '🔵', bg: '#378ADD', bar: 0.18 },
}
const previewFc = computed(() => {
  const u = authUser.value
  return (u && FACTION_MAP[u.political_type]) ? FACTION_MAP[u.political_type] : FACTION_MAP.moderate
})

function downloadShareCard() {
  const user = authUser.value
  if (!user || !user.test_completed) return
  const W = 800, H = 460
  const canvas = document.createElement('canvas')
  const dpr    = Math.min(window.devicePixelRatio || 1, 2)
  canvas.width  = W * dpr
  canvas.height = H * dpr
  const ctx = canvas.getContext('2d')
  ctx.scale(dpr, dpr)
  const fc = FACTION_MAP[user.political_type] ?? FACTION_MAP.moderate

  // 배경
  const bg = ctx.createLinearGradient(0, 0, W, H)
  bg.addColorStop(0, '#0f172a'); bg.addColorStop(1, '#1e293b')
  ctx.fillStyle = bg; ctx.fillRect(0, 0, W, H)

  // 좌측 진영 컬러 바
  ctx.fillStyle = fc.bg; ctx.fillRect(0, 0, 8, H)

  // 우측 배경 원
  ctx.beginPath(); ctx.arc(710, 230, 160, 0, Math.PI * 2)
  ctx.fillStyle = fc.bg + '18'; ctx.fill()
  ctx.font = '110px serif'; ctx.textAlign = 'center'
  ctx.fillStyle = fc.bg; ctx.fillText(fc.emoji, 710, 265)

  // 상단 텍스트
  ctx.textAlign = 'left'
  ctx.fillStyle = '#64748b'; ctx.font = '15px sans-serif'
  ctx.fillText('나의 정치 성향', 50, 75)
  ctx.fillStyle = 'white'; ctx.font = 'bold 80px sans-serif'
  ctx.fillText(fc.label, 50, 175)
  ctx.fillStyle = '#94a3b8'; ctx.font = '20px sans-serif'
  ctx.fillText('@' + (user.nickname ?? '폴릿 유저'), 50, 215)

  // 구분선
  ctx.strokeStyle = '#334155'; ctx.lineWidth = 1
  ctx.beginPath(); ctx.moveTo(50, 240); ctx.lineTo(560, 240); ctx.stroke()

  // 스펙트럼 바
  ctx.fillStyle = '#1e293b'; ctx.fillRect(50, 262, 460, 18)
  const barGrad = ctx.createLinearGradient(50, 0, 510, 0)
  barGrad.addColorStop(0, '#378ADD'); barGrad.addColorStop(0.5, '#7F77DD'); barGrad.addColorStop(1, '#E24B4A')
  ctx.fillStyle = barGrad; ctx.fillRect(50, 262, 460, 18)
  const mx = 50 + fc.bar * 460
  ctx.beginPath(); ctx.arc(mx, 271, 14, 0, Math.PI * 2)
  ctx.fillStyle = 'white'; ctx.fill()
  ctx.strokeStyle = fc.bg; ctx.lineWidth = 3.5; ctx.stroke()
  ctx.fillStyle = '#475569'; ctx.font = '13px sans-serif'
  ctx.textAlign = 'left';  ctx.fillText('진보', 50, 298)
  ctx.textAlign = 'right'; ctx.fillText('보수', 510, 298)

  // 매너 점수
  ctx.textAlign = 'left'; ctx.fillStyle = '#64748b'; ctx.font = '14px sans-serif'
  if (user.manner_score != null) ctx.fillText('매너 점수  ' + user.manner_score + '점', 50, 360)

  // 날짜 + 브랜드
  const today = new Date()
  const ds = today.getFullYear() + '.' + String(today.getMonth()+1).padStart(2,'0') + '.' + String(today.getDate()).padStart(2,'0')
  ctx.fillStyle = '#334155'; ctx.fillText(ds, 50, 430)
  ctx.textAlign = 'right'; ctx.fillStyle = '#475569'; ctx.font = 'bold 16px sans-serif'
  ctx.fillText('POLIT.KR', W - 30, H - 18)

  const link = document.createElement('a')
  link.download = 'polit-' + user.political_type + '-card.png'
  link.href = canvas.toDataURL('image/png')
  link.click()
}
</script>

<template>
<Head title="툴박스">
  <meta name="description" content="이상형 월드컵, 시사 퀴즈, 닮은꼴 정치인 찾기, 성향 공유카드 등 재미있는 정치 도구" />
  <meta property="og:title" content="툴박스 — 폴릿" />
</Head>
<div class="max-w-5xl mx-auto px-4 py-10">

  <!-- 헤더 -->
  <div class="mb-10">
    <h1 class="text-3xl font-black text-slate-900 dark:text-white mb-2">🧰 툴박스</h1>
    <p class="text-slate-500 dark:text-slate-400">로그인 없이 누구나 이용할 수 있는 재미있는 기능 모음</p>
  </div>

  <!-- ROW 1: 로또 + 운세 -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    <!-- 🎰 로또번호생성기 -->
    <div id="lotto" class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200 dark:border-slate-800 flex items-center gap-3">
        <span class="text-2xl">🎰</span>
        <div><h2 class="text-lg font-black text-slate-900 dark:text-white">로또번호생성기</h2>
          <p class="text-xs text-slate-400">6/45 랜덤 번호 자동 생성</p></div>
      </div>
      <div class="p-6">
        <div class="flex items-center gap-3 mb-5">
          <span class="text-sm text-slate-500 flex-shrink-0">생성 세트</span>
          <div class="flex gap-2">
            <button v-for="n in [1,3,5,10]" :key="n" @click="setCount = n"
              :class="['w-9 h-9 rounded-lg text-sm font-bold transition-all', setCount===n ? 'bg-violet-600 text-white' : 'bg-gray-100 dark:bg-slate-800 text-slate-500 hover:bg-gray-200 dark:hover:bg-slate-700']">{{ n }}</button>
          </div>
        </div>
        <button @click="generateLotto" :disabled="isAnimating"
          :class="['w-full py-3.5 rounded-xl font-black text-lg transition-all mb-6', isAnimating ? 'bg-gray-200 dark:bg-slate-700 text-slate-400 cursor-not-allowed' : 'bg-gradient-to-r from-violet-600 to-violet-500 hover:from-violet-500 text-white shadow-lg hover:-translate-y-0.5']">
          {{ isAnimating ? '생성 중...' : '🎲 번호 생성' }}
        </button>
        <div v-if="animatedSets.length" class="space-y-3">
          <div v-for="(set,si) in animatedSets" :key="set.id" class="bg-gray-100 dark:bg-slate-800/60 rounded-xl p-3 flex items-center gap-2">
            <span class="text-xs text-slate-400 w-5 font-bold">{{ si+1 }}</span>
            <div class="flex gap-1.5 flex-wrap">
              <span v-for="num in set.numbers" :key="num"
                :class="['w-9 h-9 rounded-full flex items-center justify-center text-sm font-black shadow-sm', lottoColor(num).bg, lottoColor(num).text]">{{ num }}</span>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-8 text-slate-400"><p class="text-4xl mb-2">🎱</p><p class="text-sm">버튼을 눌러 번호를 생성하세요</p></div>
        <div class="mt-5 pt-4 border-t border-gray-200 dark:border-slate-800 flex flex-wrap gap-2 justify-center">
          <div v-for="c in LOTTO_COLORS" :key="c.range[0]" class="flex items-center gap-1">
            <div :class="['w-5 h-5 rounded-full text-xs flex items-center justify-center font-bold', c.bg, c.text]">{{ c.range[0] }}</div>
            <span class="text-xs text-slate-400">~{{ c.range[1] }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 🔮 오늘의 운세 -->
    <div id="fortune" class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200 dark:border-slate-800 flex items-center gap-3">
        <span class="text-2xl">🔮</span>
        <div><h2 class="text-lg font-black text-slate-900 dark:text-white">오늘의 운세</h2>
          <p class="text-xs text-slate-400">나의 띠를 선택하면 오늘 운세를 알려드립니다</p></div>
      </div>
      <div class="p-6">
        <div class="grid grid-cols-4 gap-2 mb-5">
          <button v-for="(z,idx) in ZODIAC_ANIMALS" :key="idx" @click="revealFortune(idx)"
            :class="['rounded-xl py-2 px-1 text-center transition-all text-xs', selectedZodiac===idx ? 'bg-violet-600 text-white scale-105' : 'bg-gray-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-700']">
            <div class="text-lg mb-0.5">{{ z.name.split(' ')[0] }}</div>
            <div class="font-semibold">{{ z.name.split(' ')[1] }}</div>
          </button>
        </div>
        <transition name="fade">
          <div v-if="isRevealingFortune" class="text-center py-8">
            <div class="text-4xl animate-spin-slow mb-3">🔮</div>
            <p class="text-slate-400 text-sm">운세를 불러오는 중...</p>
          </div>
          <div v-else-if="fortuneResult" class="bg-gray-100 dark:bg-slate-800/60 rounded-xl p-5">
            <div class="flex items-center justify-between mb-4">
              <div>
                <span class="text-xl font-black text-slate-900 dark:text-white">{{ ZODIAC_ANIMALS[selectedZodiac].name }}</span>
                <span class="text-xs text-slate-400 ml-2">{{ ZODIAC_ANIMALS[selectedZodiac].years }}</span>
              </div>
              <span :class="['text-2xl font-black', fortuneScoreColor]">{{ fortuneResult.score }}점</span>
            </div>
            <div class="h-2 bg-gray-200 dark:bg-slate-700 rounded-full mb-3 overflow-hidden">
              <div class="h-full rounded-full bg-gradient-to-r from-violet-500 to-yellow-400 transition-all duration-700" :style="{ width: fortuneResult.score + '%' }"></div>
            </div>
            <div :class="['text-sm font-bold mb-2', fortuneScoreColor]">✨ {{ fortuneResult.summary }}</div>
            <p class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed mb-4">{{ fortuneResult.desc }}</p>
            <div class="grid grid-cols-3 gap-2 pt-3 border-t border-gray-300 dark:border-slate-700">
              <div class="text-center"><p class="text-xs text-slate-400 mb-1">행운의 색</p><p class="text-sm font-bold text-slate-900 dark:text-white">{{ fortuneResult.lucky.color }}</p></div>
              <div class="text-center border-x border-gray-300 dark:border-slate-700"><p class="text-xs text-slate-400 mb-1">행운의 숫자</p><p class="text-sm font-bold text-slate-900 dark:text-white">{{ fortuneResult.lucky.number }}</p></div>
              <div class="text-center"><p class="text-xs text-slate-400 mb-1">행운의 방향</p><p class="text-sm font-bold text-slate-900 dark:text-white">{{ fortuneResult.lucky.direction }}</p></div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-slate-400"><p class="text-4xl mb-2">🐭</p><p class="text-sm">위에서 나의 띠를 선택하세요</p></div>
        </transition>
      </div>
    </div>
  </div><!-- /ROW 1 -->

  <!-- ROW 2: 이상형 월드컵 + 시사 퀴즈 -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    <!-- 💘 이상형 월드컵 -->
    <div id="worldcup" class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200 dark:border-slate-800 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <span class="text-2xl">💘</span>
          <div><h2 class="text-lg font-black text-slate-900 dark:text-white">이상형 월드컵</h2>
            <p class="text-xs text-slate-400">32개 중 16개 무작위 선택 토너먼트</p></div>
        </div>
        <span v-if="wcStarted && !wcChampion" class="text-xs font-bold text-pink-500 bg-pink-50 dark:bg-pink-900/20 px-2.5 py-1 rounded-full">{{ wcRoundLabel }}</span>
      </div>
      <div class="p-6">

        <!-- 시작 전 -->
        <div v-if="!wcStarted">
          <p class="text-sm text-slate-600 dark:text-slate-300 mb-4 leading-relaxed">
            <span class="font-bold text-pink-500">32개 정치 가치관</span> 중 무작위로 선택된 <span class="font-bold text-pink-500">16개</span>로 토너먼트를 진행합니다. 매번 새로운 조합!
          </p>
          <div class="grid grid-cols-4 gap-1 mb-5">
            <div v-for="item in WC_ITEMS" :key="item.id" class="bg-gray-100 dark:bg-slate-800 rounded-lg p-1 text-center">
              <div class="text-base">{{ item.emoji }}</div>
              <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium leading-tight mt-0.5 truncate">{{ item.title }}</div>
            </div>
          </div>
          <button @click="startWorldCup" class="w-full py-3.5 bg-gradient-to-r from-pink-500 to-rose-500 hover:from-pink-400 text-white font-black rounded-xl shadow-lg hover:-translate-y-0.5 transition-all text-base">
            💘 시작하기
          </button>
        </div>

        <!-- 진행 중 -->
        <div v-else-if="!wcChampion && wcPair">
          <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
            <span>{{ wcCurrentMatch }} / {{ wcTotalMatches }} 경기</span>
            <span class="text-pink-500 font-bold">{{ wcRoundLabel }}</span>
          </div>
          <div class="h-1.5 bg-gray-200 dark:bg-slate-800 rounded-full mb-5 overflow-hidden">
            <div class="h-full bg-gradient-to-r from-pink-500 to-rose-500 transition-all duration-500 rounded-full"
              :style="{ width: ((wcCurrentMatch-1)/wcTotalMatches*100)+'%' }"></div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <button v-for="item in wcPair" :key="item.id" @click="pickWcWinner(item)"
              :class="['flex flex-col items-center text-center p-4 rounded-2xl border-2 transition-all duration-200',
                wcChosenId===item.id ? 'border-pink-500 bg-pink-50 dark:bg-pink-900/20 scale-95' :
                wcChosenId!==null   ? 'border-slate-200 dark:border-slate-700 opacity-40 scale-95' :
                'border-slate-200 dark:border-slate-700 hover:border-pink-400 hover:bg-pink-50 dark:hover:bg-pink-900/10 hover:-translate-y-1 cursor-pointer']">
              <span class="text-4xl mb-2">{{ item.emoji }}</span>
              <h3 class="font-black text-slate-900 dark:text-white text-sm mb-1">{{ item.title }}</h3>
              <p class="text-xs text-slate-400">{{ item.desc }}</p>
            </button>
          </div>
          <div class="flex justify-center mt-3">
            <span class="text-xs font-black text-slate-300 dark:text-slate-600 bg-gray-100 dark:bg-slate-800 w-8 h-8 rounded-full flex items-center justify-center">VS</span>
          </div>
        </div>

        <!-- 챔피언 -->
        <div v-else-if="wcChampion" class="text-center py-2">
          <div class="text-5xl mb-1">🏆</div>
          <p class="text-xs font-bold text-yellow-500 mb-4 tracking-widest">나의 이상형 가치관</p>
          <div class="bg-gradient-to-br from-yellow-50 to-amber-50 dark:from-amber-900/20 dark:to-yellow-900/20 border-2 border-yellow-400/60 rounded-2xl p-6 mb-5">
            <div class="text-5xl mb-3">{{ wcChampion.emoji }}</div>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-2">{{ wcChampion.title }}</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ wcChampion.desc }}</p>
          </div>
          <button @click="resetWorldCup" class="px-6 py-2.5 border border-slate-300 dark:border-slate-700 rounded-xl text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-800 transition-all">
            🔄 다시 하기
          </button>
        </div>
      </div>
    </div>

    <!-- 📰 시사 퀴즈 -->
    <div id="quiz" class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200 dark:border-slate-800 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <span class="text-2xl">📰</span>
          <div><h2 class="text-lg font-black text-slate-900 dark:text-white">시사 퀴즈</h2>
            <p class="text-xs text-slate-400">50문제 풀에서 랜덤 10문제 출제</p></div>
        </div>
        <span v-if="quizStarted && !quizFinished" class="text-xs font-bold text-violet-600 bg-violet-50 dark:bg-violet-900/20 px-2.5 py-1 rounded-full">{{ quizCurrentIdx+1 }} / {{ quizQuestions.length }}</span>
      </div>
      <div class="p-6">

        <!-- 시작 전 -->
        <div v-if="!quizStarted">
          <div class="bg-violet-50 dark:bg-violet-900/20 rounded-xl p-4 mb-5">
            <p class="text-sm text-violet-700 dark:text-violet-300 font-semibold mb-1">🎲 랜덤 퀴즈</p>
            <p class="text-xs text-violet-600 dark:text-violet-400">50개 문제 풀에서 매번 무작위로 10문제가 선택됩니다.</p>
          </div>
          <div class="flex items-center gap-4 mb-6 text-sm text-slate-500">
            <span>❓ 10문제 (랜덤)</span><span>⏱️ 제한 없음</span><span>📚 4지선다</span>
          </div>
          <button @click="startQuiz" class="w-full py-3.5 bg-gradient-to-r from-violet-600 to-violet-500 hover:from-violet-500 text-white font-black rounded-xl shadow-lg hover:-translate-y-0.5 transition-all text-base">
            퀴즈 시작 →
          </button>
        </div>

        <!-- 진행 중 -->
        <div v-else-if="!quizFinished">
          <div class="h-1.5 bg-gray-200 dark:bg-slate-800 rounded-full mb-5 overflow-hidden">
            <div class="h-full bg-violet-500 transition-all duration-500 rounded-full" :style="{ width: (quizCurrentIdx/quizQuestions.length*100)+'%' }"></div>
          </div>
          <p class="text-base font-bold text-slate-900 dark:text-white leading-snug mb-5">
            Q{{ quizCurrentIdx+1 }}. {{ quizQuestions[quizCurrentIdx].q }}
          </p>
          <div class="space-y-2.5 mb-5">
            <button v-for="(opt,i) in quizQuestions[quizCurrentIdx].opts" :key="i"
              @click="selectQuizAnswer(i)" :disabled="quizAnswered"
              :class="['w-full text-left px-4 py-3 rounded-xl text-sm transition-all duration-200', quizOptClass(i)]">
              <span class="font-bold mr-2">{{ ['①','②','③','④'][i] }}</span>{{ opt }}
            </button>
          </div>
          <div v-if="quizAnswered" class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-3 mb-4 text-xs text-slate-500">
            <span class="font-bold text-emerald-600 dark:text-emerald-400">정답: {{ ['①','②','③','④'][quizQuestions[quizCurrentIdx].ans] }}</span>
            {{ quizQuestions[quizCurrentIdx].opts[quizQuestions[quizCurrentIdx].ans] }}
          </div>
          <button v-if="quizAnswered" @click="nextQuiz" class="w-full py-3 bg-violet-600 hover:bg-violet-500 text-white font-bold rounded-xl transition-all text-sm">
            {{ quizCurrentIdx < quizQuestions.length - 1 ? '다음 문제 →' : '결과 보기 →' }}
          </button>
        </div>

        <!-- 결과 -->
        <div v-else class="text-center py-2">
          <div class="text-5xl mb-2">{{ quizScore===10?'🏆':quizScore>=6?'🎉':'📖' }}</div>
          <p class="text-4xl font-black text-slate-900 dark:text-white mb-1">{{ quizScore }}<span class="text-xl text-slate-400 font-normal"> / {{ quizQuestions.length }}</span></p>
          <p class="text-sm font-semibold text-slate-600 dark:text-slate-300 mb-5">{{ quizGradeMsg(quizScore) }}</p>
          <div class="space-y-1.5 mb-5 text-left">
            <div v-for="(r,i) in quizResults" :key="i"
              :class="['flex items-center gap-2 text-xs px-3 py-2 rounded-lg', r.correct ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300' : 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400']">
              <span class="font-bold">{{ r.correct?'✓':'✗' }}</span>
              <span>Q{{ i+1 }}. {{ quizQuestions[i].q.slice(0,32) }}...</span>
            </div>
          </div>
          <button @click="resetQuiz" class="px-6 py-2.5 border border-slate-300 dark:border-slate-700 rounded-xl text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-800 transition-all">
            🔄 다시 풀기
          </button>
        </div>
      </div>
    </div>
  </div><!-- /ROW 2 -->

  <!-- ROW 3: 닮은꼴 정치인 + 성향 공유카드 -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">

    <!-- 🧬 닮은꼴 정치인 찾기 -->
    <div id="politician" class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200 dark:border-slate-800 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <span class="text-2xl">🧬</span>
          <div><h2 class="text-lg font-black text-slate-900 dark:text-white">닮은꼴 정치인 찾기</h2>
            <p class="text-xs text-slate-400">나의 성향과 가장 닮은 실제 정치인은?</p></div>
        </div>
        <span v-if="polStarted && !polResult" class="text-xs font-bold text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20 px-2.5 py-1 rounded-full">
          {{ polCurrentQ+1 }} / {{ polQuestions.length }}
        </span>
      </div>
      <div class="p-6">

        <!-- 시작 전 -->
        <div v-if="!polStarted">
          <p class="text-sm text-slate-600 dark:text-slate-300 mb-4 leading-relaxed">
            20개 정치 현안 중 무작위 <span class="font-bold text-emerald-500">10가지 질문</span>에 답하면, 나와 성향이 닮은 <span class="font-bold text-emerald-500">실제 정치인</span>을 찾아드립니다.
          </p>
          <div class="grid grid-cols-3 gap-2 mb-5">
            <div v-for="a in ARCHETYPES" :key="a.name" class="bg-gray-50 dark:bg-slate-800 rounded-xl p-2.5 text-center border border-gray-200 dark:border-slate-700">
              <div class="text-xl mb-1">{{ a.emoji }}</div>
              <div class="text-xs font-bold text-slate-700 dark:text-slate-200 leading-tight">{{ a.name }}</div>
              <div class="text-xs mt-1 px-1.5 py-0.5 rounded-full inline-block font-medium" :style="{ backgroundColor: a.color+'22', color: a.color }">{{ a.badge }}</div>
            </div>
          </div>
          <button @click="startPolTest" class="w-full py-3.5 bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 text-white font-black rounded-xl shadow-lg hover:-translate-y-0.5 transition-all text-base">
            🧬 테스트 시작
          </button>
        </div>

        <!-- 진행 중 -->
        <div v-else-if="!polResult">
          <div class="h-1.5 bg-gray-200 dark:bg-slate-800 rounded-full mb-5 overflow-hidden">
            <div class="h-full bg-emerald-500 transition-all duration-500 rounded-full" :style="{ width: polProgress+'%' }"></div>
          </div>
          <p class="text-base font-bold text-slate-900 dark:text-white leading-snug mb-5">
            Q{{ polCurrentQ+1 }}. {{ polQuestions[polCurrentQ]?.q }}
          </p>
          <div class="space-y-2.5">
            <button v-for="opt in polQuestions[polCurrentQ]?.opts" :key="opt.text"
              @click="answerPol(opt.score)" :disabled="polPending"
              :class="['w-full text-left px-4 py-3 rounded-xl text-sm transition-all duration-200 border',
                polPending ? 'opacity-50 cursor-not-allowed border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800' :
                'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:border-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 cursor-pointer']">
              {{ opt.text }}
            </button>
          </div>
        </div>

        <!-- 결과 -->
        <div v-else>
          <div class="rounded-2xl border-2 p-5 mb-4" :style="{ borderColor: polResult.color+'60', backgroundColor: polResult.color+'08' }">
            <div class="text-center mb-4">
              <div class="text-5xl mb-2">{{ polResult.emoji }}</div>
              <h3 class="text-xl font-black text-slate-900 dark:text-white mb-1">{{ polResult.name }}</h3>
              <span class="text-xs font-bold px-3 py-1 rounded-full" :style="{ backgroundColor: polResult.color+'22', color: polResult.color }">{{ polResult.badge }}</span>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed mb-3">{{ polResult.desc }}</p>
            <div class="bg-white/60 dark:bg-slate-800/60 rounded-xl p-3 mb-3">
              <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">💡 {{ polResult.style }}</p>
            </div>
            <!-- 닮은 정치인 목록 -->
            <div v-if="polResult.politicians?.length" class="border border-gray-200 dark:border-slate-700 rounded-xl p-3">
              <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">🧑‍💼 닮은 정치인</p>
              <div class="space-y-1.5">
                <div v-for="p in polResult.politicians" :key="p.name" class="flex items-center justify-between text-xs">
                  <span class="font-bold text-slate-800 dark:text-slate-200">{{ p.name }}</span>
                  <span class="text-slate-400">{{ p.party }} · {{ p.role }}</span>
                </div>
              </div>
            </div>
          </div>
          <button @click="resetPolTest" class="w-full py-2.5 border border-slate-300 dark:border-slate-700 rounded-xl text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-800 transition-all">
            🔄 다시 하기
          </button>
        </div>
      </div>
    </div>

    <!-- 📊 나의 성향 공유카드 -->
    <div id="card" class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl overflow-hidden">
      <div class="px-6 py-5 border-b border-gray-200 dark:border-slate-800 flex items-center gap-3">
        <span class="text-2xl">📊</span>
        <div><h2 class="text-lg font-black text-slate-900 dark:text-white">나의 성향 공유카드</h2>
          <p class="text-xs text-slate-400">테스트 결과를 카드로 만들어 SNS에 공유</p></div>
      </div>
      <div class="p-6">

        <!-- 비로그인 -->
        <div v-if="!authUser" class="text-center py-10">
          <div class="text-5xl mb-3">🔒</div>
          <p class="text-slate-600 dark:text-slate-300 font-semibold mb-1">로그인이 필요합니다</p>
          <p class="text-sm text-slate-400 mb-5">로그인 후 나만의 성향 카드를 만들어보세요!</p>
          <Link href="/login" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-500 text-white font-bold px-5 py-2.5 rounded-xl text-sm transition-all">
            로그인하기 →
          </Link>
        </div>

        <!-- 테스트 미완료 -->
        <div v-else-if="!authUser.test_completed" class="text-center py-10">
          <div class="text-5xl mb-3">📝</div>
          <p class="text-slate-600 dark:text-slate-300 font-semibold mb-1">성향 테스트를 완료해주세요</p>
          <p class="text-sm text-slate-400 mb-5">테스트를 통해 나의 정치 성향을 먼저 확인해보세요.</p>
          <Link href="/political-test" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-500 text-white font-bold px-5 py-2.5 rounded-xl text-sm transition-all">
            성향 테스트 하러 가기 →
          </Link>
        </div>

        <!-- 카드 미리보기 + 다운로드 -->
        <div v-else>
          <div class="rounded-2xl overflow-hidden mb-5 relative" style="background: linear-gradient(135deg, #0f172a, #1e293b)">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 rounded-l-2xl" :style="{ backgroundColor: previewFc.bg }"></div>
            <div class="p-5 pl-6">
              <div class="flex items-start justify-between mb-3">
                <div>
                  <p class="text-xs text-slate-400 mb-1">나의 정치 성향</p>
                  <p class="text-3xl font-black text-white">{{ authUser.faction_label }}</p>
                  <p class="text-sm text-slate-400">@{{ authUser.nickname }}</p>
                </div>
                <div class="text-4xl">{{ previewFc.emoji }}</div>
              </div>
              <div class="mt-4 mb-1">
                <div class="relative h-3 rounded-full overflow-hidden" style="background: linear-gradient(to right, #378ADD, #7F77DD, #E24B4A)">
                  <div class="absolute top-1/2 -translate-y-1/2 w-4 h-4 bg-white rounded-full border-2 shadow transition-all"
                    :style="{ left: 'calc('+previewFc.bar*100+'% - 8px)', borderColor: previewFc.bg }"></div>
                </div>
                <div class="flex justify-between text-xs text-slate-500 mt-1"><span>진보</span><span>보수</span></div>
              </div>
              <div class="flex items-center justify-between mt-3">
                <span class="text-xs text-slate-500">매너 점수 {{ authUser.manner_score }}점</span>
                <span class="text-xs text-slate-600 font-bold tracking-widest">POLIT.KR</span>
              </div>
            </div>
          </div>
          <p class="text-xs text-slate-400 text-center mb-3">PNG 이미지로 저장해 SNS에 공유하세요 📤</p>
          <button @click="downloadShareCard"
            class="w-full py-3.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 text-white font-black rounded-xl shadow-lg hover:-translate-y-0.5 transition-all text-base flex items-center justify-center gap-2">
            📥 PNG로 저장하기
          </button>
        </div>
      </div>
    </div>
  </div><!-- /ROW 3 -->

  <!-- 놀이터 바로가기 -->
  <div class="bg-gradient-to-r from-violet-50 via-purple-50/60 to-violet-50 dark:from-slate-900 dark:via-slate-800/60 dark:to-slate-900 border border-violet-200 dark:border-slate-700 rounded-2xl p-8 text-center">
    <p class="text-3xl mb-3">🎡</p>
    <h2 class="text-xl font-black text-slate-900 dark:text-white mb-2">정치 말고 딴 얘기도 하고 싶다면?</h2>
    <p class="text-slate-500 dark:text-slate-400 text-sm mb-5">게임·스포츠·연예·주식 등 다양한 주제의 놀이터 게시판으로</p>
    <Link href="/boards" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-500 text-white font-bold px-6 py-3 rounded-xl text-sm transition-all shadow-lg hover:-translate-y-0.5">
      놀이터 구경하기 →
    </Link>
  </div>

</div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
@keyframes spin-slow { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.animate-spin-slow { animation: spin-slow 2s linear infinite; }
</style>
