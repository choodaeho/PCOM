# 폴릿(Polit) 로컬 개발 환경 구축 가이드
## WSL2 + Docker Desktop (Windows)

---

## 1. 사전 요구사항 확인

### Windows 버전
- Windows 10 버전 21H2 이상 또는 Windows 11
- PowerShell 관리자 권한 실행 가능

### 필요 소프트웨어
| 소프트웨어 | 버전 | 역할 |
|---|---|---|
| WSL2 (Ubuntu 22.04) | 2.x | Linux 실행 환경 |
| Docker Desktop | 4.x 이상 | 컨테이너 관리 |
| Git | 최신 | 소스 관리 |

---

## 2. WSL2 설치

### 2-1. PowerShell(관리자)에서 WSL2 활성화

```powershell
# WSL2 설치 (Ubuntu 22.04 기본 설치됨)
wsl --install

# 재부팅 후 버전 확인
wsl --version
```

### 2-2. Ubuntu 22.04 초기 설정

```bash
# Ubuntu 실행 후 사용자명/비밀번호 설정 (최초 1회)
# 이후 패키지 업데이트
sudo apt update && sudo apt upgrade -y
```

---

## 3. Docker Desktop 설치 및 WSL2 연동

1. [Docker Desktop 다운로드](https://www.docker.com/products/docker-desktop/)
2. 설치 시 **"Use WSL 2 based engine"** 옵션 체크
3. 설치 후 Docker Desktop → Settings → Resources → WSL Integration  
   → **Ubuntu-22.04** 토글 활성화

```bash
# WSL2 터미널에서 Docker 동작 확인
docker --version   # Docker version 26.x.x
docker compose version  # Docker Compose version v2.x.x
```

---

## 4. 프로젝트 클론 및 초기 설정

### 4-1. WSL2 내부 경로에 프로젝트 위치

> ⚠️ **중요**: 프로젝트는 반드시 WSL2 파일시스템(`~/` 또는 `/home/user/`) 안에 두세요.  
> Windows 경로(`/mnt/c/...`)에 두면 파일 I/O 속도가 10배 이상 느려집니다.

**폴더 구조 안내:**  
`D:\2026\pcom` 루트에는 문서·설계 자료가 있고, 실제 Laravel 소스는 `source/` 하위에 있습니다.

```
D:\2026\pcom\
├── source\          ← Laravel 소스 (복사 대상)
│   ├── app\
│   ├── resources\
│   ├── docker-compose.yml
│   ├── Makefile
│   └── ...
├── SETUP_WSL2.md    ← 개발 환경 가이드 (문서)
├── API_KEYS_GUIDE.md
├── design-main.html ← UI 시안 (문서)
└── ...              ← 기타 설계/전략 문서
```

```bash
cd ~
mkdir -p projects && cd projects

# Windows 경로의 Laravel 소스만 WSL2로 복사:
cp -r /mnt/d/2026/pcom/source ~/projects/pcom
cd ~/projects/pcom
```

### 4-2. 환경 변수 파일 설정

```bash
cp .env.example .env

# .env 편집 — 아래 항목 반드시 확인
nano .env
```

**주요 설정값 확인:**

```dotenv
APP_NAME="폴릿(Polit)"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Docker PostgreSQL 포트 충돌 방지
# 로컬에 PostgreSQL이 이미 5432로 실행 중이면 Docker는 5433으로 노출
DB_CONNECTION=pgsql
DB_HOST=postgres          # Docker 컨테이너 내부 hostname
DB_PORT=5432              # 컨테이너 내부 포트 (애플리케이션이 바라보는 포트)
DB_DATABASE=polit
DB_USERNAME=polit
DB_PASSWORD=secret

# Redis (Docker)
REDIS_HOST=redis
REDIS_PORT=6379

# Reverb (WebSocket)
REVERB_APP_KEY=polit-reverb-key
REVERB_APP_SECRET=polit-reverb-secret
REVERB_APP_ID=polit-001
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

---

## 5. 로컬 PostgreSQL 포트 충돌 대응

로컬에 PostgreSQL 5432가 이미 실행 중이면 `docker-compose.yml`의 포트 매핑을 변경합니다.

```bash
# docker-compose.yml 에서 postgres 서비스 포트를 5433으로 노출
# (이미 5433으로 설정되어 있다면 그대로 사용)
```

`docker-compose.yml` 확인:
```yaml
postgres:
  ports:
    - "5433:5432"   # 호스트 5433 → 컨테이너 5432
```

`.env`에서 DB_PORT는 **컨테이너 내부 포트(5432)** 를 그대로 사용합니다.  
애플리케이션은 Docker 네트워크 내부에서 `postgres:5432`로 직접 통신하기 때문입니다.

---

## 6. 전체 설치 실행 (make install)

```bash
# Makefile이 있는 프로젝트 루트에서 실행
make install
```

`make install` 내부 동작 순서:
1. `.env` 복사 (없는 경우 `.env.example` → `.env`)
2. `docker compose up -d --build` — 이미지 빌드 + 컨테이너 전체 기동
3. 20초 대기 (PostgreSQL healthcheck + PHP-FPM 초기화 완료 대기)
4. `composer install` — PHP 의존성 설치 (실행 중인 app 컨테이너 내부)
5. `php artisan key:generate` — APP_KEY 생성
6. `php artisan migrate --seed` — DB 스키마 생성 + 시드 데이터 투입
7. `npm install` — Node.js 패키지 설치
8. `npm run build` — 프론트엔드 정적 빌드 (CSR + SSR 동시)
9. `php artisan l5-swagger:generate` — Swagger 문서 생성

> **재설치 시**: `make down && docker volume rm polit_postgres_data` 후 다시 `make install`

### 설치 완료 후 접속 주소

| 서비스 | URL |
|---|---|
| 웹 앱 | http://localhost |
| API | http://localhost/api/v1/ |
| Swagger UI | http://localhost/api/documentation |
| Mailpit (메일 확인) | http://localhost:8025 |
| Vite 개발 서버 | http://localhost:5173 *(dev 모드 시)* |
| Redis Commander (--profile debug) | http://localhost:8081 |

---

## 6-1. 프론트엔드 개발 서버 (Vite + HMR)

폴릿은 **Inertia.js + Vue 3 + Vite**로 구성되어 있습니다.  
운영/배포 시에는 `npm run build`로 정적 파일을 생성하고, **개발 중에는 Vite 개발 서버를 별도로 실행**해야 핫 리로드(HMR)가 동작합니다.

### 6-1-1. Vite 개발 서버 시작

```bash
# 방법 1 (권장): Docker 컨테이너 내부에서 실행
make npm-dev
# 또는
docker compose exec app npm run dev
```

```bash
# 방법 2: WSL2 호스트에서 직접 실행 (Node.js 20이 WSL2에 설치된 경우)
cd ~/projects/pcom
npm install   # 최초 1회
npm run dev
```

> **어느 방법이 더 좋나요?**  
> - **방법 1 (Docker 내부)** — Node 버전 통일, 환경 일관성 보장. 권장.  
> - **방법 2 (WSL2 호스트)** — 파일 감지 속도가 약간 빠를 수 있음. Node 20 별도 설치 필요.

### 6-1-2. HMR(Hot Module Replacement) 설정

WSL2 + Docker 조합에서는 브라우저 ↔ Vite HMR 연결 시 `localhost`를 명시해야 합니다.  
`.env`에 아래 항목이 설정되어 있는지 확인하세요:

```dotenv
VITE_PORT=5173
VITE_HMR_HOST=localhost   # WSL2 환경에서는 localhost로 고정
```

`vite.config.js`는 이 값을 자동으로 읽어 HMR 소켓을 설정합니다.

### 6-1-3. docker-compose.yml 포트 확인

Vite 개발 서버 포트는 `app` 컨테이너에서 호스트로 노출되어 있습니다:

```yaml
app:
  ports:
    - '${VITE_PORT:-5173}:5173'   # Vite HMR 포트
```

### 6-1-4. 개발 서버 실행 후 확인

| 상태 | 확인 방법 |
|---|---|
| Vite 서버 동작 | `http://localhost:5173` 접속 시 Vite 응답 |
| HMR 동작 | Vue 파일 수정 시 브라우저 자동 리로드 |
| 앱 정상 렌더링 | `http://localhost` 접속 (Nginx → PHP → Inertia) |

> 개발 중에는 **두 서버를 동시에** 실행합니다:
> - `make up` — Nginx + PHP + DB (백그라운드)
> - `make npm-dev` — Vite 개발 서버 (포그라운드, 별도 터미널)

### 6-1-5. 프로덕션 빌드

```bash
# 정적 파일 생성 → public/build/ 에 저장됨
make npm-build
# 또는
docker compose exec app npm run build
```

빌드된 파일은 `public/build/`에 저장되며, `app.blade.php`의 `@vite` 디렉티브가 자동으로 참조합니다.

> ⚠️ **빌드 후 필수**: `npm run build`는 root 권한으로 실행되어 `storage/` 소유권이 바뀝니다.  
> **빌드 직후 반드시** 아래 권한 복구 명령을 실행하세요.  
> **`-u root` 옵션이 필수입니다** — 없으면 www-data가 root 소유 파일을 chown할 수 없어 조용히 실패합니다:
>
> ```bash
> docker compose exec -u root app bash -c \
>   "chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
>    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache"
> ```
>
> 이 명령을 빠뜨리거나 `-u root` 없이 실행하면 Laravel 로그 쓰기 실패 → 모든 페이지 500 오류가 발생합니다.

---

## 6-2. WebSocket(Reverb) 연결 확인

실시간 점수 띠(ScoreTicker), 알림 등은 **Laravel Reverb**를 통해 동작합니다.

```bash
# Reverb 컨테이너 로그 확인
docker compose logs -f reverb
```

정상 동작 시 아래 메시지가 출력됩니다:
```
Starting reverb server on 0.0.0.0:8080
```

`.env`에서 프론트엔드 클라이언트 연결 설정 확인:

```dotenv
REVERB_APP_KEY=polit-reverb-key
REVERB_APP_SECRET=polit-reverb-secret
REVERB_APP_ID=polit-001
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${APP_URL}"   # APP_URL과 동일
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

> `resources/js/echo.js`가 위 VITE_REVERB_* 변수를 읽어 Echo 클라이언트를 초기화합니다.

---

## 7. 일상 개발 명령어

### 7-0. 매일 개발 시작 순서 (필수)

> Docker Desktop을 새로 켰거나, PC를 재부팅한 경우 컨테이너가 내려간 상태입니다.  
> **반드시 아래 순서로 컨테이너를 먼저 올린 후** `docker compose exec` 명령을 사용하세요.

#### ✅ 방법 A — WSL2 터미널 (권장)

```bash
# 1. WSL2 터미널(Ubuntu) 열기
# 2. 프로젝트 폴더로 이동
cd ~/projects/pcom

# 3. 컨테이너 상태 확인
docker compose ps
# → 모든 서비스가 "running" 이면 바로 개발 시작 가능
# → "exited" 또는 목록이 비어있으면 아래 4번 실행

# 4. 컨테이너 시작 (처음 또는 재시작)
make up
# 또는
docker compose up -d

# 5. 정상 기동 확인 (app / postgres / nginx / redis / reverb)
docker compose ps

# 6. 필요 시 Artisan 시드 실행
docker compose exec app php artisan db:seed --class=AdminUserSeeder
docker compose exec app php artisan db:seed --class=TestAccountsSeeder
```

> **⚠️ 최초 세팅 또는 소스 동기화 후**: Google 2FA 패키지와 마이그레이션이 새로 추가된 경우 아래 명령을 추가로 실행하세요 (아래 7-1 참고).

#### ✅ 방법 B — Windows PowerShell (WSL2 없이)

WSL2 통합이 안 된 경우, Windows PowerShell에서 `docker compose` 를 직접 실행할 수 있습니다.  
단, `make` 명령은 사용 불가 — `docker compose` 명령으로 대체합니다.

```powershell
# 1. PowerShell에서 소스 폴더로 이동 (docker-compose.yml 위치)
cd D:\2026\pcom\source

# 2. 컨테이너 상태 확인
docker compose ps

# 3. 컨테이너 시작
docker compose up -d

# 4. 정상 기동 후 Artisan 명령 실행
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed --class=AdminUserSeeder
docker compose exec app php artisan db:seed --class=TestAccountsSeeder
```

> **WSL2 vs PowerShell 비교**
>
> | | WSL2 터미널 | Windows PowerShell |
> |---|---|---|
> | `make` 명령 | ✅ 사용 가능 | ❌ 불가 (`docker compose` 직접 입력) |
> | `docker compose` | ✅ (WSL Integration 필요) | ✅ 바로 사용 가능 |
> | 파일 I/O 속도 | ✅ 빠름 | ⚠️ 느림 (마운트 오버헤드) |
> | 권장 여부 | ✅ 권장 | 임시 방편 |

---

### 7-1. 관리자 2FA(Google Authenticator) 초기 설정 (최초 1회)

관리자 로그인은 **별도의 `/admin/login` 페이지**를 통해서만 가능하며,  
Google Authenticator OTP 2단계 인증이 필수입니다.

#### 패키지 설치 및 마이그레이션

```bash
# 1. Google 2FA TOTP 패키지 설치
docker compose exec app composer require pragmarx/google2fa

# 2. google2fa 컬럼 마이그레이션 (google2fa_secret, google2fa_enabled)
docker compose exec app php artisan migrate

# 3. 관리자 이메일이 이미 admin@polit.kr로 생성된 경우 hhpapa77@polit.kr로 업데이트
docker compose exec app php artisan tinker --execute="App\Models\User::where('is_admin',true)->update(['email'=>'hhpapa77@polit.kr'])"

# 4. (신규 환경) 관리자 시드 실행
docker compose exec app php artisan db:seed --class=AdminUserSeeder
```

#### 최초 관리자 로그인 절차

1. 브라우저에서 **`http://localhost/admin/login`** 접속  
   *(일반 `/login` 페이지로는 관리자 계정 로그인 불가)*
2. 이메일: `hhpapa77@polit.kr` / 비밀번호: `itweb8335#` 입력 → **다음 단계**
3. **최초 1회**: QR 코드 화면이 표시됨
   - 스마트폰에서 **Google Authenticator** 앱 실행
   - `+` → **QR 코드 스캔** 선택 → 화면의 QR 코드 스캔
   - 앱에 표시된 6자리 숫자 입력 → **등록 완료 및 로그인**
4. **이후 로그인부터**: OTP 입력 화면만 표시 (QR 코드 설정 불필요)

> **앱 설치 링크**  
> - iOS: [App Store](https://apps.apple.com/app/google-authenticator/id388497605)  
> - Android: [Play Store](https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2)

#### 2FA 관련 주의사항

| 상황 | 조치 |
|------|------|
| OTP 코드 오류 | 스마트폰 시계를 자동 설정으로 변경 (시간 동기화 필요) |
| 앱 삭제 / 기기 교체 | DB에서 `google2fa_enabled = false`로 초기화 후 재등록 |
| 비밀번호만 바꾸고 싶을 때 | 관리자 패널 `/admin/users` 에서 직접 변경 |

```bash
# 2FA 초기화 (긴급 재등록 시)
docker compose exec app php artisan tinker --execute="App\Models\User::where('is_admin',true)->update(['google2fa_enabled'=>false,'google2fa_secret'=>null])"
```

---

#### Docker Desktop WSL Integration 활성화 (최초 1회)

WSL2 터미널에서 `docker` 명령이 안 보이는 경우:

1. Docker Desktop 실행 → ⚙️ **Settings**
2. **Resources → WSL Integration**
3. **"Enable integration with my default WSL distro"** 토글 **ON**
4. 사용 중인 배포판(Ubuntu-22.04 등) 토글 **ON**
5. **Apply & Restart** 클릭
6. WSL2 터미널 **닫고 다시 열기**

---

### 소스 동기화 (Windows → WSL2)

Cowork(Claude)가 `D:\2026\pcom\source`의 파일을 수정하면 WSL2 프로젝트에 반영해야 합니다.  
`make sync`는 rsync를 이용해 **변경된 파일만** 골라서 복사합니다.

```bash
cd ~/projects/pcom

# 소스 동기화 (변경 파일만, vendor/node_modules/.env 제외)
make sync

# Dockerfile 또는 start-container 변경이 포함된 경우 → 동기화 + 이미지 재빌드
make sync-rebuild
```

**동기화 제외 항목** (WSL2 로컬 상태 보존):

| 제외 경로 | 이유 |
|---|---|
| `.env` | 로컬 환경 설정 보호 (비밀번호 등) |
| `vendor/` | `composer install`로 컨테이너 내부 설치 |
| `node_modules/` | `npm install`로 컨테이너 내부 설치 |
| `storage/logs/` | 런타임 로그 |
| `public/build/`, `bootstrap/ssr/` | 빌드 산출물 |

> **드라이브가 D: 가 아닌 경우:**
> ```bash
> make sync WIN_SRC=/mnt/c/other/path/source
> ```

> **최초 1회** — `make sync` 자체가 Makefile에 있으므로, Makefile부터 수동 복사 후 이후는 `make sync` 사용:
> ```bash
> cp /mnt/d/2026/pcom/source/Makefile ~/projects/pcom/Makefile
> make sync
> ```

---

### 재설치 / 초기화

```bash
# 볼륨(PostgreSQL 데이터) 포함 완전 초기화 후 재설치
# → DB 인증 오류, 마이그레이션 꼬임, 환경 초기화 시 사용
make reinstall
```

`make reinstall` 동작 순서:
1. 확인 프롬프트 (y 입력 필요)
2. `docker compose down -v` — 컨테이너 + 볼륨 완전 삭제
3. `.env.example` → `.env` 강제 재생성 (기존 `.env` 덮어쓰기)
4. `make install` 전체 재실행

> ⚠️ `.env`가 덮어써지므로 비밀번호 등 커스텀 설정은 재설치 후 다시 입력해야 합니다.

---

### 기본 명령어

```bash
# 컨테이너 시작/정지
make up
make down

# 로그 확인
make logs          # 전체 로그
make logs-app      # PHP 앱 로그만
make logs-nginx    # Nginx 로그만

# Artisan 명령
make artisan CMD="route:list"
make artisan CMD="queue:work"

# DB 초기화 (볼륨 유지, 데이터만 리셋)
make fresh         # migrate:fresh --seed

# 진영 점수 수동 집계
make artisan CMD="polit:aggregate-daily"

# Swagger 재생성
make swagger

# 코드 스타일 검사 (PSR-12)
make pint-check
make phpstan

# 테스트 실행
make test
make test-coverage
```

### 프론트엔드 명령어

```bash
# NPM 패키지 설치 (처음 1회 또는 package.json 변경 후)
make npm-install

# Vite 개발 서버 시작 (HMR 포함, 별도 터미널에서 실행)
make npm-dev

# 프로덕션 빌드 (배포 전)
make npm-build

# ⚠️ npm-build 후 항상 실행 — -u root 필수, storage 소유권 복구
docker compose exec -u root app bash -c \
  "chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
   chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache"
```

> **개발 중 권장 워크플로우:**
> ```bash
> # 터미널 1 — 백엔드 컨테이너
> make up
>
> # 터미널 2 — 프론트엔드 개발 서버
> make npm-dev
> ```
> 이후 `http://localhost` 으로 접속하면 Vue HMR이 자동으로 동작합니다.

---

## 8. 자주 발생하는 문제

### Q: `service "app" is not running`

`docker compose exec` 는 **이미 실행 중인 컨테이너**에 명령을 보내는 것입니다.  
컨테이너가 내려간 상태에서 실행하면 이 오류가 납니다.

```bash
# ① 먼저 컨테이너 상태 확인
docker compose ps

# ② 컨테이너 시작 (WSL2에서)
make up
# 또는 PowerShell에서
docker compose up -d   # (D:\2026\pcom\source 폴더에서 실행)

# ③ 컨테이너가 완전히 뜬 후 (약 10초) 원하는 명령 실행
docker compose exec app php artisan db:seed --class=AdminUserSeeder
```

> ⚠️ Docker Desktop이 종료되어 있거나 PC 재부팅 후에는 컨테이너가 항상 내려간 상태입니다.  
> 개발 시작 전 **항상 `make up` (또는 `docker compose up -d`) 을 먼저 실행**하는 습관을 들이세요.

---

### Q: `The command 'docker' could not be found in this WSL 2 distro`

Docker Desktop의 WSL Integration이 비활성화된 상태입니다.

```
Docker Desktop → Settings → Resources → WSL Integration
→ Ubuntu-22.04 토글 ON → Apply & Restart
→ WSL2 터미널 재시작 후 재시도
```

자세한 내용은 **7-0. 매일 개발 시작 순서** 항목을 참고하세요.

---

### Q: `docker: Cannot connect to the Docker daemon`
```bash
# Docker Desktop이 실행 중인지 확인
# WSL Integration 설정 확인 (3번 항목)
```

### Q: PostgreSQL 포트 5432 충돌
```bash
# docker-compose.yml에서 호스트 포트를 5433으로 변경
# 또는 로컬 PostgreSQL 서비스 중지
sudo service postgresql stop   # Ubuntu/WSL2
```

### Q: `Permission denied` — storage 디렉토리

`npm run build` 는 컨테이너 내부에서 root 권한으로 실행되어 `storage/` 소유권이 root로 바뀝니다.  
**빌드 후 반드시 권한을 복구**해야 하며, 일반 exec로 chown 시 www-data가 root 소유 파일을 변경할 수 없어 실패합니다. 반드시 **`-u root`** 옵션을 사용하세요.

#### ① 현재 소유자 진단

```bash
docker compose exec app ls -la storage/logs/
docker compose exec app ls -la storage/framework/views/ | head -5
```

#### ② 표준 복구 (`-u root` 필수)

```bash
docker compose exec -u root app bash -c \
  "chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
   chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache"
```

#### ③ Windows 바인드 마운트 환경에서 chown이 무효인 경우

소스가 `D:\2026\pcom\source`(Windows NTFS)에서 직접 마운트된 경우 `chown` 자체가 적용되지 않습니다.  
이 경우 777로 모든 사용자에게 쓰기 권한을 줍니다:

```bash
docker compose exec -u root app chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache
```

#### ④ 컴파일 캐시까지 꼬인 경우 — 캐시 완전 초기화

```bash
docker compose exec -u root app bash -c \
  "rm -rf /var/www/html/storage/framework/views/* && \
   rm -f /var/www/html/storage/logs/laravel.log && \
   touch /var/www/html/storage/logs/laravel.log && \
   chown -R www-data:www-data /var/www/html/storage && \
   chmod -R 775 /var/www/html/storage"
```

#### ⑤ 스토리지 심볼릭 링크 (최초 1회)

```bash
docker compose exec app php artisan storage:link
```

> **정상 복구 확인**: 브라우저 새로고침 후 오류가 사라지면 완료.  
> 여전히 오류가 나면 ①번 `ls -la` 결과를 확인해 소유자/권한을 직접 파악하세요.

### Q: Composer 없음
```bash
# WSL2 내부에 Composer 설치
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Q: make 명령어가 없음
```bash
sudo apt install make -y
```

### Q: `make install` 후 http://localhost 접속 시 502 Bad Gateway

**원인**: PHP-FPM이 Unix 소켓(`/run/php/php8.2-fpm.sock`)으로 listen하는데  
Nginx가 TCP `app:9000`으로 연결을 시도하면 연결이 거부됩니다.  
→ `docker/php/Dockerfile`에서 PHP-FPM pool의 `listen` 값을 9000으로 수정 후 이미지를 **반드시 다시 빌드**해야 합니다.

```bash
# 이미지 재빌드 + 재시작 (소스 변경 시 항상 이렇게)
make rebuild

# 빌드 후 컨테이너 상태 확인
make ps

# app 컨테이너 로그에서 PHP-FPM 기동 메시지 확인
make logs-app
# 정상이면: "NOTICE: fpm is running, pid 1" 메시지 출력
```

이미 `make install`을 한 번 실행했다면 이미지가 구버전으로 캐시되어 있습니다.  
`make rebuild` 후 다시 `make install` 또는 아래 순서로 실행하세요:

```bash
make down
make rebuild
make fresh          # migrate:fresh --seed
make npm-install
make npm-build
```

---

### Q: `make npm-dev` 실행 시 `sh: 1: vite: not found`

**원인**: `node_modules`가 컨테이너 내부에 설치되지 않은 상태입니다.  
`make install` 도중 app 컨테이너가 502 상태(PHP-FPM 미기동)로 인해  
`npm install` 단계가 실패했을 가능성이 높습니다.

```bash
# 1) 위의 502 문제를 먼저 해결 (make rebuild)
# 2) npm 패키지 다시 설치
make npm-install

# 설치 확인 (node_modules/.bin/vite 존재 여부)
docker compose exec app ls node_modules/.bin/vite

# 3) 개발 서버 시작
make npm-dev
```

---

### Q: 화면이 흰 페이지 / 빈 화면으로 보임 (Vite 빌드 없음)
```bash
# 프론트엔드 빌드가 한 번도 실행된 적 없거나, public/build/가 없는 경우
make npm-build

# 개발 중이라면 Vite 개발 서버를 함께 실행해야 합니다
make npm-dev   # 별도 터미널
```

### Q: HMR이 동작하지 않음 (파일 수정해도 브라우저 갱신 안 됨)
```bash
# .env에서 HMR 호스트 확인
VITE_HMR_HOST=localhost

# vite.config.js server.hmr.host 값이 VITE_HMR_HOST를 읽는지 확인
# 브라우저 개발자 도구 → Network 탭 → WS 연결 (localhost:5173) 상태 확인
```

### Q: `npm run dev` 실행 시 ENOSPC 오류 (inotify 한계 초과)
```bash
# WSL2 호스트에서 실행 시 파일 감시 한계 증설
echo "fs.inotify.max_user_watches=524288" | sudo tee -a /etc/sysctl.conf
sudo sysctl -p
```

### Q: `VITE_REVERB_APP_KEY is not defined` 오류
```bash
# .env에 VITE_REVERB_* 변수가 없거나 Vite 캐시 문제
# .env 파일 확인 후 Vite 재시작
make down && make up
make npm-dev
```

---

### Q: 관리자 로그인 시 `Class "PragmaRX\Google2FA\Google2FA" not found`

`pragmarx/google2fa` 패키지가 설치되지 않은 상태입니다.

```bash
docker compose exec app composer require pragmarx/google2fa
```

---

### Q: 관리자가 `/login`(일반 로그인)으로 접속하면?

"관리자 계정은 관리자 전용 로그인 페이지를 이용해주세요." 오류가 표시되며 로그인이 차단됩니다.  
관리자는 반드시 `/admin/login` 경로로 접속해야 합니다.

---

### Q: 2FA OTP 코드가 계속 틀리다고 나옴

Google Authenticator의 TOTP는 **스마트폰 시계**와 서버 시계가 동기화되어야 합니다.

1. 스마트폰 → 설정 → 날짜 및 시간 → **자동으로 설정** 활성화
2. Google Authenticator 앱 → 메뉴(⋮) → **시간 수정** → 코드용 동기화
3. 위 조치 후에도 안 되면 서버 시간 확인:
```bash
docker compose exec app date
```

---

### Q: 2FA 기기를 교체했거나 앱을 삭제해서 OTP를 받을 수 없음

DB에서 2FA를 초기화한 후 새 기기로 재등록합니다.

```bash
docker compose exec app php artisan tinker --execute="\
App\Models\User::where('is_admin',true)\
->update(['google2fa_enabled'=>false,'google2fa_secret'=>null])"
```

초기화 후 `/admin/login` → 이메일+비밀번호 입력 → QR 코드 재등록 화면이 표시됩니다.

---

## 9. 개발 환경 vs 운영 환경

| | 로컬 (WSL2 + Docker) | 운영 (Docker) |
|---|---|---|
| PHP 실행 | Docker 컨테이너 | Docker 컨테이너 |
| PostgreSQL | Docker (포트 5433 노출) | Docker (내부 only) |
| Redis | Docker | Docker |
| Nginx | Docker | Docker + 외부 LB |
| Xdebug | 활성화 가능 | 비활성화 |
| Opcache | 비활성화 | 활성화 |
| `.env` | `APP_ENV=local` | `APP_ENV=production` |

로컬과 운영 환경 모두 **같은 Docker 이미지**를 사용하기 때문에 "내 컴퓨터에서는 되는데 서버에서는 안 된다" 현상이 없습니다.

---

*최종 수정: 2026-06-17 (Permission denied Q&A 전면 개선 — `-u root` 필수, Windows 마운트 대응, 캐시 초기화 단계 추가)*
