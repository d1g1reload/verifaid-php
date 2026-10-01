<?php

declare(strict_types=1);

namespace Verifaid\Exception;

/**
 * HTTP 422: gambar tidak bisa diproses, karena terlalu buram atau dokumennya
 * bukan jenis yang diminta. Minta pengguna mengunggah foto ulang.
 */
final class UnprocessableEntityException extends ApiException
{
}
