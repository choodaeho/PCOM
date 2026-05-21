-- ─────────────────────────────────────────────────────────────────────────
-- Polit PostgreSQL 초기화 스크립트
-- docker-entrypoint-initdb.d/ 에서 DB 최초 생성 시 자동 실행
-- ─────────────────────────────────────────────────────────────────────────

-- 한국어 FTS 및 pg_trgm (유사 검색) 확장
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE EXTENSION IF NOT EXISTS btree_gin;
CREATE EXTENSION IF NOT EXISTS btree_gist;

-- UUID 생성 (향후 사용 대비)
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- 테스트 DB (CI/CD용)
-- CREATE DATABASE polit_test;
-- GRANT ALL PRIVILEGES ON DATABASE polit_test TO polit;

-- pg_trgm 인덱스 기본 임계값 조정 (한국어 2-gram 검색 최적화)
ALTER SYSTEM SET pg_trgm.similarity_threshold = 0.1;

-- 최대 연결 수 설정 (Sail 기본값 100 → 200)
-- ALTER SYSTEM SET max_connections = 200;

SELECT pg_reload_conf();
