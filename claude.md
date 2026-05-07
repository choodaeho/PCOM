프로젝트명: 폴릿(Polit) - Politics의 약자

정치 성향별 커뮤니티 및 토론 플랫폼



1\. 프로젝트 개요

사용자의 정치적 성향(보수, 중도, 진보)을 진단하고, 각 진영만의 독립된 커뮤니티 공간(아지트)과 전 진영이 모여 논쟁하는 공용 공간(전쟁터)을 제공하는 웹 플랫폼입니다.



2\. 핵심 비즈니스 로직

성향 진단 및 진입 제어

성향 테스트: 가입 시 필수 설문을 통해 성향 점수 산출 및 진영 할당.



아지트(Azit): 본인 진영의 게시판만 접근/열람 가능 (교차 진입 불가).



전쟁터(Battleground): 모든 진영이 참여하는 토론장. 게시글 작성 시 작성자의 진영 아이콘 표시.



데이터 분석 및 스코어링

진영 점수(Daily Stats): 매일 각 진영의 게시물 수, 추천 수, 활동 지표를 집계하여 실시간/일간 진영별 영향력 지수(Score) 산출 및 대시보드 시각화.



3\. 기술 스택 (Tech Stack)

Language/Framework: PHP 8.2+ / Laravel 11.x



Database: PostgreSQL 16



Web Server: Nginx



Infrastructure: Docker (Laravel Sail 권장)



4\. 데이터베이스 설계 (PostgreSQL)

주요 테이블 구조

users



id, email, password, political\_type (보수/중도/진보), test\_score



political\_tests



id, question, options (JSONB), weight



posts



id, user\_id, category (azit/battle), faction (작성 당시 진영), title, content



factions\_daily\_stats



id, faction\_type, date, post\_count, vote\_count, total\_score



5\. 상세 기능 및 활성화 전략 (Ideation)

활성화 및 재미 요소 추가

진영별 랭킹 시스템: 활동량이 높은 유저에게 '진영 대변인', '행동대장' 등의 칭호 부여.



실시간 투표(The Poll): 전쟁터 상단에 실시간 시사 이슈 투표를 배치하여 진영 간 표 차이를 시각화.



용어 필터링 및 매너 점수: 정치 커뮤니티 특성상 혐오 표현 방지를 위한 AI 기반 욕설 필터링 및 신고 누적 시 진영 점수 감점.



비즈니스 모델/확장성

여론 조사 데이터 판매: 정제된 성향별 데이터 통계를 기반으로 한 인사이틱 리포트 제공.



진영별 배너 광고: 각 진영 아지트의 특성에 맞는 맞춤형 광고 노출.



6\. 개발 로드맵 (Claude 업무 할당용)

Phase 1: 인프라 및 인증 (Foundations)

\[ ] Laravel 프로젝트 초기 설정 및 PostgreSQL 연결



\[ ] 성향 테스트 로직 및 회원가입 Flow 개발



\[ ] 진영별 접근 제한 미들웨어(Middleware) 구현



Phase 2: 커뮤니티 핵심 (Core)

\[ ] 아지트 및 전쟁터 CRUD API 개발



\[ ] Polymorphic 관계를 활용한 추천/비추천 시스템



\[ ] 진영별 점수 집계를 위한 스케줄러(Laravel Task Scheduling)



Phase 3: 분석 및 UI/UX (Final)

\[ ] 진영별 통계 대시보드(Chart.js 연동) 개발



\[ ] 실시간 토론 알림 서비스 (Pusher/Reverb)



7\. Claude 협업 가이드

Code Style: PSR-12 준수, Type Hinting 필수 적용.



Documentation: 모든 API는 Swagger 또는 OpenAPI 규격으로 정리.



Query Optimization: PostgreSQL의 JSONB 타입을 활용한 설문 데이터 관리 및 인덱싱 최적화 제안 요망.

