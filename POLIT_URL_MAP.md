# 폴릿(Polit) URL 전체 맵

> 최종 업데이트: 2026-06-17 (이미지 업로드 엔드포인트 추가 / 툴박스·놀이터 반영)  
> 도메인 예시: `https://polit.kr`

---

## 1. 공개 페이지 (비로그인 허용)

| URL | 페이지 | Route Name | 비고 |
|-----|--------|------------|------|
| `/` | 메인 랜딩 | `home` | 진영 소개, 실시간 점수 |
| `/login` | 로그인 | `login` | 이메일 로그인 |
| `/register` | 회원가입 | `register` | 이메일 가입 |
| `/auth/kakao` | 카카오 소셜 로그인 | `social.redirect` | |
| `/auth/naver` | 네이버 소셜 로그인 | `social.redirect` | |
| `/auth/google` | 구글 소셜 로그인 | `social.redirect` | |
| `/auth/kakao/callback` | 카카오 콜백 | `social.callback` | |
| `/auth/naver/callback` | 네이버 콜백 | `social.callback` | |
| `/auth/google/callback` | 구글 콜백 | `social.callback` | |
| `/tools` | 툴박스 | `tools.index` | 로또번호생성기, 운세, 이상형 월드컵(준비중), 시사 퀴즈(준비중) |
| `/boards` | 게시판 목록 | `boards.index` | 아지트·전쟁터·놀이터·공지사항 전체 목록 + 상단 툴박스 |
| `/boards/{slug}` | 게시판 글 목록 | `boards.show` | ex) `/boards/battle-politics` |
| `/boards/{slug}/posts/{id}` | 게시글 상세 | `posts.show` | 비로그인 읽기 가능 |

---

## 2. 인증 플로우 (로그인 필요)

| URL | 페이지 | Route Name | 비고 |
|-----|--------|------------|------|
| `/email/verify` | 이메일 인증 안내 | `verification.notice` | |
| `/email/verify/{id}/{hash}` | 이메일 인증 처리 | `verification.verify` | 서명된 링크 |
| `/email/verification-notification` (POST) | 인증 메일 재발송 | `verification.send` | |
| `/logout` (POST) | 로그아웃 | `logout` | |

---

## 3. 성향 테스트 (로그인 + 이메일 인증 + 계정 활성)

| URL | 페이지 | Route Name | 비고 |
|-----|--------|------------|------|
| `/political-test` | 성향 테스트 문항 | `political-test.show` | 미완료자만 접근 |
| `/political-test` (POST) | 테스트 제출 | `political-test.submit` | |
| `/political-test/result` | 테스트 결과 | `political-test.result` | 진영 확정 화면 |

---

## 4. 커뮤니티 (로그인 + 인증 + 활성 + **테스트 완료**)

### 이미지 업로드

| URL | Method | Route Name | 비고 |
|-----|--------|------------|------|
| `/posts/upload-image` | POST | `posts.upload-image` | Quill 에디터 이미지 업로드. `auth + verified` 필요. 최대 5MB, JPEG·PNG·GIF·WebP. 응답: `{"url": "https://..."}` |

> 저장 경로: `storage/app/public/post-images/YYYY/MM/{uuid}.ext`  
> 공개 URL: `asset('storage/post-images/YYYY/MM/{uuid}.ext')` (`storage:link` 선행 필요)

### 게시글 CRUD

| URL | 페이지 | Route Name | 비고 |
|-----|--------|------------|------|
| `/boards/{slug}/posts/create` | 게시글 작성 | `posts.create` | `faction.access` 미들웨어 적용 |
| `/boards/{slug}/posts` (POST) | 게시글 저장 | `posts.store` | |
| `/boards/{slug}/posts/{id}/edit` | 게시글 수정 | `posts.edit` | 작성자 본인만 |
| `/boards/{slug}/posts/{id}` (PUT) | 게시글 업데이트 | `posts.update` | |
| `/boards/{slug}/posts/{id}` (DELETE) | 게시글 삭제 | `posts.destroy` | 작성자 or 관리자 |

> **아지트 게시판**: `faction.access` 미들웨어가 본인 진영 여부 검사  
> **전쟁터·놀이터**: `allowed_faction = 'all'` → 모든 진영 작성 가능

### 댓글·투표·신고

| URL | Method | Route Name | 비고 |
|-----|--------|------------|------|
| `/posts/{id}/comments` | POST | `comments.store` | 댓글 작성 |
| `/comments/{id}` | PUT | `comments.update` | 작성자 본인만 |
| `/comments/{id}` | DELETE | `comments.destroy` | |
| `/posts/{id}/vote` | POST | `votes.post` | 추천/비추천 토글 |
| `/comments/{id}/vote` | POST | `votes.comment` | 댓글 추천/비추천 토글 |
| `/posts/{id}/report` | POST | `reports.post` | 게시글 신고 |
| `/comments/{id}/report` | POST | `reports.comment` | 댓글 신고 |

