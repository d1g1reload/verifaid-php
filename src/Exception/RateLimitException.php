<?php

declare(strict_types=1);

namespace Verifaid\Exception;

/**
 * HTTP 429: batas request per menit untuk tier Anda terlampaui.
 */
final class RateLimitException extends ApiException
{
    /**
     * Jumlah detik sebelum boleh mencoba lagi, dari header Retry-After.
     */
    public function getRetryAfter(): ?int
    {
        $value = $this->getResponse()->getHeader('Retry-After');

        return $value !== null && ctype_digit($value) ? (int) $value : null;
    }
}
