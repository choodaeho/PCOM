# 폴릿(Polit) 라우트 맵

## 미들웨어 체인 요약

| 별칭 | 클래스 | 설명 |
|------|--------|------|
| `auth` | Sanctum | 로그인 필수 |
| `verified` | MustVerifyEmail | 이메일 인증 완료 |
| `user.active` | EnsureUserIsActive | 활성 계정 (정지·차단 차단) |
| `political.test` | EnsurePoliticalTestCompleted | 성향 테스트 완료 |
| `faction.access` | EnsureFactionAccess | 아지트 진영 접근 제한 |
| `admin` | EnsureUserIsAdmin | 관리자 전용 |

커뮤니티 접근 최소 조건: `auth` + `verified` + `user.active` + `political.test`

---

## Web 라우트 (routes/web.php)

### 공개 / 인증

| Method | URI | Name | 설명 |
|--------|-----|------|------|
| GET | `/` | `home` | 랜딩 페이지 |
| GET | `/login` | `login` | 로그인 폼 |
| POST | `/login` | `login.submit` | 이메일 로그인 |
| GET | `/register` | `register` | 회원가입 폼 |
| POST | `/register` | `register.submit` | 이메일 회원가입 |
| GET | `/auth/{provider}` | `social.redirect` | 소셜 로그인 리다이렉트 |
| GET | `/auth/{provider}/callback` | `social.callback` | 소셜 로그인 콜백 |
| POST | `/logout` | `logout` | 로그아웃 |

### 이메일 인증

| Method | URI | Name | 설명 |
|--------|-----|------|------|
| GET | `/email/verify` | `verification.notice` | 인증 대기 안내 |
| GET | `/email/verify/{id}/{hash}` | `verification.verify` | 인증 링크 처리 |
| POST | `/email/verification-notification` | `verification.send` | 재발송 |

### 성향 테스트

| Method | URI | Name | 미들웨어 | 설명 |
|--------|-----|------|----------|------|
| GET | `/political-test` | `political-test.show` | auth+verified+active | 테스트 페이지 |
| POST | `/political-test` | `political-test.submit` | auth+verified+active | 테스트 제출 |
| GET | `/political-test/result` | `political-test.result` | auth+verified+active | 결과 확인 |

### 커뮤니티 (auth+verified+active+political.test)

| Method | URI | Name | 추가 미들웨어 | 설명 |
|--------|-----|------|--------------|------|
| GET | `/boards` | `boards.index` | — | 게시판 목록 |
| GET | `/boards/{slug}` | `boards.show` | faction.access | 게시판 상세 |
| GET | `/boards/{slug}/posts/create` | `posts.create` | faction.access | 게시글 작성 폼 |
| POST | `/boards/{slug}/posts` | `posts.store` | faction.access | 게시글 등록 |
| GET | `/boards/{slug}/posts/{post}` | `posts.show` | faction.access | 게시글 상세 |
| GET | `/boards/{slug}/posts/{post}/edit` | `posts.edit` | faction.access | 게시글 수정 폼 |
| PUT | `/boards/{slug}/posts/{post}` | `posts.update` | faction.access | 게시글 수정 |
| DELETE | `/boards/{slug}/posts/{post}` | `posts.destroy` | faction.access | 게시글 삭제 |
| POST | `/boards/{slug}/posts/{post}/comments` | `comments.store` | faction.access | 댓글 작성 |
| PUT | `/comments/{comment}` | `comments.update` | — | 댓글 수정 |
| DELETE | `/comments/{comment}` | `comments.destroy` | — | 댓글 삭제 |
| POST | `/posts/{post}/vote` | `votes.post` | — | 게시글 추천/비추천 |
| POST | `/comments/{comment}/vote` | `votes.comment` | — | 댓글 추천/비추천 |
| POST | `/posts/{post}/report` | `reports.post` | — | 게시글 신고 |
| POST | `/comments/{comment}/report` | `reports.comment` | — | 댓글 신고 |
| GET | `/polls/active` | `polls.active` | — | 현재 투표 |
| POST | `/polls/{poll}/vote` | `polls.vote` | — | 투표 참여 |
| GET | `/stats` | `stats.index` | — | 통계 대시보드 |

### 관리자 (auth+verified+active+admin)

| Method | URI | Name | 설명 |
|--------|-----|------|------|
| GET | `/admin` | `admin.dashboard` | 대시보드 |
| GET/POST | `/admin/boards` | `admin.boards.*` | 게시판 CRUD |
| GET/POST | `/admin/users` | `admin.users.*` | 사용자 관리 |
| GET/POST | `/admin/reports` | `admin.reports.*` | 신고 처리 |
| GET/POST | `/admin/polls` | `admin.polls.*` | 투표 관리 |
| GET/PUT | `/admin/score-weights` | `admin.score-weights.*` | 가중치 설정 |

---

## API 라우트 (routes/api.php) — 모든 응답 JSON

### 공개 API

