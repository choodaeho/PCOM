# 폴릿(Polit) 프로젝트 인수인계 문서

> **작성일**: 2026-07-08  
> **목적**: 새로운 Claude 세션에서 프로젝트를 즉시 이어받기 위한 완전한 핸드오프 문서  
> **기준 디렉토리**: `D:\2026\pcom\` (Docker 기반, `source/` 하위에 Laravel 프로젝트)

---

## 1. 프로젝트 개요

**폴릿(Polit)** = Politics의 약자. 사용자의 정치적 성향(보수·중도·진보)을 진단하고,  
진영 전용 공간(아지트)·전 진영 토론장(전쟁터)·자유 게시판(놀이터)을 제공하는 한국 정치 커뮤니티 플랫폼.

### 기술 스택

| 항목 | 버전 / 도구 |
|------|------------|
| Language | PHP 8.2+ |
| Framework | Laravel 11.x |
| Frontend | Vue 3 + Inertia.js (SSR) + Tailwind CSS |
| Database | PostgreSQL 16 |
| Web Server | Nginx |
| Infrastructure | Docker (Laravel Sail) |
| 에디터 | Quill 2.x (리치 텍스트) |
| 상태관리 | Pinia |
| AI | Google Gemini API (gemini-2.5-flash) |
| 이미지 | Pixabay API (폴백: Lorem Picsum) |

코드 스타일: **PSR-12**, `declare(strict_types=1)` 필수, Type Hinting 필수.

---

## 2. 핵심 비즈니스 로직

### 성향 진단
- 가입 시 필수 설문(10문항) → `test_score` 산출 → `FactionType` 자동 배정
- 점수 기준: **+25 이상 → 보수** / **-25 이하 → 진보** / **중간 → 중도**

### 게시판 접근 제어
| 게시판 | 유형 | 접근 제한 |
|--------|------|----------|
| 아지트(Azit) | 진영 전용 | 본인 진영만 열람·작성. `EnsureFactionAccess` 미들웨어 |
| 전쟁터(Battle) | 전진영 토론 | 모두 참여. 게시글에 작성자 진영 아이콘 표시 |
| 놀이터(Playground) | 자유 게시판 | 진영 제한 없음 |
| 공지사항(Notice) | 운영 공지 | 관리자만 작성 |

### 진영 점수 (Daily Stats)
매일 각 진영의 게시물 수·추천 수·활동 지표를 집계 → `factions_daily_stats` 테이블 → 대시보드 시각화

---

## 3. Enum 정의

### `app/Enums/FactionType.php`
```
conservative → 보수 (🔴 #E24B4A, 빨강)   ← 국민의힘 계열
moderate     → 중도 (🟣 #7F77DD, 보라)
progressive  → 진보 (🔵 #378ADD, 파랑)   ← 민주당 계열
```
⚠️ **한국 정치 관례**: 보수=빨강, 진보=파랑 (서구와 반대)  
메서드: `fromScore(int $score)` / `label()` / `color()` / `emoji()` / `values()`

### `app/Enums/BoardType.php`
```
azit | battle | playground | notice
```
메서드: `isFactionRestricted()` (azit만 true) / `isPlayground()` (playground만 true)

### `app/Enums/UserType.php`
```
admin | test | normal
```
- `test`: 개발용 더미 계정 → 운영 통계 쿼리에서 `WHERE user_type != 'test'`로 필터링

### 기타 Enum
- `UserStatus`: `pending | active | suspended | banned`
- `PostStatus`: `published | hidden | deleted_by_admin`
- `VoteType`: `up | down`
- `ReportReason`: 신고 사유

---

## 4. 데이터베이스 스키마

### users 테이블
| 컬럼 | 타입 | 비고 |
|------|------|------|
| id | bigint PK | |
| email | varchar(191) UNIQUE | |
| password | varchar(255) nullable | 소셜 전용은 null |
| nickname | varchar(50) UNIQUE | ⚠️ Vue에서 `user.name` 아닌 `user.nickname` 사용 |
| avatar_url | varchar(500) nullable | |
| political_type | varchar(20) | conservative/moderate/progressive |
| test_score | smallint | -100 ~ +100 |
| test_completed_at | timestamp nullable | |
| status | varchar(20) | pending/active/suspended/banned |
| email_verified_at | timestamp nullable | |
| manner_score | smallint default 100 | 신고 누적 시 감점 |
| experience_points | bigint default 0 | ✅ bigint 마이그레이션 완료 (레벨 시스템용) |
| title | varchar(50) nullable | 칭호 (진영 대변인 등) |
| is_admin | boolean default false | |
| admin_role | varchar(30) nullable | super_admin/content_admin/user_admin/stats_admin |
| user_type | varchar(20) default 'normal' | admin/test/normal |
| suspended_until | timestamp nullable | |
| deleted_at | timestamp nullable | soft delete |

### posts 테이블
| 컬럼 | 타입 | 비고 |
|------|------|------|
| id | bigint PK | |
| user_id | bigint FK → users | |
| board_id | bigint FK → boards | |
| faction | varchar(20) | 작성 당시 진영 스냅샷 |
| title | varchar(300) | |
| content | text | Quill HTML |
| category | varchar(50) nullable | ✅ 카테고리(말머리) 추가됨 |
| attachments | JSONB nullable | 첨부 파일 메타 배열 |
| status | varchar(20) default 'published' | |
| is_notice | boolean default false | |
| is_anonymous | boolean default false | |
| is_hot | boolean default false | ✅ 인기글 자동 등재용 플래그 |
| view_count / comment_count / vote_up_count / vote_down_count / report_count | integer | 비정규화 카운터 |
| search_vector | tsvector | FTS (GIN 인덱스, 트리거 자동 갱신) |
| deleted_at | timestamp nullable | soft delete |

### boards 테이블
```
id, slug, name, description, board_type, allowed_faction,
sort_order, is_active, hot_threshold, hot_label, categories (JSONB)
```
- `categories`: 게시판별 말머리 목록 (예: `["정치", "경제", "사회"]`)
- `hot_threshold`: 추천 수 기준 인기글 등재 임계값
- `hot_label`: 인기글 표시 텍스트 (예: "🔥 HOT")

### comments 테이블
```
id, post_id, user_id, parent_id, reply_to_id (✅ 재답글용), content, deleted_at
```
- `reply_to_id`: DC/펨코 스타일 재답글 — `parent_id`와 별도로 "누구에게 답글"을 표시

### factions_daily_stats 테이블
```
id, faction_type, stat_date (⚠️ 'date' 아닌 'stat_date'), post_count, vote_count, total_score
```
- **월별 집계**: `stat_date`가 해당 월의 1일로 저장
- **연별 집계**: `stat_date`가 해당 연도의 1월 1일로 저장

### AI 자동 콘텐츠 관련 테이블
```sql
auto_content_configs   -- 싱글턴 설정 (id=1 고정)
auto_content_runs      -- 실행 기록 (is_stopped 컬럼 포함)
auto_content_run_entries -- 개별 Job 결과 로그
```

### 기타 테이블
```
votes (polymorphic), reports, polls, social_accounts,
political_test_sessions, score_weights
```

---

## 5. 시드(Seed) 데이터

### 실행 순서
```bash
docker compose exec app php artisan db:seed
# 또는 특정 시더만
docker compose exec app php artisan db:seed --class=TestAccountsSeeder
```

1. `ScoreWeightsSeeder` — 진영 점수 가중치 기본값
2. `PoliticalTestsSeeder` — 성향 테스트 기본 10문항
3. `MorePoliticalTestsSeeder` — ✅ 추가 30문항 (총 40문항 → 랜덤 10개 출제)
4. `BoardsSeeder` — 기본 게시판 15개
5. `AdminUserSeeder` — 슈퍼관리자 계정
6. `TestAccountsSeeder` — 테스트 계정 90개

### 게시판 구성 (15개)
| 유형 | 게시판명 | slug |
|------|---------|------|
| 아지트 | 보수 아지트 | conservative-azit |
| 아지트 | 중도 아지트 | moderate-azit |
| 아지트 | 진보 아지트 | progressive-azit |
| 전쟁터 | 정치 전쟁터 | battle-politics |
| 전쟁터 | 경제 전쟁터 | battle-economy |
| 전쟁터 | 사회 전쟁터 | battle-society |
| 놀이터 | 유머/짤방 | play-humor |
| 놀이터 | 게임 | play-game |
| 놀이터 | 스포츠 | play-sports |
| 놀이터 | 방송/연예 | play-entertainment |
| 놀이터 | 주식/코인 | play-stock |
| 놀이터 | IT/테크 | play-it |
| 놀이터 | 먹방/맛집 | play-food |
| 놀이터 | 자유게시판 | play-free |
| 공지사항 | 공지사항 | notice |

### 관리자 계정
| 항목 | 값 |
|------|---|
| nickname | 대한민국 |
| email | `env('ADMIN_EMAIL', 'hhpapa77@polit.kr')` |
| password | `env('ADMIN_PASSWORD', 'itweb8335#')` |
| user_type | admin / is_admin: true / admin_role: super_admin |

⚠️ 운영 배포 전 반드시 `.env`로 ADMIN_PASSWORD 변경

### 테스트 계정 (90개)
| 진영 | 이메일 패턴 | 비밀번호 |
|------|------------|---------|
| 보수 | conservative01~30@test.polit.kr | fusion!@34 |
| 중도 | moderate01~30@test.polit.kr | fusion!@34 |
| 진보 | progressive01~30@test.polit.kr | fusion!@34 |

---

## 6. 미들웨어

| alias | 클래스 | 역할 |
|-------|--------|------|
| `faction.access` | `EnsureFactionAccess` | 아지트 진영 접근 제어 (관리자 우회) |
| `admin.auth` | `EnsureAdminAuthenticated` | 관리자 패널: 로그인 + is_admin + 2FA 세션 |
| `admin.user` | `EnsureUserIsAdmin` | 일반 라우트 관리자 체크 |
| `political.test` | `EnsurePoliticalTestCompleted` | 성향 테스트 완료 여부 |
| `user.active` | `EnsureUserIsActive` | 계정 status=active 확인 |

---

## 7. 프론트엔드 구조

### Inertia 공유 데이터 (`HandleInertiaRequests.php`)
```js
// Vue에서 접근: usePage().props.auth.user
user = {
  id, nickname, email,
  political_type,   // 'conservative' | 'moderate' | 'progressive'
  faction_label,    // '보수' | '중도' | '진보'
  faction_color,    // '#E24B4A' | '#7F77DD' | '#378ADD'
  faction_emoji,    // '🔴' | '🟣' | '🔵'
  is_admin,
  manner_score,
  test_completed,   // boolean
  level,            // 현재 레벨 번호
  level_emoji,      // 레벨 이모지
}
// ⚠️ user.name 이 아닌 user.nickname 사용
```

### 주요 레이아웃·컴포넌트
| 파일 | 위치 | 역할 |
|------|------|------|
| `AppLayout.vue` | `resources/js/Layouts/` | 메인 네비게이션 (데스크탑: 메가드롭다운, 모바일: 햄버거+사이드드로어) |
| `AdminLayout.vue` | `resources/js/Layouts/` | 관리자 패널 레이아웃 |
| `QuillEditor.vue` | `resources/js/Components/` | 리치 텍스트 에디터 (SSR 안전: onMounted 동적 import) |
| `PostCard.vue` | `resources/js/Components/` | 게시글 카드 (상대시간, HOT/NEW 뱃지, 댓글수 DC스타일) |
| `SeoHead.vue` | `resources/js/Components/` | 메타태그 SEO 컴포넌트 |

### QuillEditor 주요 기능
- 이미지 업로드: `POST /posts/upload-image` (최대 5MB, `storage/post-images/YYYY/MM/`)
- 동영상: YouTube/Vimeo URL → iframe 임베드
- 이모지 피커: 6행 × 10열

### 주요 라우트 패턴
```php
// "create" 문자열이 post ID로 오인되는 충돌 방지
Route::get('/posts/{post}', [PostController::class, 'show'])
    ->whereNumber('post');  // 숫자 ID만 매칭
```

---

## 8. 주요 페이지 목록 (`resources/js/Pages/`)

```
Auth/
  Login.vue, Register.vue, PoliticalTest.vue, TestResult.vue

Boards/
  Show.vue        ← 게시판 목록 (3단 필터: 전체/인기→카테고리→정렬)
  
Posts/
  Show.vue        ← 게시글 상세 (DC/펨코 스타일 재답글)
  Create.vue
  Edit.vue

Home.vue          ← 커뮤니티 포털 (최신글, 진영 점수, 실시간 알림)

Stats/
  Index.vue       ← 진영 통계 대시보드 (일간/월간/연간 바 차트)
  Ranking.vue     ← 유저 랭킹 (모바일 가로스크롤 카테고리 탭)

Tools/
  Index.vue       ← 툴박스 4종:
                    1. 이상형 월드컵 (32명→랜덤 16개 토너먼트)
                    2. 시사 퀴즈 (50문제→랜덤 10개)
                    3. 닮은꼴 정치인 (20문제 MBTI 스타일)
                    4. 성향 재테스트

Profile/
  Index.vue       ← 유저 프로필 (레벨, 뱃지, 활동 기록)

Admin/
  AutoContent/
    Index.vue     ← AI 자동 콘텐츠 설정
    Logs.vue      ← AI 실행 로그 목록
    Logs/Show.vue ← AI 실행 로그 상세
```

---

## 9. AI 자동 콘텐츠 시스템 (중요 — 많은 작업 투입됨)

### 구조
```
GenerateDailyContent (Artisan Command, 매일 05:50 실행)
  └─ GenerateAIPostJob × N  (Queue: gemini, RateLimiter 적용)
       └─ GenerateAICommentJob × M  (Queue: gemini)
```

### 핵심 파일
| 파일 | 경로 | 역할 |
|------|------|------|
| `GeminiService.php` | `app/Services/` | Gemini API 래퍼 (모델 선택, 재시도, JSON 파싱) |
| `GenerateAIPostJob.php` | `app/Jobs/` | 게시글 생성 Job |
| `GenerateAICommentJob.php` | `app/Jobs/` | 댓글 생성 Job |
| `GenerateDailyContent.php` | `app/Console/Commands/` | 매일 실행 명령어 |
| `AutoContentConfig.php` | `app/Models/` | 싱글턴 설정 모델 (id=1) |
| `AutoContentController.php` | `app/Http/Controllers/Admin/` | 관리자 API |
| `AppServiceProvider.php` | `app/Providers/` | Gemini RateLimiter 등록 |

### 현재 Gemini 모델 설정 (`GeminiService.php`)
```php
private const MODELS = [
    'gemini-2.5-flash',        // ✅ 현재 기본 모델 (무료: RPM=5 / RPD=20 / 그라운딩 500 RPD)
    'gemini-2.5-flash-lite',   // ✅ fallback 경량 모델 (무료: 별도 RPD 한도)
];
// ❌ gemini-2.0-flash / gemini-2.0-flash-lite → 2026년 6월 1일 지원 종료 (제거됨)
```

### 중요 설정 — generationConfig
```php
'generationConfig' => [
    'temperature'     => $temperature,
    'maxOutputTokens' => 4096,  // 게시글: 4096, 댓글: 300
    'topP'            => 0.95,
    'thinkingConfig'  => ['thinkingBudget' => 0],  // ⚠️ 반드시 비활성화!
],
```

**⚠️ `thinkingBudget: 0` 이유**: `gemini-2.5-flash`는 기본적으로 thinking 모드가 ON.  
thinking 토큰이 `maxOutputTokens` 예산을 선점(~1948/2048 토큰)해서 텍스트 출력이 ~100 토큰(288 bytes)만 남고  
`finishReason: MAX_TOKENS`로 JSON이 잘려 게시글 제목에 raw JSON이 삽입되는 버그 발생.  
→ `thinkingBudget: 0`으로 비활성화하면 4096 토큰 전체가 텍스트 출력에 사용됨.

### 중요 설정 — responseMimeType 사용 금지
```php
// ❌ 절대 사용 금지: responseMimeType: "application/json"
// 이유 1: grounding(google_search) + JSON mode → HTTP 400 에러
// 이유 2: JSON mode 단독에서도 MAX_TOKENS 조기 종료 유발
// 해결: JSON 형식은 프롬프트 지시로만 강제 + extractJsonObject()로 파싱
```

### Rate Limiter (`AppServiceProvider.php`)
```php
// gemini-2.5-flash 무료 RPM=5 → 안전 마진 80%
RateLimiter::for('gemini', function (object $job) {
    return Limit::perMinute(4);
});
```

Job 설정 (`GenerateAIPostJob`, `GenerateAICommentJob`):
```php
public int $tries = 50;         // 최대 시도 횟수 (Rate-limit release는 tries 차감 없음)
public int $maxExceptions = 3;  // 실제 예외 3회 시 폐기
```

### AutoContentConfig 주요 기본값
```php
'posts_per_faction'     => 2,    // 진영별 2개 (6 게시글/일 + 댓글 ≈ 18 API콜 → 무료 RPD=20 이내)
'comments_per_post_min' => 1,
'comments_per_post_max' => 3,
'use_grounding'         => true, // true=RSS 뉴스 컨텍스트 사용 (Google Search 그라운딩 미사용)
'start_hour'            => 6,
'end_hour'              => 24,
```

**뉴스 컨텍스트 아키텍처** (2026-07-08 변경):
- ~~Google Search 그라운딩~~ → **KoreanNewsService RSS** 방식으로 전환
- `use_grounding=true` → `KoreanNewsService::fetchForPrompt()` 로 RSS 뉴스 수집 → Gemini 프롬프트에 직접 주입
- Gemini가 `refs: [0, 2]` 형태로 참고한 기사 번호 반환 → 실제 참고 기사만 출처 표시
- Google Search 그라운딩 API 비용($0.035/건) 완전 제거
- **무료 티어 비용**: ~$0.20/월 (사실상 무료)

**YouTube 처리** (2026-07-08 변경):
- ~~YouTube iframe 임베드~~ 완전 제거 (Gemini가 존재하지 않는 video ID 할루시네이션 → "재생 불가" 100%)
- 대체: YouTube 검색 링크 (`youtube.com/results?search_query={topic}+뉴스`)

**`KoreanNewsService`** (`app/Services/KoreanNewsService.php`):
- RSS 소스: Google News RSS + 연합뉴스 + 진영별 언론사 (조선/중앙/한겨레/오마이뉴스)
- 최대 4개 기사 수집, 3일 이내 최신 기사만, 주제 키워드 관련성 필터링

### JSON 파싱 (`extractJsonObject`)
3단계 시도:
1. 직접 `json_decode()`
2. `{...}` 경계 추출 후 파싱
3. 상태머신으로 JSON 문자열 내 literal newline 이스케이프 후 재파싱

---

## 10. 실시간 알림 시스템

### 구조
```
Notification Model + NotificationService
  ├─ 트리거: 댓글, 추천, 멘션
  └─ 프론트: AppLayout.vue 벨 아이콘 + 드롭다운 (폴링 방식)
```

**참고**: Pusher/Reverb 실시간 미구현. 현재는 polling 방식.  
실시간 WebSocket은 Phase 3 로드맵에 있음.

---

## 11. 사용자 레벨/뱃지 시스템 (⚠️ 진행 중)

### 현재 상태
- `experience_points` bigint 마이그레이션 완료
- `app/Services/UserLevelService.php` 파일 존재
- **미완료**: 50레벨 + 100뱃지 확장 작업 중단됨

### 다음 작업 (#7, #8)
**Task #7**: `UserLevelService.php` — 50레벨 + 100뱃지 시스템 확장
- 현재 레벨 테이블을 50레벨까지 확장
- 100개 뱃지 정의 (활동 기반: 게시글 수, 댓글 수, 추천 수, 연속 접속 등)
- `assignBadges(User $user)` 메서드 구현
- 레벨별 이모지/칭호 배정

**Task #8**: `Profile/Index.vue` — 뱃지 UI 아코디언 리뉴얼
- 현재 단순 목록 → 카테고리별 아코디언 UI
- 획득/미획득 뱃지 구분 표시
- 진행률 바 (예: "게시글 50/100개")

---

## 12. 구현 완료 기능 전체 목록

### Phase 1 — 인프라 및 인증 ✅
- Laravel 11 + PostgreSQL 16 + Docker 환경 구축
- 성향 테스트(10문항) → test_score → FactionType 배정
- 회원가입 플로우 (이메일 인증, 성향 테스트 필수)
- 진영별 접근 제한 미들웨어 (`EnsureFactionAccess`)

### Phase 2 — 커뮤니티 핵심 ✅
- 아지트·전쟁터·놀이터 CRUD (Quill 에디터 적용)
- 이미지 업로드 (`PostImageController`, `storage/post-images/YYYY/MM/`)
- Polymorphic 추천/비추천 시스템 (votes 테이블)
- 진영별 점수 집계 스케줄러 (`AggregateFactionDailyStats`)

### UI/UX 개선 ✅
- `AppLayout.vue` 로고·스코어티커·푸터 모바일 최적화
- `Boards/Show.vue` 헤더·필터바 모바일 최적화
- `Stats/Ranking.vue` 카테고리 탭 모바일 가로스크롤
- Stats 페이지 헤더·테이블 모바일 오버플로우 수정

### 게시판 고도화 ✅
- `BoardController` 인기글/조회순 시간 범위 제한 + 정렬 개편
- `Boards/Show.vue` 3단 필터 UI (전체/인기글 → 카테고리 → 정렬)
- `PostCard.vue` 상대시간·HOT/NEW 뱃지·댓글수 DC스타일
- is_hot 자동 등재 (추천 수 임계값 도달 시 VoteController에서 처리)
- 카테고리(말머리) 시스템 (boards.categories JSONB + posts.category)
- `PostCard`, `Create/Edit` — 카테고리 태그·말머리 선택기

### 댓글 시스템 ✅
- DC/펨코 스타일 재답글 (`reply_to_id` 컬럼 추가)
- `Comment` 모델 + `CommentController` reply_to_id 지원
- `PostController` — `replies.replyTo.user` eager load + `level_emoji` 주입
- `Show.vue` — 재답글 UI (누구에게 달린 답글인지 표시)

### 통계 대시보드 ✅
- `StatsController` 일간/월간/연간 데이터 메서드 재설계
- `Stats/Index.vue` 바 차트 전환 + 날짜 검색 정리
- `Stats/Index.vue` 차트 상단 여백 확보 (dot overflow 방지)
- `AggregateFactionDailyStats` 월·연 버그 수정
- 오늘 실시간 점수 포인트 추가 (스케줄러 실행 전에도 당일 실시간 반영)
- 메달 시스템 + 주간 TOP 유저

### 홈 페이지 ✅
- `HomeController.php` 생성
- `Home.vue` 커뮤니티 포털 스타일 전면 개편 (최신글, 진영 점수, 공지)

### SEO ✅
- `app.blade.php` + `app.js` 기반 메타 설정
- `SeoHead.vue` 컴포넌트 — 주요 공개 페이지 15개 일괄 적용

### 툴박스 ✅ (`Tools/Index.vue`)
- 이상형 월드컵: 정치인 32명 DB → 랜덤 16명 선택 → 토너먼트
- 시사 퀴즈: 50문제 DB → 랜덤 10문제 출제
- 닮은꼴 정치인: 20문제 MBTI 스타일 → 실제 정치인 결과
- 성향 재테스트: `PoliticalTestService` + Controller (랜덤 10문항 세션 기반)
- `MorePoliticalTestsSeeder`: 성향 테스트 문항 30개 추가

### AppLayout 네비게이션 ✅
- 툴박스 메뉴 4개 항목 추가
- 데스크탑: 호버 메가드롭다운
- 모바일: 햄버거 + 좌측 사이드 드로어

### 실시간 알림 ✅
- Migration + `Notification` Model + `NotificationService`
- `NotificationController` (읽음 처리, 목록)
- `AppLayout.vue` 벨 아이콘 + 드롭다운 (폴링 방식)

### AI 자동 콘텐츠 ✅
- Migration (auto_content_configs, auto_content_runs, auto_content_run_entries)
- Model: `AutoContentConfig`, `AutoContentRun`, `AutoContentRunEntry`
- `GeminiService`: Gemini API 래퍼, 모델 폴백, JSON 파싱, 그라운딩 지원
- `GenerateAIPostJob` + `GenerateAICommentJob`: Queue 기반 비동기 생성
- `GenerateDailyContent` Artisan Command (매일 05:50 자동 실행)
- `AutoContentController`: 설정/실행/중지/로그 API
- `Admin/AutoContent/Index.vue`: 설정 UI (그라운딩, 이미지, 유튜브, 수량, 시간)
- `Admin/AutoContent/Logs.vue`: 날짜별 실행 목록 + 중지 버튼
- `Admin/AutoContent/Logs/Show.vue`: 상세 로그 뷰어 + 오류 터미널
- `is_stopped` 컬럼: 실행 중 강제 중지 기능

---

## 13. 남은 작업 (미완료)

### 🔲 Task #7: UserLevelService 50레벨 + 100뱃지 확장
**파일**: `app/Services/UserLevelService.php`

필요한 작업:
1. 레벨 테이블을 50레벨까지 확장 (경험치 임계값, 레벨명, 이모지)
2. 100개 뱃지 정의 배열 (ID, 이름, 조건, 아이콘)
3. `assignBadges(User $user): array` 구현
4. `calculateLevel(int $exp): array` → level, emoji, next_threshold 반환
5. 활동 이벤트에 경험치 부여 훅 연결 (PostObserver, VoteObserver 등)

뱃지 카테고리 예시:
- 게시글 활동: 첫 게시글, 10/50/100/500개 작성
- 댓글 활동: 10/50/100/500개 댓글
- 추천: 100/500/1000 추천 받음
- 진영: 진영 대변인, 행동대장, 논객
- 기간: 7일/30일/100일 연속 접속
- 특별: 성향 테스트 완료, 툴박스 완주

### 🔲 Task #8: Profile/Index.vue 뱃지 UI 아코디언 리뉴얼
**파일**: `resources/js/Pages/Profile/Index.vue`

필요한 작업:
- 카테고리별 아코디언 UI (접었다 펼치기)
- 획득 뱃지: 컬러 아이콘 + 획득일
- 미획득 뱃지: 그레이아웃 + 달성 조건 표시
- 진행률 바 (예: 게시글 47/100)

### 🔲 Phase 3 — 추가 기능 (로드맵)
- 실시간 토론 알림 (WebSocket: Pusher 또는 Laravel Reverb)
- 진영별 실시간 투표 (The Poll) — 전쟁터 상단
- 관리자 패널 확장 (유저 관리, 신고 처리, 콘텐츠 관리)

---

## 14. 개발 환경 & 명령어

### Docker 필수 명령어
```bash
# 컨테이너 시작
docker compose up -d

# 프론트엔드 빌드
docker compose exec app npm run build

# ⚠️ npm run build 후 항상 실행 (root 빌드로 storage 소유권이 바뀜)
docker compose exec -u root app bash -c \
  "chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
   chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache"

# Windows 바인드 마운트 환경에서 chown이 무효인 경우 → 777로 대체
# docker compose exec -u root app chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# 컴파일 캐시 초기화 (뷰 캐시 + 로그)
docker compose exec -u root app bash -c \
  "rm -rf /var/www/html/storage/framework/views/* && \
   rm -f /var/www/html/storage/logs/laravel.log && \
   touch /var/www/html/storage/logs/laravel.log && \
   chown -R www-data:www-data /var/www/html/storage && \
   chmod -R 775 /var/www/html/storage"

# SSR 서버 재시작
docker compose restart ssr

# 마이그레이션
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:fresh --seed

# 큐 워커 (개발)
docker compose exec app php artisan queue:work --queue=gemini,default

# 큐 워커 재시작 (코드 변경 후)
docker compose exec app php artisan queue:restart

# 스토리지 심볼릭 링크 (최초 1회)
docker compose exec app php artisan storage:link

# 라우트 캐시 초기화
docker compose exec app php artisan route:clear

# 로그 확인
docker compose exec app tail -f storage/logs/laravel.log
docker compose exec app tail -f storage/logs/laravel.log | grep -E "(GeminiService|GenerateAI)"

# AI 자동 콘텐츠 수동 실행
docker compose exec app php artisan content:generate-daily --date=2026-07-08
docker compose exec app php artisan content:generate-daily --date=2026-07-08 --dry-run
```

---

## 15. Claude 협업 가이드 (중요 주의사항)

### 코드 스타일
- **PSR-12** 준수
- `declare(strict_types=1)` — 모든 PHP 파일 최상단 필수
- **Type Hinting** 필수 (파라미터, 반환값 모두)
- Enum 사용: 문자열 직접 비교 금지 (`FactionType::CONSERVATIVE` 방식 사용)

### DB 주의사항
- `factions_daily_stats`의 날짜 컬럼: `stat_date` (**`date` 아닌 `stat_date`**)
- `posts` 테이블 조회 시 운영 통계: `WHERE user_type != 'test'` 필터링 필요
- JSONB 컬럼 (`attachments`, `options`, `categories`): GIN 인덱스 권장
- `content` 검증: Quill HTML은 `strip_tags()` 후 길이 체크

### 프론트엔드 주의사항
- Vue에서 유저 닉네임: `user.name` ❌ → `user.nickname` ✅
- CSRF: `app.blade.php`에 `<meta name="csrf-token">` 필수, `window.axios`로 XSRF 자동 처리
- 이미지 URL: `asset('storage/' . $path)` 패턴 (`storage:link` 선행 필요)
- SSR 안전: Quill 등 브라우저 전용 라이브러리는 `onMounted`에서 동적 import

### GeminiService 주의사항
- `thinkingBudget: 0` **절대 제거하지 말 것** — 제거 시 JSON 잘림 버그 재발
- `responseMimeType: "application/json"` **절대 추가하지 말 것** — 두 가지 치명적 버그
- 모델 배열에 `gemini-2.0-flash` / `gemini-2.0-flash-lite` 추가 금지 — 2026년 6월 1일 종료
- Rate Limiter는 `AppServiceProvider.php`에서 `perMinute(4)` 설정 (RPM=5의 80% 마진)

### 파일 저장 위치
- `Laravel 소스`: `D:\2026\pcom\source\`
- `핵심 설정`: `D:\2026\pcom\source\.env`
- `CLAUDE.md` (기술 스펙): `D:\2026\pcom\CLAUDE.md`
- `이 문서`: `D:\2026\pcom\PROJECT_HANDOFF.md`

---

## 16. 알려진 이슈 및 해결된 버그

### ✅ 해결됨: AI 게시글 제목에 raw JSON 삽입 (`{"title":"...","content":...`)
**원인**: `gemini-2.5-flash` thinking 모드가 기본 활성화 → `maxOutputTokens` 2048 중  
~1948 토큰을 thinking에 소비 → 실제 텍스트 출력 ~100 토큰(288 bytes) → JSON 잘림 →  
fallback이 잘린 JSON 원문을 제목으로 삽입  
**해결**: `generationConfig`에 `thinkingConfig: {thinkingBudget: 0}` 추가  
**파일**: `app/Services/GeminiService.php` → `httpCall()` 메서드

### ✅ 해결됨: `responseMimeType: "application/json"` 충돌
**원인 1**: grounding + JSON mode → HTTP 400 (`Tool use with a response mime type: 'application/json' is unsupported`)  
**원인 2**: JSON mode 단독 사용 시에도 MAX_TOKENS 조기 종료  
**해결**: `responseMimeType` 완전 제거, JSON 형식은 프롬프트 지시로만 강제

### ✅ 해결됨: 429 Rate Limit 폭증
**원인**: MODELS 배열 첫 번째에 `gemini-2.5-flash`(RPD=20) → 모든 Job이 2.5-flash 시도 → RPD 즉시 소진  
**해결**: 모델 배열 재정렬 + Rate Limiter 8/min → 4/min 조정

### ✅ 해결됨: gemini-2.0-flash 지원 종료 (2026-06-01)
**원인**: MODELS 배열에 2.0-flash 계열이 기본 모델로 등록되어 있었으나 2026년 6월 1일 서비스 종료  
**해결**: 2.5-flash + 2.5-flash-lite로 교체

### ✅ 해결됨: `stat_date` 컬럼명 혼동
초기 설계에서 `date`로 설계되었으나 PostgreSQL 예약어와의 충돌 가능성으로 `stat_date`로 변경.  
집계 쿼리 작성 시 반드시 `stat_date` 사용.

### ✅ 해결됨: `npm run build` 후 storage 권한 오류
**원인**: Docker 내부 root 빌드로 storage 소유권이 root로 변경  
**해결**: 빌드 후 항상 `chown -R www-data:www-data` 실행 (반드시 `-u root` 옵션)

---

## 17. 관리자 패널 구조

**URL**: `/admin/*`  
**미들웨어**: `admin.auth` (로그인 + is_admin 체크)

```
/admin/dashboard          — 종합 대시보드
/admin/users              — 유저 관리
/admin/posts              — 게시글 관리
/admin/reports            — 신고 처리
/admin/auto-content       — AI 자동 콘텐츠 설정
/admin/auto-content/logs  — AI 실행 로그 목록
/admin/auto-content/logs/{run_id} — AI 실행 로그 상세
```

---

## 18. 외부 서비스 의존성

| 서비스 | 용도 | 설정 위치 |
|--------|------|----------|
| Google Gemini API | AI 게시글/댓글 생성 | `.env` GEMINI_API_KEY |
| Pixabay API | 게시글 이미지 삽입 | `.env` PIXABAY_API_KEY |
| Lorem Picsum | Pixabay 폴백 이미지 | 하드코딩 (API 키 불필요) |
| Pusher/Reverb | 실시간 알림 (미구현) | `.env` PUSHER_* |

---

*이 문서는 2026년 7월 8일 기준으로 작성되었습니다. CLAUDE.md와 함께 참고하세요.*
