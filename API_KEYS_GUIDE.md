# 폴릿(Polit) 외부 API 키 설정 가이드

이 프로젝트에서 실제 서비스 운영을 위해 발급/설정이 필요한 모든 외부 API 키와 설정값을 정리합니다.

---

## 1. 카카오 소셜 로그인 (필수)

**발급처**: https://developers.kakao.com

**발급 절차**:
1. 카카오 개발자 콘솔 → 내 애플리케이션 → 애플리케이션 추가하기
2. 앱 이름: 폴릿(Polit), 사업자명 입력
3. 플랫폼 → Web 플랫폼 등록 → 사이트 도메인 입력
4. 카카오 로그인 → 활성화 설정 ON
5. Redirect URI 등록: `https://yourdomain.com/auth/kakao/callback`
6. 동의항목 설정: nickname(필수), email(선택)
7. 요약정보 → REST API 키 복사

**.env 설정**:
```dotenv
KAKAO_CLIENT_ID=your_rest_api_key_here
KAKAO_CLIENT_SECRET=your_client_secret_here   # 보안 → Client Secret 활성화 후 발급
KAKAO_REDIRECT_URI=https://yourdomain.com/auth/kakao/callback
```

**주의사항**:
- 로컬 개발: `http://localhost/auth/kakao/callback` 도 Redirect URI에 추가 필요
- Client Secret은 선택사항이지만 보안을 위해 활성화 권장

---

## 2. 네이버 소셜 로그인 (필수)

**발급처**: https://developers.naver.com

**발급 절차**:
1. Application → 애플리케이션 등록
2. 사용 API: 네이버 로그인 선택
3. 제공 정보 선택: 이메일(필수), 별명(필수), 프로필 사진(선택)
4. 서비스 환경: PC 웹 → 서비스 URL 입력
5. Callback URL: `https://yourdomain.com/auth/naver/callback`
6. 애플리케이션 등록 후 Client ID / Client Secret 확인

**.env 설정**:
```dotenv
NAVER_CLIENT_ID=your_client_id_here
NAVER_CLIENT_SECRET=your_client_secret_here
NAVER_REDIRECT_URI=https://yourdomain.com/auth/naver/callback
```

**주의사항**:
- 서비스 상태가 "검수 중"이면 등록된 테스터 계정만 로그인 가능
- 실서비스 오픈 전 검수 신청 필요

---

## 3. 구글 OAuth 2.0 (필수)

**발급처**: https://console.cloud.google.com

**발급 절차**:
1. Google Cloud Console → 새 프로젝트 생성 "polit"
2. API 및 서비스 → OAuth 동의 화면 설정
   - 사용자 유형: 외부
   - 앱 이름, 로고, 지원 이메일 입력
   - 범위 추가: `email`, `profile`, `openid`
3. 사용자 인증 정보 → OAuth 2.0 클라이언트 ID 만들기
   - 애플리케이션 유형: 웹 애플리케이션
   - 승인된 리디렉션 URI: `https://yourdomain.com/auth/google/callback`
4. 클라이언트 ID / 클라이언트 보안 비밀번호 복사

**.env 설정**:
```dotenv
GOOGLE_CLIENT_ID=your_client_id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your_client_secret_here
GOOGLE_REDIRECT_URI=https://yourdomain.com/auth/google/callback
```

**주의사항**:
- 퍼블리시되지 않은 앱은 테스트 사용자 100명 제한
- 민감한 범위 사용 시 Google 인증 절차 필요

---

## 4. Laravel Reverb (WebSocket) — 자체 서버

Reverb는 외부 서비스가 아닌 **자체 호스팅 WebSocket 서버**입니다.

**.env 설정**:
```dotenv
REVERB_APP_ID=polit-001
REVERB_APP_KEY=your-random-32char-key-here
REVERB_APP_SECRET=your-random-32char-secret-here
REVERB_HOST=your-domain.com
REVERB_PORT=443
REVERB_SCHEME=https

# 프론트엔드(Vite)에서 Reverb 연결용
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

**로컬 개발 설정**:
```dotenv
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

---

## 5. SMTP 이메일 서비스 (이메일 인증 필수)

### 로컬 개발: Mailpit (자동 설정)
Docker로 Mailpit 실행됨. `http://localhost:8025` 에서 수신 메일 확인.

### 운영: Gmail SMTP (무료)
1. Google 계정 → 보안 → 2단계 인증 활성화
2. 앱 비밀번호 생성 (16자리)

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=yourapp@gmail.com
MAIL_PASSWORD=your_16digit_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@polit.kr
MAIL_FROM_NAME="폴릿(Polit)"
```

### 운영: AWS SES (대용량 권장)
```dotenv
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=ap-northeast-2
```

### 운영: Resend (개발자 친화적, 무료 3,000건/월)
```dotenv
MAIL_MAILER=resend
RESEND_KEY=re_your_api_key_here
```

---

## 6. APP_KEY (Laravel 암호화 키)

외부 발급이 아닌 Laravel 내부 생성:

```bash
make artisan cmd="key:generate"
# 또는
php artisan key:generate
```

**절대 외부에 노출 금지**: JWT 토큰, 세션, 쿠키 암호화에 사용.

---

## 7. Sanctum 설정

외부 API 키 불필요. `.env`에서 도메인만 설정:

```dotenv
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,yourdomain.com,api.yourdomain.com
SESSION_DOMAIN=.yourdomain.com
```

---

## 8. 환경별 체크리스트

### 로컬 개발 (WSL2 + Docker)
- [ ] `.env` 파일 생성 (`cp .env.example .env`)
- [ ] `APP_KEY` 생성 (`php artisan key:generate`)
- [ ] 소셜 로그인: 개발용 앱 생성 또는 테스트 계정 등록
- [ ] KAKAO_CLIENT_ID / NAVER_CLIENT_ID / GOOGLE_CLIENT_ID 설정
- [ ] Mailpit 사용 (이미 Docker에 포함)
- [ ] REVERB 로컬 설정 (localhost:8080)

### 운영 서버 배포
- [ ] 모든 소셜 로그인 프로덕션 Redirect URI 등록
- [ ] APP_ENV=production, APP_DEBUG=false
- [ ] HTTPS 적용 후 Redirect URI 업데이트
- [ ] 이메일 서비스 설정 (SES 권장)
- [ ] REVERB 도메인/포트 설정
- [ ] SESSION_SECURE_COOKIE=true
- [ ] LOG_CHANNEL=daily

---

## 9. 빠른 설정 요약표

| 서비스 | 필수여부 | 발급처 | 로컬 대체 |
|---|---|---|---|
| 카카오 로그인 | 권장 | developers.kakao.com | 이메일 로그인으로 대체 |
| 네이버 로그인 | 권장 | developers.naver.com | 이메일 로그인으로 대체 |
| 구글 로그인 | 권장 | console.cloud.google.com | 이메일 로그인으로 대체 |
| SMTP 이메일 | 필수 | Gmail/SES/Resend | Mailpit (Docker 내장) |
| Laravel Reverb | 권장 | 자체호스팅 | Docker 내 reverb 서비스 |
| APP_KEY | 필수 | artisan key:generate | 동일 |
| PostgreSQL | 필수 | 자체 설치 | Docker 내 postgres |
| Redis | 필수 | 자체 설치 | Docker 내 redis |

모든 키는 `.env` 파일에만 저장하고 절대 Git에 커밋하지 마세요. `.gitignore`에 `.env`가 포함되어 있는지 반드시 확인하세요.
