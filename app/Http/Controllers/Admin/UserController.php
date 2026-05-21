<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::query()->withCount(['posts', 'comments']);

        if ($request->filled('search')) {
            $query->where(fn ($q) => $q->where('email', 'ilike', '%' . $request->search . '%')
                ->orWhere('nickname', 'ilike', '%' . $request->search . '%'));
        }

        if ($request->filled('faction')) {
            $query->where('political_type', $request->faction);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return Inertia::render('Admin/Users/Index', [
            'users'   => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $request->only(['search', 'faction', 'status']),
        ]);
    }

    public function show(User $user): Response
    {
        $user->load(['posts' => fn ($q) => $q->latest()->limit(10), 'socialAccounts']);

        return Inertia::render('Admin/Users/Show', ['user' => $user]);
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'days'   => ['required', 'integer', 'min:1', 'max:365'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $user->update([
            'status'            => 'suspended',
            'suspended_until'   => now()->addDays($validated['days']),
            'suspension_reason' => $validated['reason'],
        ]);

        AdminActionLog::record($request->user()->id, 'user_suspend', $user, $validated);

        return back()->with('success', "{$user->nickname} 계정을 {$validated['days']}일 정지했습니다.");
    }

    public function ban(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $user->update(['status' => 'banned']);

        AdminActionLog::record($request->user()->id, 'user_ban', $user, $validated);

        return back()->with('success', "{$user->nickname} 계정을 영구 차단했습니다.");
    }

    public function activate(Request $request, User $user): RedirectResponse
    {
        $user->update(['status' => 'active', 'suspended_until' => null]);

        AdminActionLog::record($request->user()->id, 'user_activate', $user);

        return back()->with('success', "{$user->nickname} 계정을 활성화했습니다.");
    }
}
