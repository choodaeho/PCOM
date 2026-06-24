<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <title inertia>폴릿(Polit)</title>
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
