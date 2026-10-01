<?php

declare(strict_types=1);

namespace Verifaid\Exception;

/**
 * HTTP 400: parameter tidak lengkap atau tidak valid, misalnya gambar tidak
 * terkirim, nominal di bawah minimum, atau bank_code kosong.
 */
final class BadRequestException extends ApiException
{
}
