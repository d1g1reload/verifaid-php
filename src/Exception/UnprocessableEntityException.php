<?php

declare(strict_types=1);

namespace Verifaid\Exception;

/**
 * HTTP 422: request valid tetapi tidak bisa diproses. Untuk OCR: gambar terlalu
 * buram, atau dokumen bukan jenis yang diminta. Untuk Payment Gateway: tagihan
 * ditolak oleh payment gateway (detailnya ada di getErrorData()).
 */
final class UnprocessableEntityException extends ApiException
{
}
