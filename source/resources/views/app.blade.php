<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">

    {{-- ── 타이틀 (Inertia 페이지별 오버라이드, 기본값은 app.js 콜백 참조) ── --}}
    <title inertia>폴릿 — 보수·중도·진보 정치 커뮤니티</title>

    {{-- ── 기본 SEO 메타 (Inertia Head로 오버라이드 가능) ── --}}
    <meta name="description"        content="나의 정치 성향을 진단하고, 보수·중도·진보 진영 아지트와 전쟁터에서 자유롭게 토론하는 정치 커뮤니티">
    <meta name="keywords"           content="정치 성향 테스트, 정치 커뮤니티, 보수, 중도, 진보, 정치 토론, 아지트, 전쟁터, 폴릿">
    <meta name="author"             content="폴릿(Polit)">
    <meta name="robots"             content="index, follow">

    {{-- ── Open Graph ── --}}
    <meta property="og:site_name"   content="폴릿">
    <meta property="og:type"        content="website">
    <meta property="og:locale"      content="ko_KR">
    <meta property="og:title"       content="폴릿 — 보수·중도·진보 정치 커뮤니티">
    <meta property="og:description" content="나의 정치 성향을 진단하고, 보수·중도·진보 진영 아지트와 전쟁터에서 자유롭게 토론하는 정치 커뮤니티">
    <meta property="og:image"       content="{{ url('/og-image.png') }}">
    <meta property="og:url"         content="{{ url()->current() }}">

    {{-- ── Twitter / X Card ── --}}
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="폴릿 — 보수·중도·진보 정치 커뮤니티">
    <meta name="twitter:description" content="나의 정치 성향을 진단하고, 보수·중도·진보 진영 아지트와 전쟁터에서 자유롭게 토론하는 정치 커뮤니티">
    <meta name="twitter:image"       content="{{ url('/og-image.png') }}">
    {{-- 화면 깜빡임(FOUC) 방지: 페이지 렌더링 전 다크 클래스 즉시 적용 --}}
    <script>
        (function () {
            var t = localStorage.getItem('polit-theme') || 'auto';
            var isDark = false;
            if (t === 'dark') {
                isDark = true;
            } else if (t === 'auto') {
                var h = new Date().getHours();
                isDark = (h >= 18 || h < 6); // 18:00 ~ 05:59 자동 다크
            }
            if (isDark) document.documentElement.classList.add('dark');
        })();
    </script>
    @vite(['resources/js/app.js'])
    @inertiaHead
</head>
<body class="antialiased">
    @inertia
</body>
</html>
