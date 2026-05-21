<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PollController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Polls/Index', [
            'polls' => Poll::withTrashed()->latest()->paginate(20),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Polls/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'     => ['required', 'string', 'max:200'],
            'options'   => ['required', 'array', 'min:2'],
            'options.*' => ['required', 'string', 'max:100'],
            'starts_at' => ['nullable', 'date'],
            'ends_at'   => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
        ]);

        $options = array_values(array_map(
            fn ($label, $i) => ['id' => $i + 1, 'label' => $label, 'vote_count' => 0],
            $validated['options'],
            array_keys($validated['options'])
        ));

        Poll::create([...$validated, 'options' => $options, 'total_vote_count' => 0]);

        return redirect()->route('admin.polls.index')->with('success', '투표를 생성했습니다.');
    }

    public function close(Poll $poll): RedirectResponse
    {
        $poll->update(['is_active' => false, 'ends_at' => now()]);

        return back()->with('success', '투표를 종료했습니다.');
    }

    public function destroy(Poll $poll): RedirectResponse
    {
        $poll->delete();

        return back()->with('success', '투표를 삭제했습니다.');
    }
}
