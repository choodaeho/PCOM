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

```bash
cd ~
mkdir -p projects && cd projects

# 기존 Windows 경로의 소스를 WSL2로 복사하는 경우:
cp -r /mnt/d/2026/pcom ~/projects/pcom
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

`make install` 내부 동작:
1. `composer install` — PHP 의존성 설치
2. `.env` 복사 (없는 경우)
3. `php artisan key:generate` — APP_KEY 생성
4. `docker compose up -d` — 컨테이너 전체 기동
5. 10초 대기 (PostgreSQL 초기화 완료 대기)
6. `php artisan migrate:fresh --seed` — DB 스키마 생성 + 시드 데이터 투입
7. `php artisan l5-swagger:generate` — Swagger 문서 생성

### 설치 완료 후 접속 주소

| 서비스 | URL |
|---|---|
| 웹 앱 | http://localhost |
| API | http://localhost/api/v1/ |
| Swagger UI | http://localhost/api/documentation |
| Mailpit (메일 확인) | http://localhost:8025 |
| Redis Commander (--profile debug) | http://localhost:8081 |

---

## 7. 일상 개발 명령어

```bash
# 컨테이너 시작/정지
make up
make down

# 로그 확인
make logs          # 전체 로그
make logs-app      # PHP 앱 로그만
make logs-nginx    # Nginx 로그만

# Artisan 명령
make artisan cmd="route:list"
make artisan cmd="queue:work"

# DB 초기화
make fresh         # migrate:fresh --seed

# 진영 점수 수동 집계
make artisan cmd="polit:aggregate-daily"

# Swagger 재생성
make swagger

# 코드 스타일 검사 (PSR-12)
make pint-check
make phpstan

# 테스트 실행
make test
make test-coverage
```

---

## 8. 자주 발생하는 문제

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
```bash
make artisan cmd="storage:link"
chmod -R 775 storage bootstrap/cache
```

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

*최종 수정: 2026-05-21*