### 실시간 투표 (The Poll)

| URL | Method | Route Name | 비고 |
|-----|--------|------------|------|
| `/polls/active` | GET | `polls.active` | 활성 투표 조회 |
| `/polls/{id}/vote` | POST | `polls.vote` | 투표 참여 |

### 통계 대시보드

| URL | 페이지 | Route Name |
|-----|--------|------------|
| `/stats` | 통계 메인 | `stats.index` |
| `/stats/daily` | 일간 통계 | `stats.daily` |
| `/stats/monthly` | 월간 통계 | `stats.monthly` |
| `/stats/yearly` | 연간 통계 | `stats.yearly` |

### 프로필

| URL | 페이지 | Route Name |
|-----|--------|------------|
| `/profile` | 프로필 조회 | `profile.index` |
| `/profile/edit` | 프로필 수정 | `profile.edit` |
| `/profile` (PUT) | 프로필 업데이트 | `profile.update` |

---

## 5. 관리자 인증 `/admin/login` (일반 로그인과 완전 분리)

> 관리자 계정(`is_admin = true`)은 `/login`(일반 로그인 페이지) 사용 불가.  
> 반드시 `/admin/login`을 통해 로그인 후 Google Authenticator OTP 2단계 인증을 완료해야 합니다.

### 로그인 플로우

```
1단계: /admin/login     이메일 + 비밀번호 입력
          ↓ (성공)
2단계: /admin/login/2fa Google Authenticator 6자리 OTP 입력
          ↓ (최초 1회: QR 코드 스캔 + OTP 등록)
완료:  /admin          대시보드 진입
```

### 관리자 인증 URL

| URL | Method | 페이지 | Route Name | 비고 |
|-----|--------|--------|------------|------|
| `/admin/login` | GET | 관리자 로그인 폼 | `admin.login` | 이미 2FA 완료 시 대시보드 리다이렉트 |
| `/admin/login` | POST | 로그인 제출 | `admin.login.submit` | 1단계: 이메일+비밀번호 검증 |
| `/admin/login/2fa` | GET | OTP 인증 폼 | `admin.login.2fa` | 최초: QR 설정 화면 / 이후: OTP 입력 화면 |
| `/admin/login/2fa` | POST | OTP 검증 | `admin.login.2fa.verify` | 2단계: Google Authenticator 코드 검증 |
| `/admin/logout` | POST | 로그아웃 | `admin.logout` | 세션 초기화 후 `/admin/login` 리다이렉트 |

---

## 6. 관리자 패널 `/admin` (`admin.auth` 미들웨어 — 로그인 + is_admin + 2FA 완료 필수)

| URL | 페이지 | Route Name | 비고 |
|-----|--------|------------|------|
| `/admin` | 대시보드 | `admin.dashboard` | 요약 지표 |
| `/admin/users` | 사용자 목록 | `admin.users.index` | 전체 회원 조회/검색 |
| `/admin/users/{id}` | 사용자 상세 | `admin.users.show` | |
| `/admin/users/{id}/suspend` (POST) | 사용자 일시정지 | `admin.users.suspend` | |
| `/admin/users/{id}/ban` (POST) | 사용자 영구차단 | `admin.users.ban` | |
| `/admin/users/{id}/activate` (POST) | 사용자 활성화 | `admin.users.activate` | |
| `/admin/boards` | 게시판 관리 | `admin.boards.index` | |
| `/admin/boards/create` | 게시판 생성 | `admin.boards.create` | |
| `/admin/boards/{id}/edit` | 게시판 수정 | `admin.boards.edit` | |
| `/admin/posts` | 게시글 관리 | `admin.posts.index` | 전체 게시글 조회/삭제 |
| `/admin/posts/{id}/hide` (POST) | 게시글 숨김 | `admin.posts.hide` | |
| `/admin/posts/{id}/restore` (POST) | 게시글 복원 | `admin.posts.restore` | |
| `/admin/reports` | 신고 처리 | `admin.reports.index` | 미처리 신고 목록 |
| `/admin/reports/{id}` | 신고 상세 | `admin.reports.show` | |
| `/admin/reports/{id}/action` (POST) | 신고 조치 | `admin.reports.action` | 경고/삭제/정지 |
| `/admin/reports/{id}/dismiss` (POST) | 신고 기각 | `admin.reports.dismiss` | |
| `/admin/polls` | 투표 관리 | `admin.polls.index` | |
| `/admin/polls/create` | 투표 생성 | `admin.polls.create` | |
| `/admin/polls/{id}/edit` | 투표 수정 | `admin.polls.edit` | |
| `/admin/polls/{id}/close` (POST) | 투표 마감 | `admin.polls.close` | |
| `/admin/score-weights` | 점수 가중치 | `admin.score-weights.index` | 진영 점수 기준 조정 |

