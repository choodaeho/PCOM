<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PostStatus;
use App\Models\DeletionRequest;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DeletionRequestController extends Controller
{
    private const VALID_TYPES = [
        'personal_info',
        'defamation',
        'copyright',
        'post',
        'comment',
        'other',
    ];

    public function create(): Response
    {
        return Inertia::render('Legal/DeletionRequest', [
            'requestTypes' => $this->requestTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'request_type'    => ['required', 'string', Rule::in(self::VALID_TYPES)],
            'requester_name'  => ['required', 'string', 'max:100'],
            'requester_email' => ['required', 'email', 'max:200'],
            'target_url'      => ['nullable', 'url', 'max:1000'],
            'description'     => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'request_type.required'    => '삭제 요청 유형을 선택해주세요.',
            'request_type.in'          => '올바른 요청 유형을 선택해주세요.',
            'requester_name.required'  => '신청자 이름을 입력해주세요.',
            'requester_email.required' => '이메일 주소를 입력해주세요.',
            'requester_email.email'    => '올바른 이메일 주소 형식으로 입력해주세요.',
            'target_url.url'           => '올바른 URL 형식으로 입력해주세요.',
            'description.required'     => '삭제 요청 사유를 입력해주세요.',
            'description.min'          => '삭제 요청 사유는 10자 이상 입력해주세요.',
            'description.max'          => '삭제 요청 사유는 2,000자 이내로 입력해주세요.',
        ]);

        $deletionRequest = DeletionRequest::create([
            ...$validated,
            'user_id' => auth()->id(),
            'status'  => 'pending',
        ]);

        // 대상 URL이 있으면 게시물 자동 블라인드
        if (!empty($validated['target_url'])) {
            $this->tryAutoBlind($deletionRequest);
        }

        return redirect()
            ->route('legal.deletion-request')
            ->with('flash', [
                'success' => '삭제 요청이 접수되었습니다. 영업일 기준 7일 이내에 처리 결과를 이메일로 안내해드립니다.',
            ]);
    }

    // ─────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────

    private function tryAutoBlind(DeletionRequest $request): void
    {
        $url = $request->target_url;

        // /boards/{slug}/posts/{id} 패턴 매칭
        if (preg_match('/\/boards\/[^\/]+\/posts\/(\d+)/', $url, $matches)) {
            $post = Post::find((int) $matches[1]);
            if ($post && $post->status === PostStatus::Published) {
                $post->update(['status' => PostStatus::Hidden]);
                $request->update([
                    'related_post_id' => $post->id,
                    'blinded_type'    => 'post',
                ]);
            }
        }
    }

    /** @return array<int, array{value: string, label: string, description: string}> */
    private function requestTypes(): array
    {
        return [
            [
                'value'       => 'personal_info',
                'label'       => '개인정보 포함 게시물',
                'description' => '주민등록번호, 전화번호, 주소 등 개인정보가 무단으로 공개된 게시물',
            ],
            [
                'value'       => 'defamation',
                'label'       => '명예훼손 게시물',
                'description' => '허위 사실 유포, 모욕 등으로 명예가 훼손된 게시물',
            ],
            [
                'value'       => 'copyright',
                'label'       => '저작권 침해 게시물',
                'description' => '허가 없이 게재된 사진, 영상, 글 등 저작물',
            ],
            [
                'value'       => 'post',
                'label'       => '일반 게시물 삭제',
                'description' => '본인이 작성한 게시물 또는 기타 삭제가 필요한 게시물',
            ],
            [
                'value'       => 'comment',
                'label'       => '댓글 삭제',
                'description' => '본인이 작성한 댓글 또는 기타 삭제가 필요한 댓글',
            ],
            [
                'value'       => 'other',
                'label'       => '기타',
                'description' => '위 항목에 해당하지 않는 삭제 요청',
            ],
        ];
    }
}
