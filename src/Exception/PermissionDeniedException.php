<?php

declare(strict_types=1);

namespace Verifaid\Exception;

/**
 * HTTP 403: akses ditolak. Penyebabnya bisa API key atau akun dinonaktifkan,
 * paket H2H tidak mencakup jenis dokumen yang diminta, atau API key sandbox
 * dipakai di endpoint production (dan sebaliknya).
 */
final class PermissionDeniedException extends ApiException
{
}
