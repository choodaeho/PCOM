# 폴릿(Polit) — 정치 성향별 커뮤니티 플랫폼

Politics의 약자. 사용자의 정치적 성향(보수·중도·진보)을 진단하고,
진영 전용 공간(아지트)과 전 진영 토론장(전쟁터), 정치 무관 자유 게시판(놀이터)을 제공하는 웹 플랫폼.

---

## 1. 기술 스택

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

코드 스타일: PSR-12, `declare(strict_types=1)` 필수, Type Hinting 필수.
API 문서: Swagger / OpenAPI 규격.

---

## 2. 핵심 비즈니스 로직

### 성향 진단
- 가입 시 필수 설문(10문항) → `test_score` 산출 → `FactionType` 자동 배정
- 점수 기준: +25 이상 → 보수 / -25 이하 → 진보 / 중간 → 중도

### 게시판 접근 제어
- **아지트(Azit)**: 본인 진영만 열람·작성 가능. `EnsureFactionAccess` 미들웨어 적용
- **전쟁터(Battle)**: 모든 진영 참여. 게시글에 작성자 진영 아이콘 표시
- **놀이터(Playground)**: 정치 무관 자유 게시판. 진영 제한 없음
- **공지사항(Notice)**: 관리자만 작성 가능

### 진영 점수 (Daily Stats)
매일 각 진영의 게시물 수·추천 수·활동 지표를 집계 → `factions_daily_stats` 테이블에 저장 → 대시보드 시각화

---

## 3. Enum 정의

### FactionType (`app/Enums/FactionType.php`)
```
conservative → 보수 (🔴 #E24B4A, 빨강) | moderate → 중도 (🟣 #7F77DD) | progressive → 진보 (🔵 #378ADD, 파랑)
```
⚠️ 한국 정치 관례: 보수=빨강(국민의힘 계열), 진보=파랑(민주당 계열)
- `FactionType::fromScore(int $score)` — 점수로 진영 결정
- `label()` / `color()` / `emoji()` / `values()` 메서드 제공

### BoardType (`app/Enums/BoardType.php`)
```
azit | battle | playground | notice
```
- `isFactionRestricted()` — azit만 true
- `isPlayground()` — playground만 true

### UserType (`app/Enums/UserType.php`)
```
admin | test | normal
```
- `admin`: 관리자 계정 (`is_admin = true` 와 함께 사용)
- `test`: 개발·QA 전용 더미 계정 (운영 통계에서 필터링)
- `normal`: 일반 가입 회원

### 기타 Enum
- `UserStatus`: pending | active | suspended | banned
- `PostStatus`: published | hidden | deleted_by_admin
- `VoteType`: up | down
- `ReportReason`: 신고 사유

---

## 4. 데이터베이스 설계 (PostgreSQL 16)

### users
| 컬럼 | 타입 | 비고 |
|------|------|------|
| id | bigint PK | |
| email | varchar(191) UNIQUE | |
| password | varchar(255) nullable | 소셜 전용은 null |
| nickname | varchar(50) UNIQUE | |
| avatar_url | varchar(500) nullable | |
| political_type | varchar(20) | conservative/moderate/progressive |
| test_score | smallint | -100 ~ +100 |
| test_completed_at | timestamp nullable | |
| status | varchar(20) | pending/active/suspended/banned |
| email_verified_at | timestamp nullable | |
| manner_score | smallint default 100 | 신고 누적 시 감점 |
| title | varchar(50) nullable | 진영 대변인 등 칭호 |
| is_admin | boolean default false | |
| admin_role | varchar(30) nullable | super_admin/content_admin/user_admin/stats_admin |
| user_type | varchar(20) default 'normal' | admin/test/normal |
| suspended_until | timestamp nullable | |
| deleted_at | timestamp nullable | soft delete |

인덱스: `idx_users_political_type` (partial), `idx_users_status` (partial), `idx_users_is_admin` (partial)

