<?php
namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Fetches a URL with a real browser UA and a short timeout.
 * 403 / 429 / login-wall / captcha pages => status 'blocked', NEVER invented content.
 * Any other failure => status 'failed' with the error message.
 */
class WebFetchService
{
    public function __construct(private Client $http = new Client()) {}

    /** @return array{status: string, text: string, error: ?string} */
    public function fetch(string $url): array
    {
        try {
            $res = $this->http->get($url, [
                'timeout' => 8, 'connect_timeout' => 5, 'http_errors' => false,
                'allow_redirects' => ['max' => 5],
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,text/plain,*/*',
                    'Accept-Language' => 'en-IN,en;q=0.9',
                ],
            ]);
            $code = $res->getStatusCode();
            $body = (string) $res->getBody();
            if ($code === 403 || $code === 429 || $code === 401) {
                return ['status' => 'blocked', 'text' => '', 'error' => "HTTP $code — site refused automated access."];
            }
            if ($code >= 400) {
                return ['status' => 'failed', 'text' => '', 'error' => "HTTP $code."];
            }
            $text = trim(preg_replace('/\s+/', ' ', strip_tags($body)) ?? '');
            if ($this->looksLikeLoginWall($body, $text)) {
                return ['status' => 'blocked', 'text' => '', 'error' => 'Login wall / captcha page detected.'];
            }
            return ['status' => 'ok', 'text' => mb_substr($text, 0, 20000), 'error' => null];
        } catch (RequestException $e) {
            $code = $e->getResponse()?->getStatusCode();
            if (in_array($code, [403, 429, 401], true)) {
                return ['status' => 'blocked', 'text' => '', 'error' => "HTTP $code — site refused automated access."];
            }
            return ['status' => 'failed', 'text' => '', 'error' => $e->getMessage()];
        } catch (\Throwable $e) {
            return ['status' => 'failed', 'text' => '', 'error' => $e->getMessage()];
        }
    }

    private function looksLikeLoginWall(string $html, string $text): bool
    {
        $lower = strtolower($html . ' ' . substr($text, 0, 2000));
        return str_contains($lower, 'captcha') || str_contains($lower, 'verify you are human')
            || str_contains($lower, 'log in to continue') || str_contains($lower, 'sign in to continue');
    }
}

/**
 * No-key web search via DuckDuckGo's HTML endpoint. If DuckDuckGo blocks us,
 * we degrade gracefully (empty results, nothing invented) and the review page
 * says so. Each result is also a candidate for the disambiguation step.
 */
class WebSearchService
{
    public function __construct(private WebFetchService $fetch) {}

    /** @return array<int, array{title: string, url: string, snippet: string}> */
    public function search(string $query, int $limit = 6): array
    {
        $url = 'https://html.duckduckgo.com/html/?q=' . urlencode($query);
        $r = $this->fetch->fetch($url);
        if ($r['status'] !== 'ok' || $r['text'] === '') return [];
        // DDG html endpoint returns real HTML; fetch() stripped tags, so re-fetch raw for links.
        try {
            $raw = (string) (new Client())->get($url, ['timeout' => 8, 'headers' => [
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/126.0 Safari/537.36',
            ]])->getBody();
        } catch (\Throwable) { return []; }
        preg_match_all('/<a[^>]+class="result__a"[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>.*?<a[^>]+class="result__snippet"[^>]*>(.*?)<\/a>/s', $raw, $m, PREG_SET_ORDER);
        $out = [];
        foreach ($m as $row) {
            $out[] = [
                'title' => trim(strip_tags(html_entity_decode($row[2]))),
                'url' => html_entity_decode($row[1]),
                'snippet' => trim(strip_tags(html_entity_decode($row[3]))),
            ];
            if (count($out) >= $limit) break;
        }
        return $out;
    }
}
