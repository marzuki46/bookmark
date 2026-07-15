<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class LinkCheckerService
{
    public function check(string $url): array
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return ['status' => 'invalid', 'code' => 0, 'message' => 'Invalid URL'];
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,*/*',
                ])
                ->withoutVerifying()
                ->head($url);

            $code = $response->status();

            if ($code === 404) {
                return ['status' => 'dead', 'code' => 404, 'message' => 'Not Found'];
            }

            if (in_array($code, [401, 403])) {
                return ['status' => 'auth_required', 'code' => $code, 'message' => $code === 401 ? 'Unauthorized' : 'Forbidden'];
            }

            if ($response->successful()) {
                return ['status' => 'alive', 'code' => $code, 'message' => 'OK'];
            }

            return ['status' => 'error', 'code' => $code, 'message' => 'HTTP '.$code];
        } catch (ConnectionException $e) {
            return ['status' => 'timeout', 'code' => 0, 'message' => 'Connection timeout (10s)'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'code' => 0, 'message' => mb_substr($e->getMessage(), 0, 100)];
        }
    }

    public function checkBatch(array $urls, ?callable $onProgress = null): array
    {
        $results = [];
        $total = count($urls);

        foreach ($urls as $index => $url) {
            $results[] = array_merge(
                ['url' => $url],
                $this->check($url)
            );

            if ($onProgress) {
                $onProgress($index + 1, $total);
            }
        }

        return $results;
    }
}
