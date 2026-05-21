# 폴릿(Polit) — 데이터베이스 설계 README

## 개요

Phase 1+2 통합 DB 설계. PostgreSQL 16 기준.

---

## 마이그레이션 실행 순서

```bash
# 전체 마이그레이션 + 시드 한 번에 실행
php artisan migrate --seed

# 마이그레이션만
php artisan migrate

# 시드만
php artisan db:seed
```

| 순서 | 파일명 | 설명 |
|------|--------|------|
| 01 | `create_users_table` | 사용자 (ENUM 타입 생성 포함) |
| 02 | `create_social_accounts_table` | 소셜 로그인 계정 연결 |
| 03 | `create_political_tests_table` | 성향 테스트 문항 (JSONB 선택지) |
| 04 | `create_political_test_sessions_table` | 사용자별 테스트 응답 세션 |
| 05 | `create_score_weights_table` | 진영 점수 가중치 설정 |
| 06 | `create_boards_table` | 생성형 게시판 (아지트/전쟁터/공지) |
| 07 | `create_posts_table` | 게시물 (FTS 트리거 포함) |
| 08 | `create_comments_table` | 댓글 + 대댓글 (1-depth) |
| 09 | `create_votes_table` | 추천/비추천 (Polymorphic) |
| 10 | `create_reports_table` | 신고 (Polymorphic) |
| 11 | `create_polls_table` | 실시간 투표 |
| 12 | `create_poll_votes_table` | 투표 참여 기록 |
| 13 | `create_factions_daily_stats_table` | 진영 일간 통계 |
| 14 | `create_factions_monthly_stats_table` | 진영 월간 통계 |
| 15 | `create_factions_yearly_stats_table` | 진영 연간 통계 |
| 16 | `create_admin_action_logs_table` | 관리자 액션 감사 로그 |

---

## 진영 점수 공식

```
raw_score        = post×3.00 + comment×1.00 + vote_up×2.00
                 - vote_down×0.50 - report×5.00

normalized_score = raw_score ÷ max(active_user_count, 1)
```

가중치는 `score_weights` 테이블에서 관리자가 실시간 조정 가능.

---

## 핵심 설계 결정사항

### 1. Faction Snapshot
`posts.faction`, `comments.faction`, `poll_votes.faction` 모두 **작성 당시 진영**을 스냅샷으로 저장.
사용자 진영이 재테스트로 바뀌어도 과거 콘텐츠의 진영 귀속은 변경되지 않음.

### 2. 비정규화 카운터
`posts.comment_count`, `posts.vote_up_count` 등은 Observer/Event로 자동 갱신.
실시간 COUNT 쿼리를 줄여 고트래픽 조회 성능 확보.

### 3. Polymorphic 관계
`votes`, `reports`는 `votable_type/id`, `reportable_type/id`로 posts·comments를 모두 처리.
새 콘텐츠 타입 추가 시 기존 로직 재사용 가능.

### 4. FTS (Full-Text Search)
`posts.search_vector`는 `BEFORE INSERT OR UPDATE` 트리거로 자동 업데이트.
`simple` 사전 사용 (한국어 형태소 분석기 pg_bigm 연동 시 교체).

### 5. 4-tier 통계 계층
```
Redis (실시간, 1분 갱신)
  → factions_daily_stats  (매일 00:05 집계)
    → factions_monthly_stats (매월 1일 00:10 롤업)
      → factions_yearly_stats  (매년 1월 1일 00:15 롤업)
```

### 6. 소프트 삭제
`users`, `boards`, `posts`, `comments`에 `deleted_at` 적용.
관리자 복구 가능 + 감사 추적용.

---

## 비주얼 DB 설계 문서

→ `db_design.html` 파일 브라우저로 열기
