<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 게시글용 이미지 URL 조회
 *
 * 우선순위:
 *   1. Pixabay API (무료, 100 req/hour — apiKey 있을 때)
 *   2. Picsum Photos (완전 무료 폴백)
 */
class NewsImageService
{
    private const PIXABAY_API = 'https://pixabay.com/api/';

    public function __construct(private readonly string $pixabayApiKey = '')
    {
    }

    /**
     * 주제 키워드로 이미지 URL 반환
     *
     * @param  string $query      영어 검색어 (예: "korea politics economy")
     * @param  int    $width      권장 너비 (기본 800)
     * @return string 이미지 URL (항상 유효한 값 반환)
     */
    public function fetchImageUrl(string $query, int $width = 800): string
    {
        if (! empty($this->pixabayApiKey)) {
            $url = $this->fetchFromPixabay($query);
            if ($url !== null) {
                return $url;
            }
        }

        return $this->buildPicsumUrl($query, $width);
    }

    /**
     * Pixabay API로 이미지 검색
     */
    private function fetchFromPixabay(string $query): ?string
    {
        try {
            $response = Http::timeout(10)->get(self::PIXABAY_API, [
                'key'         => $this->pixabayApiKey,
                'q'           => urlencode($query),
                'image_type'  => 'photo',
                'orientation' => 'horizontal',
                'category'    => 'backgrounds',
                'min_width'   => 640,
                'per_page'    => 10,
                'safesearch'  => 'true',
                'lang'        => 'en',
            ]);

            if (! $response->successful()) {
                Log::warning('[NewsImageService] Pixabay 오류', ['status' => $response->status()]);
                return null;
            }

            $hits = $response->json('hits', []);
            if (empty($hits)) {
                // 검색어를 단순화해서 재시도 (첫 단어만)
                $firstWord = explode(' ', trim($query))[0];
                if ($firstWord !== $query) {
                    return $this->fetchFromPixabay($firstWord);
                }
                return null;
            }

            // 랜덤하게 상위 5개 중 선택 (다양성)
            $pick = $hits[array_rand(array_slice($hits, 0, min(5, count($hits))))];

            return $pick['webformatURL'] ?? $pick['largeImageURL'] ?? null;

        } catch (\Throwable $e) {
            Log::warning('[NewsImageService] Pixabay 예외', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Picsum Photos 폴백 (완전 무료, 키 불필요)
     * seed 값으로 쿼리어 기반의 일관된 이미지 반환
     */
    private function buildPicsumUrl(string $query, int $width): string
    {
        // 쿼리를 seed로 변환하여 동일 주제엔 동일 이미지
        $seed = abs(crc32($query)) % 1000;
        return "https://picsum.photos/seed/{$seed}/{$width}/450";
    }
}
