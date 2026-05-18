# 폴릿(Polit) — 단계별 개발 전략 v2.0

> **업데이트**: 관리자 기능 / 생성형 게시판 / 평균 기반 진영 점수 / 이력 통계 / 실시간 점수 헤더 반영

---

## 목차

1. [핵심 설계 변경 사항](#1-핵심-설계-변경-사항)
2. [진영 점수 집계 설계 (평균화)](#2-진영-점수-집계-설계-평균화)
3. [Phase 1 — 인프라 및 인증](#3-phase-1--인프라-및-인증)
4. [Phase 2 — 커뮤니티 핵심 + 생성형 게시판](#4-phase-2--커뮤니티-핵심--생성형-게시판)
5. [Phase 3 — 분석 · 실시간 · UI/UX](#5-phase-3--분석--실시간--uiux)
6. [데이터베이스 스키마 (전체)](#6-데이터베이스-스키마-전체)
7. [관리자 기능 총괄](#7-관리자-기능-총괄)

---

## 1. 핵심 설계 변경 사항

| 항목 | 기존 | 변경 후 |
|---|---|---|
| 진영 점수 산식 | 원시 합계 (post + vote) | **진영 사용자 수로 나눈 평균** |
| 점수 이력 | 없음 | 일별 / 월별 / 연도별 집계 테이블 |
| 점수 노출 위치 | 대시보드 페이지 한정 | **모든 페이지 상단 헤더 실시간 표시** |
| 게시판 구조 | 하드코딩 | **관리자가 동적으로 생성하는 생성형 게시판** |
| 관리자 기능 | 미정의 | Phase별 전담 기능 세트 정의 |

---

## 2. 진영 점수 집계 설계 (평균화)

### 2-1. 문제 정의

사용자 수가 특정 진영에 치우쳐 있으면(예: 진보 70% / 중도 20% / 보수 10%) 단순 합계 기반 점수는 사용자가 많은 진영이 구조적으로 유리해져 점수 자체가 무의미해집니다.

### 2-2. 평균 기반 점수 산식

```
진영 영향력 지수(Faction Score) =
  ( post_count × 3
  + comment_count × 1
  + vote_up_count × 2
  - vote_down_count × 0.5
  - report_count × 5
  ) ÷ active_user_count
```

- **active_user_count**: 해당 날짜에 1회 이상 활동(로그인 or 게시 or 댓글)한 진영 내 사용자 수
- 가중치는 관리자 패널에서 조정 가능하도록 `score_weights` 테이블에 저장
- 소수점 2자리까지 유지 (NUMERIC(10,2))

### 2-3. 이력 데이터 계층 구조

```
실시간(Redis)
   └── 매 1분마다 캐시 갱신
         │
         ▼
factions_hourly_cache      ← 최근 24시간 (1시간 단위, Redis 또는 DB)
         │
         ▼ [Daily Scheduler - 자정]
factions_daily_stats       ← 365일 보관
         │
         ▼ [Monthly Aggregator - 매월 1일]
factions_monthly_stats     ← 5년 보관
         │
         ▼ [Yearly Aggregator - 매년 1월 1일]
factions_yearly_stats      ← 영구 보관
```

### 2-4. 실시간 헤더 노출 흐름

```
사용자 행위 발생
   └─ Event 발행 (PostCreated / VoteCreated / ...)
         └─ FactionScoreUpdated Listener
               └─ Redis HINCRBY (atomic)
                     └─ Laravel Reverb → Broadcast
                           └─ 모든 페이지 헤더 WebSocket 수신 → 점수 업데이트
```

---

## 3. Phase 1 — 인프라 및 인증

**목표**: 프로젝트 뼈대와 사용자/관리자 인증, 진영 배정 완성

---

### 3-1. 인프라 구성

| 작업 | 세부 내용 |
|---|---|
| Laravel Sail + Docker 세팅 | PHP 8.2, PostgreSQL 16, Redis, Nginx, Reverb 컨테이너 구성 |
| 환경 변수 관리 | `.env` 분리 (dev / staging / prod) |
| CI 기본 파이프라인 | GitHub Actions: Lint(PSR-12) → PHPUnit → migrate:fresh |

### 3-2. 회원가입 및 성향 진단

```
[회원가입] → [이메일 인증] → [성향 테스트 10문항] → [점수 산출] → [진영 할당] → [아지트 입장]
```

- `political_tests` 테이블: JSONB `options` (보기 텍스트 + 점수 가중치)
- 점수 범위: 0~100 → 0~33 보수 / 34~66 중도 / 67~100 진보
- 결과 저장: `users.political_type`, `users.test_score`
- 재테스트: 90일 후 1회 허용 (이전 테스트 결과 이력 보관)

### 3-3. 진영 접근 제한 미들웨어

```php
// app/Http/Middleware/FactionGate.php
// 아지트 접근: auth()->user()->political_type === $route->parameter('faction')
// 위반 시 → 403 + 본인 아지트로 리다이렉트
```

---

### [관리자] Phase 1 관리자 기능

#### A. 관리자 인증 및 권한 체계

```
SuperAdmin
  ├── ContentAdmin   (게시물/댓글 관리)
  ├── UserAdmin      (사용자 관리)
  └── StatsAdmin     (통계 조회 전용)
```

- Laravel Spatie Permission 패키지 활용
- 관리자 계정은 `users.role` ENUM 또는 별도 `admins` 테이블로 분리
- 2FA(TOTP) 필수 적용

#### B. 관리자 대시보드 기본 구조

| 메뉴 | 기능 |
|---|---|
| 홈 | 가입자 현황, 진영별 비율 파이차트, 오늘의 주요 지표 |
| 사용자 관리 | 목록/검색/필터, 진영 강제 변경, 계정 정지/해제, 탈퇴 처리 |
| 성향 테스트 관리 | 문항 CRUD, 가중치 조정, 미리보기 |
| 시스템 설정 | 점수 가중치 테이블 편집, 유지보수 모드 ON/OFF |

---

## 4. Phase 2 — 커뮤니티 핵심 + 생성형 게시판

**목표**: 아지트 · 전쟁터 핵심 기능 + 동적 게시판 생성 시스템 + 점수 집계 엔진 완성

---

### 4-1. 생성형 게시판 (Dynamic Board System)

관리자가 코드 변경 없이 게시판을 설계하고 배포하는 시스템입니다.

#### 게시판 속성 설계

```php
// boards 테이블
id
name                  // 게시판 이름 (예: "정책 토론방", "자유수다")
slug                  // URL slug (예: policy-debate)
space                 // ENUM: azit | battle | both
allowed_factions      // JSONB: ["conservative"] | ["all"]
board_type            // ENUM: general | debate | qna | poll | notice
config                // JSONB: 커스텀 설정 (아래 참고)
ai_description        // AI가 생성한 게시판 소개문
sort_order            // 노출 순서
is_active             // 활성화 여부
created_by            // 관리자 ID
```

**`config` JSONB 예시:**
```json
{
  "allow_anonymous": false,
  "require_min_score": 10,
  "allow_attachment": true,
  "max_daily_posts_per_user": 5,
  "show_faction_badge": true,
  "pinned_poll_id": null,
  "ai_topic_suggestion": true,
  "debate_format": "oxford"
}
```

#### 게시판 타입별 동작

| 타입 | 설명 |
|---|---|
| `general` | 일반 자유 게시판 |
| `debate` | 찬성/반대 포지션 선택 후 작성. 전쟁터 전용 |
| `qna` | 질문/답변 구조, 채택 기능 포함 |
| `poll` | 실시간 투표 + 진영별 결과 시각화 |
| `notice` | 관리자 전용 공지. 읽기 전용 |

#### AI 게시판 생성 어시스턴트 (관리자 패널 내)

1. 관리자가 목적/대상 진영/주제를 자연어로 입력
2. Claude API 호출 → 게시판 이름, 소개문, 추천 config 초안 생성
3. 관리자 검토 후 확정 → DB 저장 → 즉시 반영

```php
// app/Services/BoardGeneratorService.php
public function generate(string $prompt): BoardDraftDTO
{
    // Anthropic API 호출
    // 반환: name, slug, ai_description, suggested_config
}
```

---

### 4-2. 게시물 · 댓글 CRUD API

#### posts 테이블 확장

```sql
board_id        BIGINT REFERENCES boards(id)   -- 게시판 연결
faction         faction_type NOT NULL            -- 작성 당시 진영 스냅샷
content_type    ENUM('text','rich','poll')
status          ENUM('published','hidden','deleted','pending_review')
view_count      INT DEFAULT 0
score_weight    DECIMAL(5,2) DEFAULT 1.0         -- 관리자 가중치 보정
```

#### 추천/비추천 (Polymorphic)

```php
// votes 테이블: votable_type, votable_id → posts, comments 모두 커버
// 1유저 1표 보장, 진영 교차 투표 허용 (전쟁터 한정)
```

---

### 4-3. 진영 점수 집계 엔진

#### Laravel Task Scheduling

```php
// routes/console.php
Schedule::command('faction:score-snapshot')
    ->everyMinute()           // Redis 실시간 캐시 갱신

Schedule::command('faction:daily-aggregate')
    ->dailyAt('00:05')        // 일별 집계 → factions_daily_stats

Schedule::command('faction:monthly-aggregate')
    ->monthlyOn(1, '00:10')   // 월별 집계 → factions_monthly_stats

Schedule::command('faction:yearly-aggregate')
    ->yearlyOn(1, 1, '00:15') // 연도별 집계 → factions_yearly_stats
```

#### 집계 명령어 핵심 로직

```php
// app/Console/Commands/FactionDailyAggregate.php
$score = (
    $postCount * $weights['post']
    + $commentCount * $weights['comment']
    + $voteUpCount * $weights['vote_up']
    - $voteDownCount * $weights['vote_down']
    - $reportCount * $weights['report']
) / max($activeUserCount, 1);  // 0 나눔 방지

FactionDailyStat::upsert([
    'faction_type'     => $faction,
    'date'             => today(),
    'post_count'       => $postCount,
    'comment_count'    => $commentCount,
    'vote_up_count'    => $voteUpCount,
    'vote_down_count'  => $voteDownCount,
    'report_count'     => $reportCount,
    'active_user_count'=> $activeUserCount,
    'total_user_count' => $totalUserCount,
    'raw_score'        => $rawScore,
    'avg_score'        => $score,      // ← 핵심: 평균화된 점수
], ['faction_type', 'date']);
```

---

### [관리자] Phase 2 관리자 기능

#### A. 게시판 관리 (생성형 게시판 어드민)

| 기능 | 설명 |
|---|---|
| 게시판 CRUD | 신규 생성, 수정, 비활성화(삭제 대신 soft) |
| AI 생성 어시스턴트 | 자연어 입력 → 게시판 설정 초안 자동 생성 |
| 노출 순서 드래그 앤 드롭 | sort_order 실시간 조정 |
| 게시판 복제 | 기존 설정을 복사해 신규 게시판으로 분기 |
| 접근 진영 제한 변경 | 운영 중에도 실시간 변경 가능 |

#### B. 게시물 · 댓글 관리

| 기능 | 설명 |
|---|---|
| 신고 처리 큐 | 신고된 게시물 목록, 내용 확인, 승인/블라인드/삭제 처리 |
| 블라인드 처리 | status → `hidden`, 작성자에게 사유 이메일 발송 |
| 고정 공지 설정 | 전쟁터/아지트 상단 고정 게시물 지정 |
| 대량 작업 | 체크박스 선택 후 일괄 블라인드 / 삭제 |
| 욕설 필터 관리 | AI 필터 임계값 조정, 화이트리스트/블랙리스트 편집 |

#### C. 점수 가중치 관리

```
[관리자 패널 → 점수 설정]
게시물 작성   ×  [3.0 ▼] 포인트
댓글 작성     ×  [1.0 ▼] 포인트
추천          ×  [2.0 ▼] 포인트
비추천        ×  [-0.5▼] 포인트
신고 누적     ×  [-5.0▼] 포인트
                        [저장] → score_weights 테이블 업데이트
                                → Redis 캐시 무효화
```

---

## 5. Phase 3 — 분석 · 실시간 · UI/UX

**목표**: 실시간 점수 헤더 + 이력 통계 대시보드 + 실시간 알림 완성

---

### 5-1. 실시간 점수 헤더 (모든 페이지 공통)

#### 헤더 컴포넌트 설계

```
┌──────────────────────────────────────────────────────────────────┐
│  🏛 폴릿(Polit)          [실시간 진영 점수]          [내 아지트] │
│                                                                    │
│   🔵 보수  42.7점    🟡 중도  38.1점    🔴 진보  51.3점          │
│   ▲+2.1 (1분전)      ━ 변동없음          ▼-0.8 (3분전)          │
└──────────────────────────────────────────────────────────────────┘
```

#### 구현 방식

```php
// Laravel Reverb WebSocket Channel
// Channel: faction-scores (public)
// Event: FactionScoreUpdated {
//   conservative: 42.7, neutral: 38.1, progressive: 51.3,
//   deltas: { conservative: +2.1, ... }, updated_at: timestamp
// }
```

```javascript
// resources/js/components/FactionScoreHeader.vue
Echo.channel('faction-scores')
    .listen('FactionScoreUpdated', (e) => {
        this.scores = e.scores;
        this.deltas = e.deltas;
        this.flashUpdate();  // 점수 변경 시 숫자 깜빡임 애니메이션
    });
```

- **Fallback**: WebSocket 연결 실패 시 30초 polling으로 자동 전환
- **Redis Key 구조**: `faction:score:realtime` → HASH {conservative, neutral, progressive}
- 서버 부하 방지: 1분에 최대 1회 broadcast (throttle)

---

### 5-2. 이력 통계 대시보드

#### 조회 인터페이스

```
[일별 보기 ▼]  [2026년 5월 ▼]  [전체 진영 ▼]  [조회]

──────────────────────────────────────────────
 날짜      보수(평균)  중도(평균)  진보(평균)  1위
──────────────────────────────────────────────
 05/12     42.7       38.1        51.3      진보 🏆
 05/11     39.2       40.5        48.9      진보
 05/10     45.1       37.8        43.2      보수
 ...
──────────────────────────────────────────────

[Line Chart: 추이 그래프 - Chart.js]
```

#### API 엔드포인트

```
GET /api/stats/faction-scores?period=daily&year=2026&month=5
GET /api/stats/faction-scores?period=monthly&year=2026
GET /api/stats/faction-scores?period=yearly
```

```php
// 월별 집계 쿼리 예시 (PostgreSQL date_trunc 활용)
SELECT
    faction_type,
    DATE_TRUNC('month', date) AS month,
    ROUND(AVG(avg_score), 2)  AS avg_monthly_score,
    SUM(post_count)           AS total_posts,
    SUM(active_user_count)    AS total_active_users
FROM factions_daily_stats
WHERE date BETWEEN :start AND :end
GROUP BY faction_type, month
ORDER BY month ASC, avg_monthly_score DESC;
```

---

### 5-3. 실시간 투표 (The Poll)

```
┌─────────────────── 오늘의 쟁점 ───────────────────┐
│  "AI 기본소득 도입, 찬성하십니까?"                 │
│                                                     │
│  🔵 보수: ████████░░░░  62%  (124표)               │
│  🟡 중도: ██████░░░░░░  48%  (96표)                │
│  🔴 진보: ██░░░░░░░░░░  21%  (42표)                │
│                                     [투표하기]      │
└─────────────────────────────────────────────────────┘
```

- 투표 결과도 실시간 WebSocket으로 헤더와 동일 채널 병행 처리
- 진영별 투표 결과만 공개 (개인 투표는 익명)

---

### 5-4. 진영 랭킹 시스템

```
칭호 부여 기준 (주간 리셋)
├── 진영 대변인   : 해당 진영 내 avg_score 1위
├── 행동대장      : 주간 게시물 수 1위
├── 설득의 달인   : 상대 진영으로부터 추천을 가장 많이 받은 유저
└── 평화주의자    : 신고 0 + 댓글 50개 이상
```

---

### [관리자] Phase 3 관리자 기능

#### A. 진영 점수 이력 관리 대시보드

| 기능 | 설명 |
|---|---|
| 일별/월별/연도별 조회 | 관리자 전용 상세 뷰 (사용자 수 원시 데이터 포함) |
| 이상치 탐지 | 특정 날짜 avg_score가 전일 대비 ±50% 초과 시 경보 표시 |
| 수동 보정 | 봇 활동 등 이상 데이터 발견 시 관리자가 해당 날짜 점수 수동 수정 |
| CSV/Excel 내보내기 | 여론조사 리포트 생성용 원시 데이터 다운로드 |

#### B. 실시간 모니터링 패널

```
[실시간 관리자 모니터]
├── 현재 접속자 수 (진영별)
├── 분당 게시물 생성 수
├── 분당 신고 발생 수 (임계값 초과 시 알람)
└── WebSocket 연결 상태 / Reverb 서버 health
```

#### C. 사용자 성향 밸런스 모니터

```
진영별 사용자 비율 현황
  🔵 보수  ████████  32%  (1,280명)
  🟡 중도  ████░░░░  18%  (720명)
  🔴 진보  ██████████ 50%  (2,000명)

  ⚠️ 경고: 진보 비율이 50%를 초과했습니다.
  → 추천 조치: 중도/보수 신규 가입 이벤트 기획 필요
```

- 특정 진영 비율이 50% 초과 시 관리자 대시보드 경보 발송 (이메일 + 패널 알림)

---

## 6. 데이터베이스 스키마 (전체)

```sql
-- 진영 ENUM
CREATE TYPE faction_type AS ENUM ('conservative', 'neutral', 'progressive');

-- 사용자
CREATE TABLE users (
    id                BIGSERIAL PRIMARY KEY,
    email             VARCHAR(255) UNIQUE NOT NULL,
    password          VARCHAR(255) NOT NULL,
    political_type    faction_type,
    test_score        SMALLINT CHECK (test_score BETWEEN 0 AND 100),
    last_tested_at    TIMESTAMPTZ,
    role              ENUM('user','content_admin','user_admin','stats_admin','super_admin') DEFAULT 'user',
    status            ENUM('active','suspended','withdrawn') DEFAULT 'active',
    manner_score      SMALLINT DEFAULT 100,
    created_at        TIMESTAMPTZ DEFAULT NOW(),
    updated_at        TIMESTAMPTZ DEFAULT NOW()
);

-- 성향 테스트 문항
CREATE TABLE political_tests (
    id         BIGSERIAL PRIMARY KEY,
    question   TEXT NOT NULL,
    options    JSONB NOT NULL,  -- [{text, score_delta, order}]
    weight     DECIMAL(4,2) DEFAULT 1.0,
    sort_order SMALLINT DEFAULT 0,
    is_active  BOOLEAN DEFAULT TRUE
);

-- 게시판 (생성형)
CREATE TABLE boards (
    id               BIGSERIAL PRIMARY KEY,
    name             VARCHAR(100) NOT NULL,
    slug             VARCHAR(100) UNIQUE NOT NULL,
    space            ENUM('azit','battle','both') NOT NULL,
    allowed_factions JSONB DEFAULT '["all"]',
    board_type       ENUM('general','debate','qna','poll','notice') DEFAULT 'general',
    config           JSONB DEFAULT '{}',
    ai_description   TEXT,
    sort_order       SMALLINT DEFAULT 0,
    is_active        BOOLEAN DEFAULT TRUE,
    created_by       BIGINT REFERENCES users(id),
    created_at       TIMESTAMPTZ DEFAULT NOW(),
    updated_at       TIMESTAMPTZ DEFAULT NOW()
);

-- 게시물
CREATE TABLE posts (
    id           BIGSERIAL PRIMARY KEY,
    user_id      BIGINT REFERENCES users(id),
    board_id     BIGINT REFERENCES boards(id),
    faction      faction_type NOT NULL,
    category     ENUM('azit','battle') NOT NULL,
    title        VARCHAR(300) NOT NULL,
    content      TEXT NOT NULL,
    content_type ENUM('text','rich','poll') DEFAULT 'text',
    status       ENUM('published','hidden','deleted','pending_review') DEFAULT 'published',
    view_count   INT DEFAULT 0,
    score_weight DECIMAL(5,2) DEFAULT 1.0,
    created_at   TIMESTAMPTZ DEFAULT NOW(),
    updated_at   TIMESTAMPTZ DEFAULT NOW()
);

-- 댓글
CREATE TABLE comments (
    id         BIGSERIAL PRIMARY KEY,
    post_id    BIGINT REFERENCES posts(id),
    user_id    BIGINT REFERENCES users(id),
    parent_id  BIGINT REFERENCES comments(id),
    faction    faction_type NOT NULL,
    content    TEXT NOT NULL,
    status     ENUM('published','hidden','deleted') DEFAULT 'published',
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 추천/비추천 (Polymorphic)
CREATE TABLE votes (
    id            BIGSERIAL PRIMARY KEY,
    votable_type  VARCHAR(50) NOT NULL,  -- 'post' | 'comment'
    votable_id    BIGINT NOT NULL,
    user_id       BIGINT REFERENCES users(id),
    vote_type     ENUM('up','down') NOT NULL,
    created_at    TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(votable_type, votable_id, user_id)
);

-- 점수 가중치 (관리자 조정)
CREATE TABLE score_weights (
    id          BIGSERIAL PRIMARY KEY,
    key         VARCHAR(50) UNIQUE NOT NULL,  -- post, comment, vote_up, ...
    value       DECIMAL(5,2) NOT NULL,
    updated_by  BIGINT REFERENCES users(id),
    updated_at  TIMESTAMPTZ DEFAULT NOW()
);

-- 진영 점수 (일별) ← 핵심
CREATE TABLE factions_daily_stats (
    id                BIGSERIAL PRIMARY KEY,
    faction_type      faction_type NOT NULL,
    date              DATE NOT NULL,
    post_count        INT DEFAULT 0,
    comment_count     INT DEFAULT 0,
    vote_up_count     INT DEFAULT 0,
    vote_down_count   INT DEFAULT 0,
    report_count      INT DEFAULT 0,
    active_user_count INT DEFAULT 0,   -- 당일 활동 사용자 수
    total_user_count  INT DEFAULT 0,   -- 전체 진영 사용자 수
    raw_score         NUMERIC(10,2),   -- 합계 점수 (참고용)
    avg_score         NUMERIC(10,2),   -- 평균 점수 (공식 지표)
    UNIQUE(faction_type, date)
);

-- 진영 점수 (월별)
CREATE TABLE factions_monthly_stats (
    id                BIGSERIAL PRIMARY KEY,
    faction_type      faction_type NOT NULL,
    year_month        DATE NOT NULL,   -- 매월 1일로 저장
    avg_score         NUMERIC(10,2),
    total_posts       INT,
    total_active_days INT,
    UNIQUE(faction_type, year_month)
);

-- 진영 점수 (연도별)
CREATE TABLE factions_yearly_stats (
    id           BIGSERIAL PRIMARY KEY,
    faction_type faction_type NOT NULL,
    year         SMALLINT NOT NULL,
    avg_score    NUMERIC(10,2),
    total_posts  INT,
    UNIQUE(faction_type, year)
);

-- 실시간 투표
CREATE TABLE polls (
    id          BIGSERIAL PRIMARY KEY,
    board_id    BIGINT REFERENCES boards(id),
    question    VARCHAR(500) NOT NULL,
    options     JSONB NOT NULL,         -- [{id, text}]
    is_active   BOOLEAN DEFAULT TRUE,
    expires_at  TIMESTAMPTZ,
    created_by  BIGINT REFERENCES users(id),
    created_at  TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE poll_votes (
    id           BIGSERIAL PRIMARY KEY,
    poll_id      BIGINT REFERENCES polls(id),
    user_id      BIGINT REFERENCES users(id),
    option_id    INT NOT NULL,
    faction_type faction_type NOT NULL,
    created_at   TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(poll_id, user_id)
);

-- 인덱스
CREATE INDEX idx_posts_board_status ON posts(board_id, status);
CREATE INDEX idx_posts_faction_created ON posts(faction, created_at DESC);
CREATE INDEX idx_factions_daily_date ON factions_daily_stats(date DESC, faction_type);
CREATE INDEX idx_political_tests_options ON political_tests USING GIN(options);
CREATE INDEX idx_boards_config ON boards USING GIN(config);
CREATE INDEX idx_votes_votable ON votes(votable_type, votable_id);
```

---

## 7. 관리자 기능 총괄

| Phase | 관리자 기능 | 우선순위 |
|---|---|---|
| 1 | 관리자 인증 (2FA) + RBAC 권한 체계 | 🔴 필수 |
| 1 | 관리자 대시보드 홈 (KPI 요약) | 🔴 필수 |
| 1 | 사용자 관리 (목록, 정지, 진영 변경) | 🔴 필수 |
| 1 | 성향 테스트 문항 CRUD | 🔴 필수 |
| 2 | **생성형 게시판 CRUD + AI 어시스턴트** | 🔴 필수 |
| 2 | 게시물/댓글 신고 처리 큐 | 🔴 필수 |
| 2 | 점수 가중치 편집 패널 | 🟡 중요 |
| 2 | 욕설 필터 임계값 · 블랙리스트 편집 | 🟡 중요 |
| 3 | 진영 점수 이력 대시보드 (일/월/연) | 🔴 필수 |
| 3 | 진영 밸런스 경보 + 모니터 | 🟡 중요 |
| 3 | 실시간 접속 모니터링 패널 | 🟢 선택 |
| 3 | 점수 이상치 탐지 + 수동 보정 | 🟡 중요 |
| 3 | 통계 CSV/Excel 내보내기 | 🟡 중요 |
| 3 | 칭호 기준 편집 | 🟢 선택 |

---

*최종 수정: 2026-05-12 | 다음 리뷰: Phase 1 완료 후*
