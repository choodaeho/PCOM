# ─────────────────────────────────────────────────────────────────────────────
# 폴릿(Polit) Makefile — Docker/Sail 공통 커맨드 단축키
#
# 사용법:
#   make up          # 컨테이너 시작
#   make fresh       # DB 초기화 후 마이그레이션 + 시드
#   make help        # 전체 명령어 목록
# ─────────────────────────────────────────────────────────────────────────────

SAIL = ./vendor/bin/sail
DC   = docker compose

# ─────────────────────────────────────────────
# 컨테이너 관리
# ─────────────────────────────────────────────

.PHONY: up
up: ## 모든 컨테이너 백그라운드 시작
	$(DC) up -d

.PHONY: up-debug
up-debug: ## Redis Commander 포함 디버그 모드 시작
	$(DC) --profile debug up -d

.PHONY: down
down: ## 컨테이너 종료
	$(DC) down

.PHONY: restart
restart: down up ## 재시작

.PHONY: rebuild
rebuild: ## 이미지 다시 빌드 후 시작
	$(DC) build --no-cache && $(DC) up -d

.PHONY: ps
ps: ## 실행 중인 컨테이너 목록
	$(DC) ps

.PHONY: logs
logs: ## 전체 로그 스트리밍
	$(DC) logs -f

.PHONY: logs-app
logs-app: ## app 컨테이너 로그
	$(DC) logs -f app

.PHONY: logs-nginx
logs-nginx: ## nginx 로그
	$(DC) logs -f nginx

# ─────────────────────────────────────────────
# Laravel Artisan
# ─────────────────────────────────────────────

.PHONY: artisan
artisan: ## artisan 커맨드 실행 (make artisan CMD="route:list")
	$(DC) exec app php artisan $(CMD)

.PHONY: tinker
tinker: ## Laravel Tinker REPL
	$(DC) exec app php artisan tinker

.PHONY: migrate
migrate: ## 마이그레이션 실행
	$(DC) exec app php artisan migrate

.PHONY: migrate-fresh
migrate-fresh: ## DB 초기화 + 마이그레이션
	$(DC) exec app php artisan migrate:fresh

.PHONY: seed
seed: ## 시드 데이터 삽입
	$(DC) exec app php artisan db:seed

.PHONY: fresh
fresh: ## DB 초기화 + 마이그레이션 + 시드 한 번에
	$(DC) exec app php artisan migrate:fresh --seed

.PHONY: routes
routes: ## 등록된 라우트 목록
	$(DC) exec app php artisan route:list --path=api

.PHONY: cache-clear
cache-clear: ## 모든 캐시 초기화
	$(DC) exec app php artisan cache:clear
	$(DC) exec app php artisan config:clear
	$(DC) exec app php artisan route:clear
	$(DC) exec app php artisan view:clear

.PHONY: swagger
swagger: ## Swagger 문서 재생성
	$(DC) exec app php artisan l5-swagger:generate

.PHONY: schedule
schedule: ## 스케줄러 수동 실행 (테스트용)
	$(DC) exec app php artisan schedule:run

.PHONY: queue-work
queue-work: ## 큐 워커 직접 실행 (컨테이너 외부)
	$(DC) exec app php artisan queue:work --tries=3

# ─────────────────────────────────────────────
# 코드 품질
# ─────────────────────────────────────────────

.PHONY: test
test: ## PHPUnit 테스트 전체 실행
	$(DC) exec app php artisan test

.PHONY: test-coverage
test-coverage: ## 커버리지 리포트 생성 (storage/coverage/)
	$(DC) exec app php artisan test --coverage --min=70

.PHONY: pint
pint: ## Laravel Pint (PSR-12 코드 포맷)
	$(DC) exec app ./vendor/bin/pint

.PHONY: pint-check
pint-check: ## Pint 검사 (수정 없이)
	$(DC) exec app ./vendor/bin/pint --test

.PHONY: phpstan
phpstan: ## PHPStan 정적 분석
	$(DC) exec app ./vendor/bin/phpstan analyse --memory-limit=512M

# ─────────────────────────────────────────────
# Windows → WSL2 소스 동기화
# ─────────────────────────────────────────────

# Windows 소스 경로 (WSL2에서 마운트되는 경로)
WIN_SRC ?= /mnt/d/2026/pcom/source

.PHONY: sync
sync: ## Windows(D:/2026/pcom/source) → WSL2 소스 동기화 (변경 파일만)
	@command -v rsync >/dev/null 2>&1 || { echo "❌ rsync가 없습니다: sudo apt install rsync -y"; exit 1; }
	@echo "🔄 동기화 중: $(WIN_SRC) → $(CURDIR)"
	@rsync -rltu \
		--no-perms --no-owner --no-group \
		--omit-dir-times \
		--exclude='.env' \
		--exclude='vendor/' \
		--exclude='node_modules/' \
		--exclude='.git/' \
		--exclude='storage/logs/*.log' \
		--exclude='storage/framework/cache/' \
		--exclude='storage/framework/sessions/' \
		--exclude='storage/framework/views/' \
		--exclude='bootstrap/cache/' \
		--exclude='public/build/' \
		--exclude='bootstrap/ssr/' \
		--itemize-changes \
		$(WIN_SRC)/ $(CURDIR)/
	@chmod +x $(CURDIR)/docker/php/start-container 2>/dev/null || true
	@echo ""
	@echo "✅ 동기화 완료"
	@echo "💡 Dockerfile 변경이 포함된 경우: make rebuild"