### posts
| 컬럼 | 타입 | 비고 |
|------|------|------|
| id | bigint PK | |
| user_id | bigint FK → users | |
| board_id | bigint FK → boards | |
| faction | varchar(20) | 작성 당시 진영 스냅샷 |
| title | varchar(300) | |
| content | text | Quill HTML |
| attachments | JSONB nullable | 첨부 파일 메타 배열 |
| status | varchar(20) default 'published' | |
| is_notice | boolean default false | |
| is_anonymous | boolean default false | |
| view_count / comment_count / vote_up_count / vote_down_count / report_count | uint | 비정규화 카운터 |
| search_vector | tsvector | FTS (GIN 인덱스, 트리거 자동 갱신) |
| deleted_at | timestamp nullable | soft delete |

### boards
slug, name, description, board_type, allowed_faction, sort_order, is_active

### political_tests
id, question, options (JSONB), weight

### factions_daily_stats
id, faction_type, stat_date (**주의: `date` 아닌 `stat_date`**), post_count, vote_count, total_score

### 기타 테이블
comments, votes (polymorphic), reports, polls, social_accounts, political_test_sessions, score_weights

---

## 5. 시드(Seed) 데이터

실행 순서 (`DatabaseSeeder.php`):
1. `ScoreWeightsSeeder` — 진영 점수 가중치 기본값
2. `PoliticalTestsSeeder` — 성향 테스트 문항 10개
3. `BoardsSeeder` — 기본 게시판 구성
4. `AdminUserSeeder` — 슈퍼관리자 계정
5. `TestAccountsSeeder` — 테스트 계정 90개

```bash
docker compose exec app php artisan db:seed
# 또는 특정 시더만
docker compose exec app php artisan db:seed --class=TestAccountsSeeder
```

### 게시판 구성 (BoardsSeeder — 15개)
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

### 관리자 계정 (AdminUserSeeder)
| 항목 | 값 |
|------|---|
| nickname | 대한민국 |
| email | `env('ADMIN_EMAIL', 'hhpapa77@polit.kr')` |
| password | `env('ADMIN_PASSWORD', 'itweb8335#')` |
| user_type | admin |
| is_admin | true |
| admin_role | super_admin |
| political_type | moderate |

> ⚠️ 운영 배포 전 반드시 `.env`로 ADMIN_PASSWORD 변경할 것

### 테스트 계정 (TestAccountsSeeder — 90개)
| 진영 | 수량 | 이메일 패턴 | 공통 비밀번호 |
|------|------|------------|-------------|
| 보수 | 30개 | conservative01~30@test.polit.kr | fusion!@34 |
| 중도 | 30개 | moderate01~30@test.polit.kr | fusion!@34 |
| 진보 | 30개 | progressive01~30@test.polit.kr | fusion!@34 |

`user_type = 'test'` 로 관리 → 운영 통계 쿼리에서 `WHERE user_type != 'test'` 로 필터링

---

## 6. 미들웨어

| alias | 클래스 | 역할 |
|-------|--------|------|
| `faction.access` | `EnsureFactionAccess` | 아지트 진영 접근 제어 (관리자 우회) |
| `admin.auth` | `EnsureAdminAuthenticated` | 관리자 패널: 로그인 + is_admin + 2FA 세션 확인 |
| `admin.user` | `EnsureUserIsAdmin` | 일반 라우트 관리자 체크 |
| `political.test` | `EnsurePoliticalTestCompleted` | 성향 테스트 완료 여부 확인 |
| `user.active` | `EnsureUserIsActive` | 계정 상태 active 확인 |

---

## 7. 프론트엔드 구조

### Inertia 공유 데이터 (`HandleInertiaRequests.php`)
```js
// Vue에서 접근: usePage().props.auth.user
user = {
  id, nickname, email,
  political_type,   // 'conservative' | 'moderate' | 'progressive'
  faction_label,    // '보수' | '중도' | '진보'
  faction_color,    // '#378ADD' | '#7F77DD' | '#E24B4A'
  faction_emoji,    // '🔵' | '🟣' | '🔴'
  is_admin,
  manner_score,
  test_completed,   // boolean
}
// 주의: user.name 이 아닌 user.nickname 사용
```

### 주요 컴포넌트
- `AppLayout.vue` — 네비게이션. 데스크탑: 호버 메가드롭다운 / 모바일: 햄버거 + 좌측 사이드 드로어
- `QuillEditor.vue` — 리치 텍스트 에디터
  - 이미지 업로드: `window.axios.post('/posts/upload-image', FormData)` (최대 5MB)
  - 동영상: YouTube/Vimeo URL → iframe 임베드
  - 이모지 피커: 6행 × 10열
  - SSR 안전: `onMounted`에서 동적 import

