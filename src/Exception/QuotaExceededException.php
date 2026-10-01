<?php

declare(strict_types=1);

namespace Verifaid\Exception;

/**
 * HTTP 402: kuota hit habis. Lakukan top-up di dashboard VerifAID.
 */
final class QuotaExceededException extends ApiException
{
}
