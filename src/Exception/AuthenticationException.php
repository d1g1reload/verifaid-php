<?php

declare(strict_types=1);

namespace Verifaid\Exception;

/**
 * HTTP 401: API key tidak valid, tidak ditemukan, atau tidak dikirim.
 */
final class AuthenticationException extends ApiException
{
}