| Method | URI | 설명 |
|--------|-----|------|
| POST | `/api/v1/auth/register` | 이메일 회원가입 |
| POST | `/api/v1/auth/login` | 이메일 로그인 → Bearer Token |
| GET | `/api/v1/auth/social/{provider}` | 소셜 로그인 URL 반환 |
| POST | `/api/v1/auth/social/{provider}/callback` | 소셜 코드 교환 → Token |
| POST | `/api/v1/auth/password/forgot` | 비밀번호 재설정 메일 발송 |
| POST | `/api/v1/auth/password/reset` | 비밀번호 재설정 |
| GET | `/api/v1/stats/realtime` | 진영 실시간 점수 (1분 캐시) |

### 인증 필요 (Bearer Token)

| Method | URI | 설명 |
|--------|-----|------|
| POST | `/api/v1/auth/logout` | 로그아웃 |
| GET | `/api/v1/auth/me` | 내 정보 |
| GET | `/api/v1/auth/email/verify/{id}/{hash}` | 이메일 인증 |
| POST | `/api/v1/auth/email/resend` | 인증 메일 재발송 |
| GET | `/api/v1/political-test/questions` | 테스트 문항 |
| POST | `/api/v1/political-test/submit` | 테스트 제출 |
| GET | `/api/v1/political-test/result` | 테스트 결과 |

### 커뮤니티 API (verified + political.test)

| Method | URI | 설명 |
|--------|-----|------|
| GET | `/api/v1/boards` | 게시판 목록 (진영별 필터) |
| GET | `/api/v1/boards/{slug}` | 게시판 상세 |
| GET | `/api/v1/boards/{slug}/posts` | 게시글 목록 |
| POST | `/api/v1/boards/{slug}/posts` | 게시글 작성 |
| GET | `/api/v1/posts/{post}` | 게시글 상세 (조회수 증가) |
| PUT | `/api/v1/posts/{post}` | 게시글 수정 |
| DELETE | `/api/v1/posts/{post}` | 게시글 삭제 |
| GET | `/api/v1/posts/{post}/comments` | 댓글 목록 |
| POST | `/api/v1/posts/{post}/comments` | 댓글/대댓글 작성 |
| PUT | `/api/v1/comments/{comment}` | 댓글 수정 |
| DELETE | `/api/v1/comments/{comment}` | 댓글 삭제 |
| POST | `/api/v1/posts/{post}/vote` | 게시글 추천 토글 |
| POST | `/api/v1/comments/{comment}/vote` | 댓글 추천 토글 |
| POST | `/api/v1/posts/{post}/report` | 게시글 신고 |
| POST | `/api/v1/comments/{comment}/report` | 댓글 신고 |
| GET | `/api/v1/polls/active` | 현재 투표 + 진영별 현황 |
| POST | `/api/v1/polls/{poll}/vote` | 투표 참여 |
| GET | `/api/v1/polls/{poll}/stats` | 투표 통계 |
| GET | `/api/v1/stats/daily` | 일간 점수 이력 |
| GET | `/api/v1/stats/monthly` | 월간 점수 이력 |
| GET | `/api/v1/stats/yearly` | 연간 점수 이력 |

### 관리자 API (admin)

| Method | URI | 설명 |
|--------|-----|------|
| GET | `/api/v1/admin/summary` | 대시보드 요약 |
| GET | `/api/v1/admin/users` | 사용자 목록 |
| POST | `/api/v1/admin/users/{user}/suspend` | 일시 정지 |
| POST | `/api/v1/admin/users/{user}/ban` | 영구 차단 |
| POST | `/api/v1/admin/users/{user}/activate` | 활성화 |
| CRUD | `/api/v1/admin/boards` | 게시판 관리 |
| GET | `/api/v1/admin/posts` | 게시글 관리 |
| POST | `/api/v1/admin/posts/{post}/hide` | 게시글 숨김 |
| GET | `/api/v1/admin/reports` | 신고 목록 |
| POST | `/api/v1/admin/reports/{report}/action` | 신고 처리 |
| POST | `/api/v1/admin/reports/{report}/dismiss` | 신고 기각 |
| CRUD | `/api/v1/admin/polls` | 투표 관리 |
| GET/PUT | `/api/v1/admin/score-weights` | 점수 가중치 |
| POST | `/api/v1/admin/aggregate/daily` | 집계 수동 실행 |

---

## WebSocket 채널 (routes/channels.php)

| 채널 | 유형 | 이벤트 | 설명 |
|------|------|--------|------|
| `faction-scores` | Public | FactionScoreUpdated | 진영 점수 실시간 |
| `polls.{pollId}` | Public | PollVoteUpdated | 투표 현황 실시간 |
| `posts.{postId}` | Private | CommentCreated | 게시글 새 댓글 알림 |
| `boards.{slug}` | Private | PostCreated | 게시판 새 게시글 알림 |
| `users.{userId}` | Private | UserNotification | 개인 알림 |
| `presence.boards.{slug}` | Presence | — | 동시 접속자 현황 |
| `admin.notifications` | Private | — | 관리자 신고 알림 |
