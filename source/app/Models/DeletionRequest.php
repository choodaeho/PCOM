<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeletionRequest extends Model
{
    protected $fillable = [
        'request_type',
        'requester_name',
        'requester_email',
        'target_url',
        'description',
        'status',
        'processed_at',
        'user_id',
        'related_post_id',
        'related_comment_id',
        'blinded_type',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function relatedPost(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Post::class, 'related_post_id');
    }

    public function relatedComment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Comment::class, 'related_comment_id');
    }

    /** 요청 유형 레이블 */
    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'personal_info' => '개인정보',
            'defamation'    => '명예훼손',
            'copyright'     => '저작권',
            'post'          => '게시물',
            'comment'       => '댓글',
            'other'         => '기타',
            default         => $type,
        };
    }
}
