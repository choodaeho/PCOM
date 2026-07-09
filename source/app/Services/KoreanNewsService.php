<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 한국 뉴스 RSS 수집 서비스
 *
 * Gemini 프롬프트에 주입할 실제 뉴스 컨텍스트를 RSS 피드로부터 수집합니다.
 * Google Search 그라운딩 대체재로 사용됩니다.
 *
 * 수집 전략:
 *   1. Google News RSS (주제 키워드 검색) — 가장 신뢰도 높은 소스
 *   2. 연합뉴스 RSS — 권위 있는 국내 통신사
 *   3. 진영별 언론사 RSS (보수→조선/중앙, 진보→한겨레/오마이뉴스)
 *
 * ⚠️ 뉴스 수집 실패(네트워크 오류, RSS 파싱 실패)는 조용히 처리되며,
 *    빈 배열을 반환합니다. 호출자는 빈 배열로 폴백하여 계속 진행해야 합니다.
 */
class KoreanNewsService
{
    /** Gemini 프롬프트에 포함할 최대 기사 수 */
    private const MAX_ARTICLES = 4;

    /** 단일 피드에서 가져올 최대 기사 수 */
    private const MAX_PER_FEED = 3;

    /** 최신 기사 기준 일수 (이 일수 이내 기사만 포함) */
    private const MAX_AGE_DAYS = 3;

    /** RSS 요청 타임아웃(초) */
    private const TIMEOUT_SECONDS = 8;

    /**
     * 진영별 언론사 RSS 피드 목록
     *
     * @var array<string, list<array{url:string, name:string}>>
     */
    private const FACTION_FEEDS = [
        'conservative' => [
            ['url' => 'https://www.chosun.com/arc/outboundfeeds/rss/',     'name' => '조선일보'],
            ['url' => 'https://rss.joins.com/joins_news_list.xml',          'name' => '중앙일보'],
        ],
        'moderate' => [
            ['url' => 'https://www.yna.co.kr/RSS/politics.xml',             'name' => '연합뉴스'],
            ['url' => 'https://www.yna.co.kr/RSS/economy.xml',              'name' => '연합뉴스'],
        ],
        'progressive' => [
            ['url' => 'https://www.hani.co.kr/rss/',                        'name' => '한겨레'],
            ['url' => 'https://www.ohmynews.com/rss/rss.aspx',              'name' => '오마이뉴스'],
        ],
    ];

    /**
     * 진영 + 주제에 맞는 최신 뉴스 기사 목록 반환
     *
     * Gemini 프롬프트 주입용. 최대 MAX_ARTICLES개 반환.
     * 네트워크 오류 등 수집 실패 시 빈 배열 반환 (예외 미발생).
     *
     * @return list<array{title:string, summary:string, url:string, source:string}>
     */
    public function fetchForPrompt(string $faction, string $topic): array
    {
        $keywords = $this->extractKeywords($topic);
        $articles = [];

        // 1. Google News RSS (주제 키워드 검색)
        $gNews    = $this->fetchGoogleNews($topic, $keywords);
        $articles = array_merge($articles, $gNews);

        // 2. 연합뉴스 (항상 포함 — 권위 있는 중립 통신사)
        if (count($articles) < self::MAX_ARTICLES) {
            $yna      = $this->fetchRssFeed(
                'https://www.yna.co.kr/RSS/politics.xml',
                '연합뉴스',
                $keywords,
            );
            $articles = array_merge($articles, $yna);
        }

        // 3. 진영별 언론사 RSS
        $feeds = self::FACTION_FEEDS[$faction] ?? [];
        foreach ($feeds as $feed) {
            if (count($articles) >= self::MAX_ARTICLES) {
                break;
            }
            $feedArticles = $this->fetchRssFeed($feed['url'], $feed['name'], $keywords);
            $articles     = array_merge($articles, $feedArticles);
        }

        // URL 기준 중복 제거 + 최대 개수 제한
        $unique = [];
        $seen   = [];
        foreach ($articles as $article) {
            $key = md5($article['url']);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[]   = $article;
            }
            if (count($unique) >= self::MAX_ARTICLES) {
                break;
            }
        }

        Log::debug('[KoreanNewsService] 뉴스 수집 완료', [
            'faction'   => $faction,
            'topic'     => mb_substr($topic, 0, 50),
            'articles'  => count($unique),
        ]);

