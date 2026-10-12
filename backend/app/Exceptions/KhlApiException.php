<?php

namespace App\Exceptions;

use RuntimeException;

class KhlApiException extends RuntimeException
{
    public static function requestFailed(string $url, int $status, string $body = ''): self
    {
        return new self(sprintf(
            'KHL API request failed [%d] %s — %s',
            $status,
            $url,
            mb_substr($body, 0, 500)
        ));
    }

    public static function invalidJson(string $url, string $raw): self
    {
        return new self(sprintf(
            'KHL API returned invalid JSON for %s — %s',
            $url,
            mb_substr($raw, 0, 500)
        ));
    }

    public static function unexpectedStructure(string $url, string $hint = ''): self
    {
        return new self(sprintf(
            'KHL API returned unexpected structure for %s%s',
            $url,
            $hint ? ' — ' . $hint : ''
        ));
    }
}