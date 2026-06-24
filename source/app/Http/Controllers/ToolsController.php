<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class ToolsController extends Controller
{
    /**
     * 폴릿 툴박스 페이지.
     *
     * 로또번호생성기, 오늘의 운세 등 비로그인 사용자도 이용 가능한 유입 기능 모음.
     */
    public function index(): Response
    {
        return Inertia::render('Tools/Index');
    }
}
