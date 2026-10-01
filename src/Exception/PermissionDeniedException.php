<?php

declare(strict_types=1);

namespace Verifaid\Exception;

/**
 * HTTP 403: akses ditolak. Penyebabnya bisa API key atau akun dinonaktifkan,
 * atau paket H2H tidak mencakup jenis dokumen yang diminta.
 */
final class PermissionDeniedException extends ApiException
{
}