.PHONY: sync-rebuild
sync-rebuild: sync ## 동기화 + 이미지 재빌드 (Dockerfile 변경 시)
	@echo "🐳 이미지 재빌드..."
	$(DC) build app
	$(DC) up -d app ssr

# ─────────────────────────────────────────────
# 패키지 관리
# ─────────────────────────────────────────────

.PHONY: composer-install
composer-install: ## Composer 패키지 설치
	$(DC) exec app composer install

.PHONY: composer-update
composer-update: ## Composer 패키지 업데이트
	$(DC) exec app composer update

.PHONY: npm-install
npm-install: ## NPM 패키지 설치
	$(DC) exec app npm install

.PHONY: npm-dev
npm-dev: ## Vite 개발 서버 시작 (HMR 포함)
	$(DC) exec app npm run dev

.PHONY: npm-build
npm-build: ## 프론트엔드 빌드 (CSR + SSR 동시)
	$(DC) exec app npm run build

.PHONY: ssr-start
ssr-start: ## Inertia SSR 서버 컨테이너 시작
	$(DC) up -d ssr

.PHONY: ssr-stop
ssr-stop: ## Inertia SSR 서버 컨테이너 중지
	$(DC) stop ssr

.PHONY: ssr-logs
ssr-logs: ## SSR 서버 로그 스트리밍
	$(DC) logs -f ssr

.PHONY: ssr-restart
ssr-restart: ## SSR 서버 재시작 (빌드 후 코드 반영 시)
	$(DC) restart ssr

# ─────────────────────────────────────────────
# 초기 설치
# ─────────────────────────────────────────────

.PHONY: reinstall
reinstall: ## 볼륨 포함 완전 초기화 후 재설치 (인증 오류·DB 꼬임 시 사용)
	@echo "⚠️  데이터 볼륨을 포함한 완전 초기화를 진행합니다."
	@echo "   PostgreSQL 데이터가 모두 삭제됩니다."
	@read -p "   계속하시겠습니까? [y/N] " confirm; \
	  [ "$$confirm" = "y" ] || [ "$$confirm" = "Y" ] || { echo "취소"; exit 1; }
	$(DC) down -v --remove-orphans
	@echo "🔑 .env 파일 강제 재생성..."
	cp .env.example .env
	$(MAKE) install

.PHONY: install
install: ## 프로젝트 최초 설치 (clone 후 1회 실행)
	@echo "🔑 .env 파일 생성..."
	cp -n .env.example .env || true
	@echo "🐳 이미지 빌드 및 컨테이너 시작..."
	$(DC) up -d --build
	@echo "⏳ PostgreSQL 인증 준비 대기 중 (최대 60초)..."
	@set -e; \
	DB_USER=$$(grep '^DB_USERNAME' .env | cut -d= -f2); \
	DB_PASS=$$(grep '^DB_PASSWORD' .env | cut -d= -f2); \
	DB_NAME=$$(grep '^DB_DATABASE' .env | cut -d= -f2); \
	for i in $$(seq 1 30); do \
	  if $(DC) exec -T postgres psql -U "$$DB_USER" -d "$$DB_NAME" -c "SELECT 1" > /dev/null 2>&1; then \
	    echo "✅ PostgreSQL 준비 완료 ($${i}회 시도)"; break; \
	  fi; \
	  echo "  대기 중... ($${i}/30)"; sleep 2; \
	  if [ $$i -eq 30 ]; then echo "❌ PostgreSQL 연결 시간 초과"; exit 1; fi; \
	done
	@echo "📦 Composer 패키지 설치..."
	$(DC) exec app composer install --no-interaction --prefer-dist
	@echo "🗝️  APP_KEY 생성..."
	$(DC) exec app php artisan key:generate
	@echo "🗄️  마이그레이션 + 시드..."
	$(DC) exec app php artisan migrate --seed
	@echo "🎨 NPM 패키지 설치..."
	$(DC) exec app npm install
	@echo "🏗️  프론트엔드 빌드..."
	$(DC) exec app npm run build
	@echo "📝 Swagger 문서 생성..."
	$(DC) exec app php artisan l5-swagger:generate
	@echo ""
	@echo "✅ 설치 완료!"
	@echo "   🌐 앱:        http://localhost"
	@echo "   📧 메일:      http://localhost:8025"
	@echo "   📖 API 문서:  http://localhost/api/documentation"
	@echo ""
	@echo "💡 개발 서버 시작: make npm-dev"

# ─────────────────────────────────────────────
# 셸 접속
# ─────────────────────────────────────────────

.PHONY: shell
shell: ## app 컨테이너 bash 셸
	$(DC) exec app bash

.PHONY: shell-postgres
shell-postgres: ## PostgreSQL psql 셸
	$(DC) exec postgres psql -U polit -d polit

.PHONY: shell-redis
shell-redis: ## Redis CLI
	$(DC) exec redis redis-cli -a secret

# ─────────────────────────────────────────────
# 도움말
# ─────────────────────────────────────────────

.PHONY: help
help: ## 사용 가능한 명령어 목록
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
	  awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}' | \
	  sort

.DEFAULT_GOAL := help
