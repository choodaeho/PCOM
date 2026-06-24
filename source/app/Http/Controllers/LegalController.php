<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\LegalDocument;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    /** 이용약관 */
    public function terms(Request $request): Response
    {
        return $this->renderLegal($request, 'terms', 'Legal/Terms');
    }

    /** 개인정보처리방침 */
    public function privacy(Request $request): Response
    {
        return $this->renderLegal($request, 'privacy', 'Legal/Privacy');
    }

    /** 청소년보호정책 */
    public function youthProtection(Request $request): Response
    {
        return $this->renderLegal($request, 'youth_protection', 'Legal/YouthProtection');
    }

    // ─────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────

    private function renderLegal(Request $request, string $type, string $view): Response
    {
        // 전체 이력 (id, version, title, effective_date, is_current)
        $versions = LegalDocument::historyOf($type)
            ->get(['id', 'version', 'title', 'effective_date', 'is_current']);

        // 특정 버전 조회 (query string ?v={id})
        $viewingId = $request->query('v');
        $document  = $viewingId
            ? LegalDocument::where('type', $type)->findOrFail((int) $viewingId)
            : LegalDocument::currentOf($type)->firstOrFail();

        return Inertia::render($view, [
            'document'  => array_merge(
                $document->only(['id', 'version', 'title', 'content', 'is_current']),
                [
                    'effective_date' => $document->effective_date->format('Y년 m월 d일'),
                    'published_at'   => $document->published_at?->format('Y. m. d'),
                ]
            ),
            'versions'  => $versions->map(fn (LegalDocument $v) => [
                'id'             => $v->id,
                'version'        => $v->version,
                'title'          => $v->title,
                'effective_date' => $v->effective_date->format('Y. m. d'),
                'is_current'     => $v->is_current,
            ]),
            'isCurrent' => $document->is_current,
        ]);
    }
}