> **접근 조건**: `admin.auth` 미들웨어 — ① Auth::check() + ② is_admin = true + ③ 세션 `admin_2fa_verified = true`  
> 세 조건 중 하나라도 불충족 시 `/admin/login` 또는 `/admin/login/2fa` 로 리다이렉트

---

## 7. REST API (`/api/v1/`) — Sanctum 토큰 인증

> `Authorization: Bearer {token}` 헤더 필요 (인증 필요 엔드포인트)

### 인증 API

| Endpoint | Method | 설명 |
|----------|--------|------|
| `/api/v1/auth/register` | POST | 회원가입 |
| `/api/v1/auth/login` | POST | 로그인 → 토큰 반환 |
| `/api/v1/auth/logout` | POST | 로그아웃 (토큰 삭제) |
| `/api/v1/auth/email/verify/{id}/{hash}` | GET | 이메일 인증 |
| `/api/v1/auth/email/resend` | POST | 인증 메일 재발송 |
| `/api/v1/auth/password/forgot` | POST | 비밀번호 재설정 메일 발송 |
| `/api/v1/auth/password/reset` | POST | 비밀번호 재설정 처리 |
| `/api/v1/auth/social/{provider}/redirect` | GET | 소셜 로그인 리다이렉트 |
| `/api/v1/auth/social/{provider}/callback` | GET | 소셜 로그인 콜백 |

### 성향 테스트 API

| Endpoint | Method | 설명 |
|----------|--------|------|
| `/api/v1/political-test` | GET | 문항 목록 조회 |
| `/api/v1/political-test/submit` | POST | 답변 제출 → 진영 확정 |
| `/api/v1/political-test/result` | GET | 나의 테스트 결과 조회 |

### 커뮤니티 API

| Endpoint | Method | 설명 |
|----------|--------|------|
| `/api/v1/boards` | GET | 게시판 목록 |
| `/api/v1/boards/{slug}` | GET | 게시판 상세 + 글 목록 |
| `/api/v1/boards/{slug}/posts` | GET | 게시글 목록 (페이지네이션) |
| `/api/v1/boards/{slug}/posts` | POST | 게시글 작성 |
| `/api/v1/posts/{id}` | GET | 게시글 상세 |
| `/api/v1/posts/{id}` | PUT | 게시글 수정 |
| `/api/v1/posts/{id}` | DELETE | 게시글 삭제 |
| `/api/v1/posts/{id}/comments` | GET | 댓글 목록 |
| `/api/v1/posts/{id}/comments` | POST | 댓글 작성 |
| `/api/v1/comments/{id}` | PUT | 댓글 수정 |
| `/api/v1/comments/{id}` | DELETE | 댓글 삭제 |
| `/api/v1/posts/{id}/vote` | POST | 게시글 추천/비추천 |
| `/api/v1/comments/{id}/vote` | POST | 댓글 추천/비추천 |
| `/api/v1/posts/{id}/report` | POST | 게시글 신고 |
| `/api/v1/comments/{id}/report` | POST | 댓글 신고 |

### 투표·통계 API

| Endpoint | Method | 설명 |
|----------|--------|------|
| `/api/v1/polls/active` | GET | 활성 투표 조회 |
| `/api/v1/polls/{id}/vote` | POST | 투표 참여 |
| `/api/v1/polls/{id}/stats` | GET | 투표 통계 (진영별) |
| `/api/v1/stats` | GET | 통계 메인 |
| `/api/v1/stats/daily` | GET | 일간 통계 |
| `/api/v1/stats/monthly` | GET | 월간 통계 |
| `/api/v1/stats/yearly` | GET | 연간 통계 |

### 프로필·사용자 API

| Endpoint | Method | 설명 |
|----------|--------|------|
| `/api/v1/user/me` | GET | 내 정보 조회 |
| `/api/v1/user/profile` | GET | 프로필 조회 |
| `/api/v1/user/profile` | PUT | 프로필 수정 |
| `/api/v1/user/password` | PUT | 비밀번호 변경 |
| `/api/v1/user` | DELETE | 계정 탈퇴 |
| `/api/v1/user/posts` | GET | 내 게시글 목록 |
| `/api/v1/user/comments` | GET | 내 댓글 목록 |

### Swagger API 문서

| URL | 설명 |
|-----|------|
| `/api/documentation` | Swagger UI |
| `/api/documentation.json` | OpenAPI JSON |

---

## 8. 게시판 슬러그(slug) 목록

