<?php

declare(strict_types=1);

namespace app\shared\Forum\Infrastructure;

use app\shared\Forum\Contract\ForumHttpClientInterface;
use RuntimeException;

/**
 * HTTP adapter for the forum. Throws ForumPageNotFoundException for
 * missing topics so the scan can skip them without failing the run.
 */
final class ForumHttpClient implements ForumHttpClientInterface
{
    private const BANNED_STATUS = [429, 500, 502, 503, 504];

    public function __construct(
        private readonly int $timeout = 30,
        private readonly int $retries = 3,
        private readonly int $delayMicroseconds = 500000,
    ) {
    }

    public function get(string $url): string
    {
        $attempt = 0;
        $status = 0;
        $statusText = '';
        $body = false;
        while (true) {
            $attempt++;
            [$status, $statusText, $body] = $this->request($url);
            if ($status >= 200 && $status < 300 && $body !== false) {
                return $body;
            }
            if ($status === 404) {
                throw new ForumPageNotFoundException('Topic does not exist: ' . $url);
            }
            if ($attempt > $this->retries || (!in_array($status, self::BANNED_STATUS, true) && $status >= 400)) {
                throw new RuntimeException(sprintf('Forum request failed: %s [%s]', $url, $statusText));
            }
            usleep($this->delayMicroseconds * $attempt);
        }
    }

    /**
     * @return array{0: int, 1: string, 2: string|false}
     */
    private function request(string $url): array
    {
        $handle = curl_init($url);
        if ($handle === false) {
            throw new RuntimeException('Unable to initialize cURL for: ' . $url);
        }
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; AWD-Parser/1.0)',
            CURLOPT_ACCEPT_ENCODING => '',
        ]);
        $body = curl_exec($handle);
        $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        if ($body === false) {
            $statusText = (string)curl_error($handle);
        } else {
            $statusText = $status . ' ' . $this->statusPhrase($status);
        }
        curl_close($handle);
        return [$status, $statusText, $body];
    }

    private function statusPhrase(int $status): string
    {
        return match ($status) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            default => 'HTTP ' . $status,
        };
    }
}
