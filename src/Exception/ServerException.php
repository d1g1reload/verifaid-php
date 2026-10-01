<?php

declare(strict_types=1);

namespace Verifaid\Exception;

/**
 * HTTP 5xx: terjadi gangguan di server VerifAID. Coba lagi beberapa saat lagi.
 */
final class ServerException extends ApiException
{
}