| slug | 이름 | 유형 | 접근 가능 진영 | 미들웨어 |
|------|------|------|--------------|---------|
| `conservative-azit` | 보수 아지트 | 아지트 | 보수만 | `faction.access` |
| `moderate-azit` | 중도 아지트 | 아지트 | 중도만 | `faction.access` |
| `progressive-azit` | 진보 아지트 | 아지트 | 진보만 | `faction.access` |
| `battle-politics` | 정치 전쟁터 | 전쟁터 | 전 진영 | — |
| `battle-economy` | 경제 전쟁터 | 전쟁터 | 전 진영 | — |
| `battle-society` | 사회 전쟁터 | 전쟁터 | 전 진영 | — |
| `play-humor` | 유머/짤방 | 놀이터 | 전 진영 | — |
| `play-game` | 게임 | 놀이터 | 전 진영 | — |
| `play-sports` | 스포츠 | 놀이터 | 전 진영 | — |
| `play-entertainment` | 방송/연예 | 놀이터 | 전 진영 | — |
| `play-stock` | 주식/코인 | 놀이터 | 전 진영 | — |
| `play-it` | IT/테크 | 놀이터 | 전 진영 | — |
| `play-food` | 먹방/맛집 | 놀이터 | 전 진영 | — |
| `play-free` | 자유게시판 | 놀이터 | 전 진영 | — |
| `notice` | 공지사항 | 공지 | 전 진영 (읽기) / 관리자 (쓰기) | `admin.user` |

> **아지트 접근 규칙**: `EnsureFactionAccess` 미들웨어가 `board.allowed_faction`과 `user.political_type`을 비교.  
> 관리자(`is_admin = true`)는 모든 아지트 진입 가능.  
> **게시글 URL 주의**: `{post}`는 숫자 ID만 허용(`->whereNumber('post')`). "create" 문자열은 별도 라우트로 분리.

---

## 9. 계정 정보 요약

### 관리자 계정

| 항목 | 값 |
|------|-----|
| 이메일 | `hhpapa77@polit.kr` (ADMIN_EMAIL env) |
| 닉네임 | `대한민국` |
| 비밀번호 | `itweb8335#` (ADMIN_PASSWORD env로 변경 가능) |
| 권한 | `super_admin` / `is_admin = true` |
| user_type | `admin` |
| 로그인 URL | `/admin/login` (**일반 `/login` 사용 불가**) |
| 2FA | Google Authenticator (TOTP) — 최초 로그인 시 QR 등록 |

### 테스트 계정 (공통 비밀번호: `fusion!@34`)

| 진영 | 이메일 패턴 | 수 |
|------|------------|-----|
| 보수 | `conservative01~30@test.polit.kr` | 30개 |
| 중도 | `moderate01~30@test.polit.kr` | 30개 |
| 진보 | `progressive01~30@test.polit.kr` | 30개 |

**보수 닉네임 (30개)**  
자유대한, 태극전사, 나라사랑해, 국가수호대, 애국청년단, 자유파수꾼, 보수의한방, 전통가치론, 강철안보론, 자유시장파, 경제성장론, 작은정부론, 한미동맹론, 보수논객킹, 자유민주파, 대한파수꾼, 우파의목소리, 보수혁신파, 국익우선론, 자유경제파, 성장주의자, 보수왕도, 전통수호자, 우파전사단, 보수정론직, 안보제일론, 자유우파론, 경제자유론, 나라지킴이, 한국보수파

**중도 닉네임 (30개)**  
균형잡는자, 합리주의자, 중립지대인, 실용주의자, 균형감각론, 이성주의자, 중도의힘, 합리선택자, 열린사고자, 공정시각론, 중도현실론, 실용론자, 균형발전론, 합리개혁론, 중도실용론, 팩트체크왕, 이성시민론, 합리사고인, 중립적시각, 균형정치론, 실용접근자, 중도개혁론, 합리경제론, 균형사회론, 현실주의자, 이성균형론, 합리논객론, 중도유권자, 균형의소리, 실용개혁론

**진보 닉네임 (30개)**  
평등사회론, 노동자의꿈, 복지국가론, 민중의목소리, 환경지킴이, 사회정의론, 평등세상론, 진보의날개, 노동연대론, 복지확대론, 민주시민론, 평등권리론, 사회개혁론, 진보전진론, 노동권리자, 환경보호자, 사회연대론, 민중연대론, 복지천국론, 진보시민론, 노동존중론, 평등국가론, 민주개혁론, 진보의길, 사회적가치, 노동의벗, 복지확장론, 평등시민론, 민주진보론, 사회변혁론

> 테스트 계정은 `user_type = 'test'` 로 구분됩니다.  
> 통계 쿼리에서 `WHERE user_type != 'test'` 조건으로 실제 사용자 데이터와 분리하세요.