### 라우트 주의사항
```php
// /boards/{board:slug}/posts/{post}에서 "create" 문자열 충돌 방지
Route::get('/posts/{post}', [PostController::class, 'show'])
    ->whereNumber('post');  // 숫자 ID만 매칭
```

---

## 8. 개발 로드맵

### Phase 1: 인프라 및 인증 ✅ 완료
- [x] Laravel 프로젝트 초기 설정 및 PostgreSQL 연결
- [x] 성향 테스트 로직 및 회원가입 Flow
- [x] 진영별 접근 제한 미들웨어 구현

### Phase 2: 커뮤니티 핵심 ✅ 완료
- [x] 아지트·전쟁터·놀이터 CRUD (Quill 에디터 적용)
- [x] 이미지 업로드 (`PostImageController` — `storage/post-images/YYYY/MM/`)
- [x] Polymorphic 추천/비추천 시스템
- [x] 진영별 점수 집계 스케줄러

### Phase 3: 분석 및 UI/UX 🔲 진행 중
- [ ] 진영별 통계 대시보드 (Chart.js 연동)
- [ ] 실시간 토론 알림 서비스 (Pusher/Reverb)
- [ ] 이상형 월드컵 기능
- [ ] 시사 퀴즈 기능

---

## 9. 자주 쓰는 Docker 명령어

```bash
# 프론트엔드 빌드 (app 컨테이너 내부)
docker compose exec app npm run build

# ⚠️ npm run build 후 항상 실행 — root 빌드로 storage 소유권이 바뀜
# 반드시 -u root 옵션 사용 (일반 exec는 chown 권한 없음)
docker compose exec -u root app bash -c \
  "chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
   chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache"

# Windows 바인드 마운트라 chown이 무효인 경우 → 777로 대체
# docker compose exec -u root app chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# 컴파일 캐시까지 꼬인 경우 — 뷰 캐시 + 로그 초기화
# docker compose exec -u root app bash -c \
#   "rm -rf /var/www/html/storage/framework/views/* && \
#    rm -f /var/www/html/storage/logs/laravel.log && \
#    touch /var/www/html/storage/logs/laravel.log && \
#    chown -R www-data:www-data /var/www/html/storage && \
#    chmod -R 775 /var/www/html/storage"

# SSR 서버 재시작
docker compose restart ssr

# 마이그레이션
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:fresh --seed

# 시더 실행
docker compose exec app php artisan db:seed

# 스토리지 심볼릭 링크 (최초 1회)
docker compose exec app php artisan storage:link

# 라우트 캐시 초기화
docker compose exec app php artisan route:clear

# 로그 확인
docker compose exec app tail -f storage/logs/laravel.log
```

---

## 10. 활성화 전략 (Ideation)

- **진영별 랭킹**: 활동량 높은 유저에게 '진영 대변인', '행동대장' 칭호 부여
- **실시간 투표(The Poll)**: 전쟁터 상단에 시사 이슈 투표 → 진영 간 비율 시각화
- **매너 점수**: AI 욕설 필터 + 신고 누적 시 진영 점수 감점
- **여론 조사 데이터 판매**: 성향별 통계 인사이트 리포트
- **진영별 배너 광고**: 아지트 특성에 맞는 맞춤 광고

---

## 11. 협업 가이드 (Claude 전용)

- **코드 스타일**: PSR-12, `declare(strict_types=1)`, Type Hinting 필수
- **DB 컬럼 주의**: `factions_daily_stats`의 날짜 컬럼은 `stat_date` (not `date`)
- **Enum 사용**: 문자열 직접 비교 금지, 반드시 Enum 사용
- **JSONB 인덱싱**: `attachments`, `options` 컬럼은 GIN 인덱스 권장
- **content 검증**: Quill HTML은 `strip_tags()` 후 길이 체크
- **CSRF**: `app.blade.php`에 `<meta name="csrf-token">` 필수, `window.axios`로 XSRF 자동 처리
- **이미지 URL**: `asset('storage/' . $path)` 패턴 사용 (`storage:link` 선행 필요)
