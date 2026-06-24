<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\LegalDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LegalDocumentController extends Controller
{
    private const TYPES = [
        'terms'            => '이용약관',
        'privacy'          => '개인정보처리방침',
        'youth_protection' => '청소년보호정책',
    ];

    public function index(): Response
    {
        $documents = LegalDocument::withTrashed()
            ->whereNull('deleted_at')
            ->orderBy('type')
            ->orderByDesc('effective_date')
            ->get();

        return Inertia::render('Admin/Legal/Index', [
            'grouped' => $documents->groupBy('type')->map(fn ($docs) => $docs->map(fn ($d) => [
                'id'             => $d->id,
                'type'           => $d->type,
                'version'        => $d->version,
                'title'          => $d->title,
                'effective_date' => $d->effective_date->format('Y. m. d'),
                'is_current'     => $d->is_current,
                'published_at'   => $d->published_at?->format('Y. m. d H:i'),
            ])),
            'types' => self::TYPES,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Legal/Form', [
            'types'       => self::TYPES,
            'defaultType' => $request->query('type', 'terms'),
            'document'    => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type'           => ['required', Rule::in(array_keys(self::TYPES))],
            'version'        => ['required', 'string', 'max:20'],
            'title'          => ['required', 'string', 'max:200'],
            'content'        => ['required', 'string'],
            'effective_date' => ['required', 'date'],
            'set_as_current' => ['nullable', 'boolean'],
        ], [
            'type.required'           => '문서 유형을 선택하세요.',
            'version.required'        => '버전을 입력하세요.',
            'title.required'          => '제목을 입력하세요.',
            'content.required'        => '내용을 입력하세요.',
            'effective_date.required' => '시행일을 입력하세요.',
        ]);

        $setCurrent = (bool) ($validated['set_as_current'] ?? false);

        if ($setCurrent) {
            LegalDocument::where('type', $validated['type'])->update(['is_current' => false]);
        }

        $doc = LegalDocument::create([
            'type'           => $validated['type'],
            'version'        => $validated['version'],
            'title'          => $validated['title'],
            'content'        => $validated['content'],
            'effective_date' => $validated['effective_date'],
            'is_current'     => $setCurrent,
            'created_by'     => $request->user()->id,
            'published_at'   => now(),
        ]);

        AdminActionLog::record($request->user()->id, 'legal_create', $doc);

        return redirect()->route('admin.legal.index')
            ->with('success', "'{$doc->title}' {$doc->version} 버전이 추가되었습니다.");
    }

    public function edit(LegalDocument $legal): Response
    {
        return Inertia::render('Admin/Legal/Form', [
            'types'    => self::TYPES,
            'document' => [
                'id'             => $legal->id,
                'type'           => $legal->type,
                'version'        => $legal->version,
                'title'          => $legal->title,
                'content'        => $legal->content,
                'effective_date' => $legal->effective_date->format('Y-m-d'),
                'is_current'     => $legal->is_current,
            ],
        ]);
    }

    public function update(Request $request, LegalDocument $legal): RedirectResponse
    {
        $validated = $request->validate([
            'version'        => ['required', 'string', 'max:20'],
            'title'          => ['required', 'string', 'max:200'],
            'content'        => ['required', 'string'],
            'effective_date' => ['required', 'date'],
        ]);

        $legal->update($validated);

        AdminActionLog::record($request->user()->id, 'legal_update', $legal);

        return redirect()->route('admin.legal.index')
            ->with('success', '문서가 수정되었습니다.');
    }

    public function setCurrent(Request $request, LegalDocument $legal): RedirectResponse
    {
        LegalDocument::where('type', $legal->type)->update(['is_current' => false]);
        $legal->update(['is_current' => true]);

        AdminActionLog::record($request->user()->id, 'legal_set_current', $legal);

        return back()->with('success', "{$legal->version}이(가) 현재 적용 버전으로 설정되었습니다.");
    }

    public function destroy(Request $request, LegalDocument $legal): RedirectResponse
    {
        abort_if($legal->is_current, 422, '현재 적용 중인 버전은 삭제할 수 없습니다.');
        $legal->delete();

        AdminActionLog::record($request->user()->id, 'legal_delete', $legal);

        return back()->with('success', '버전이 삭제되었습니다.');
    }
}
