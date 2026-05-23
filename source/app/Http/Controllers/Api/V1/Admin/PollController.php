<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\Poll;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PollController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Poll::withTrashed()->latest()->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:1000'],
            'options'     => ['required', 'array', 'min:2', 'max:5'],
            'options.*.label' => ['required', 'string', 'max:100'],
            'starts_at'   => ['nullable', 'date'],
            'ends_at'     => ['nullable', 'date', 'after:starts_at'],
        ]);

        // options에 id와 vote_count 추가
        $options = collect($validated['options'])->values()->map(fn ($o, $i) => [
            'id'         => $i + 1,
            'label'      => $o['label'],
            'vote_count' => 0,
        ])->toArray();

        $poll = Poll::create([
            ...$validated,
            'options'    => $options,
            'created_by' => $request->user()->id,
        ]);

        AdminActionLog::record($request->user()->id, 'poll.create', $poll);

        return response()->json($poll, 201);
    }

    public function show(Poll $poll): JsonResponse
    {
        return response()->json($poll->load('creator:id,nickname'));
    }

    public function update(Request $request, Poll $poll): JsonResponse
    {
        $validated = $request->validate([
            'title'       => ['sometimes', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:1000'],
            'ends_at'     => ['nullable', 'date'],
            'is_active'   => ['boolean'],
        ]);

        $poll->update($validated);
        AdminActionLog::record($request->user()->id, 'poll.update', $poll, $validated);

        return response()->json($poll->fresh());
    }

    public function destroy(Request $request, Poll $poll): Response
    {
        $poll->delete();
        AdminActionLog::record($request->user()->id, 'poll.delete', $poll);
        return response()->noContent();
    }

    public function close(Request $request, Poll $poll): JsonResponse
    {
        $poll->update(['is_active' => false, 'ends_at' => now()]);
        AdminActionLog::record($request->user()->id, 'poll.close', $poll);
        return response()->json(['message' => '투표가 종료되었습니다.']);
    }
}
