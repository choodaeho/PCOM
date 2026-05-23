<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScoreWeight;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScoreWeightController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/ScoreWeights/Index', [
            'weights' => ScoreWeight::orderBy('action_type')->get(),
        ]);
    }

    public function update(Request $request, ScoreWeight $scoreWeight): RedirectResponse
    {
        $validated = $request->validate([
            'weight'      => ['required', 'numeric', 'min:-100', 'max:100'],
            'description' => ['nullable', 'string', 'max:200'],
            'is_active'   => ['boolean'],
        ]);

        $scoreWeight->update($validated);
        ScoreWeight::invalidateCache();

        return back()->with('success', '가중치를 수정했습니다. (캐시 초기화 완료)');
    }
}
