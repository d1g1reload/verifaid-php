<?php

declare(strict_types=1);

namespace Verifaid\Exception;

/**
 * HTTP 400: request tidak lengkap atau tidak valid, misalnya gambar tidak
 * terkirim atau formatnya tidak didukung.
 */
final class BadRequestException extends ApiException
{
}
