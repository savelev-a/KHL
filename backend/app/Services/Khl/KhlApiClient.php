<?php

namespace App\Services\Khl;

use App\Exceptions\KhlApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KhlApiClient
{
    private string $baseUrl;
    private int $timeout;
    private int $retries;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            (string) config('services.khl.base_url', 'https://khl.api.webcaster.pro/api/khl_mobile'),
            '/'
        );
        $this->timeout = (int) config('services.khl.timeout', 15);
        $this->retries = (int) config('services.khl.retries', 3);
    }

    /**
     * GET-запрос к API КХЛ с ретраями и логированием.
     *
     * @param  string  $path   относительный путь, например '/teams_v2.json'
     * @param  array   $query  query-параметры
     * @return array           декодированный JSON как ассоциативный массив
     *
     * @throws KhlApiException
     */
    public function get(string $path, array $query = []): array
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');
        $startedAt = microtime(true);

        Log::channel('khl')->info('KHL API request', [
            'url'   => $url,
            'query' => $query,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->retry(
                    $this->retries,
                    function (int $attempt, \Throwable $e) {
                        // exponential backoff: 1s, 2s, 4s
                        return (int) (1000 * (2 ** ($attempt - 1)));
                    },
                    function (\Throwable $e, $request) {
                        // ретраим на connection/timeout/5xx, но не на 4xx
                        if ($e instanceof ConnectionException) {
                            return true;
                        }
                        if ($e instanceof RequestException && $e->response) {
                            return $e->response->serverError();
                        }
                        return false;
                    },
                    throw: false
                )
                ->acceptJson()
                ->get($url, $query);

            $duration = round((microtime(true) - $startedAt) * 1000);

            if ($response->failed()) {
                Log::channel('khl')->error('KHL API request failed', [
                    'url'      => $url,
                    'status'   => $response->status(),
                    'duration' => $duration,
                    'body'     => mb_substr($response->body(), 0, 500),
                ]);

                throw KhlApiException::requestFailed(
                    $url,
                    $response->status(),
                    $response->body()
                );
            }

            $data = $response->json();

            if (!is_array($data)) {
                Log::channel('khl')->error('KHL API returned non-array JSON', [
                    'url'  => $url,
                    'body' => mb_substr($response->body(), 0, 500),
                ]);

                throw KhlApiException::invalidJson($url, $response->body());
            }

            Log::channel('khl')->info('KHL API response', [
                'url'      => $url,
                'status'   => $response->status(),
                'duration' => $duration,
                'items'    => $this->countItems($data),
            ]);

            return $data;

        } catch (ConnectionException $e) {
            Log::channel('khl')->error('KHL API connection error', [
                'url'   => $url,
                'error' => $e->getMessage(),
            ]);

            throw new KhlApiException(
                "KHL API connection error for {$url}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Грубая оценка «сколько элементов» — только для логов.
     */
    private function countItems(array $data): int
    {
        if (array_is_list($data)) {
            return count($data);
        }

        foreach (['data', 'items', 'teams', 'matches', 'games'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return count($data[$key]);
            }
        }

        return count($data);
    }
}