        return array_values($unique);
    }

    // ─────────────────────────────────────────────────────────
    // private: 피드 수집
    // ─────────────────────────────────────────────────────────

    /**
     * Google News RSS 검색으로 기사 수집
     *
     * @param  list<string> $keywords
     * @return list<array{title:string, summary:string, url:string, source:string}>
     */
    private function fetchGoogleNews(string $topic, array $keywords): array
    {
        $query = urlencode($topic . ' 한국');
        $url   = "https://news.google.com/rss/search?q={$query}&hl=ko&gl=KR&ceid=KR:ko";
        return $this->fetchRssFeed($url, 'Google News', $keywords);
    }

    /**
     * RSS 피드 파싱 및 기사 수집
     *
     * @param  list<string> $keywords  관련성 필터에 사용할 키워드 목록
     * @return list<array{title:string, summary:string, url:string, source:string}>
     */
    private function fetchRssFeed(string $feedUrl, string $sourceName, array $keywords): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; PolilNewsBot/1.0; +https://polit.kr)',
                    'Accept'     => 'application/rss+xml, application/xml, text/xml, */*',
                ])
                ->get($feedUrl);

            if (!$response->successful()) {
                Log::debug('[KoreanNewsService] RSS 응답 실패', [
                    'feed'   => $feedUrl,
                    'status' => $response->status(),
                ]);
                return [];
            }

            // 잘못된 XML 경고 억제
            $xml = @simplexml_load_string($response->body());
            if ($xml === false) {
                Log::debug('[KoreanNewsService] XML 파싱 실패', ['feed' => $feedUrl]);
                return [];
            }

            $articles = [];
            $items    = $xml->channel->item ?? $xml->item ?? [];

            foreach ($items as $item) {
                // 제목 추출
                $title = trim(strip_tags((string)($item->title ?? '')));
                if (empty($title)) {
                    continue;
                }

                // URL 추출 (link → guid 순)
                $url = $this->extractUrl($item);
                if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                    continue;
                }

                // 최신 기사 필터
                if (!$this->isRecentArticle((string)($item->pubDate ?? ''))) {
                    continue;
                }

                // 주제 관련성 필터 (키워드 없으면 전체 포함)
                if (!empty($keywords) && !$this->isRelevant($title, $keywords)) {
                    continue;
                }

                // 요약 추출 (200자 이내)
                $summary = strip_tags((string)($item->description ?? ''));
                $summary = (string) preg_replace('/\s+/', ' ', trim($summary));
                $summary = mb_substr($summary, 0, 200);

                // 출처 이름 (Google News는 <source> 태그로 언론사명 제공)
                $source = $sourceName;
                $srcTag = trim(strip_tags((string)($item->source ?? '')));
                if ($srcTag !== '') {
                    $source = $srcTag;
                }

                $articles[] = [
                    'title'   => $title,
                    'summary' => $summary,
                    'url'     => $url,
                    'source'  => $source,
                ];

                if (count($articles) >= self::MAX_PER_FEED) {
                    break;
                }
            }

            return $articles;

        } catch (\Throwable $e) {
            Log::warning('[KoreanNewsService] RSS 수집 실패', [
                'feed'  => mb_substr($feedUrl, 0, 100),
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────
    // private: 헬퍼
    // ─────────────────────────────────────────────────────────

    /**
     * SimpleXML item 요소에서 기사 URL 추출
     *
     * RSS 2.0: <link> 텍스트 → <guid> 텍스트 순으로 시도
     */
    private function extractUrl(\SimpleXMLElement $item): string
    {
        $url = trim((string)($item->link ?? ''));
        if (!empty($url)) {
            return $url;
        }

        // guid가 URL 형태인 경우
        $guid = trim((string)($item->guid ?? ''));
        if (filter_var($guid, FILTER_VALIDATE_URL)) {
            return $guid;
        }

        return '';
    }

    /**
     * pubDate 기준 최신 기사 여부 확인 (MAX_AGE_DAYS 이내)
     *
     * 날짜 파싱 실패 시 포함(true)으로 처리 (안전 폴백)
     */
    private function isRecentArticle(string $pubDate): bool
    {
        if (empty($pubDate)) {
            return true;
        }
        try {
            $date    = new \DateTime($pubDate);
            $diffDay = (int) abs(now()->diffInDays($date));
            return $diffDay <= self::MAX_AGE_DAYS;
        } catch (\Exception) {
            return true;
        }
    }

    /**
     * 주제 문자열에서 2글자 이상 키워드 배열 추출
     *
     * @return list<string>
     */
    private function extractKeywords(string $topic): array
    {
        $words = preg_split('/[\s,·\/\|]+/', $topic) ?: [];
        $words = array_map('trim', $words);
        $words = array_filter($words, fn(string $w) => mb_strlen($w) >= 2);
        return array_values($words);
    }

    /**
     * 기사 제목이 키워드 중 하나라도 포함하는지 확인
     *
     * @param list<string> $keywords
     */
    private function isRelevant(string $title, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (mb_strpos($title, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }
}
