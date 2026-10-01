<?php

declare(strict_types=1);

namespace Verifaid\Exception;

/**
 * HTTP 404: endpoint tidak ditemukan. Biasanya karena opsi "base_url" keliru.
 */
final class NotFoundException extends ApiException
{
}
