<?php

declare(strict_types=1);

namespace Verifaid\Exception;

use RuntimeException;

/**
 * Server VerifAID tidak bisa dihubungi: DNS gagal, koneksi ditolak, timeout,
 * atau masalah sertifikat SSL. Kode exception berisi nomor error cURL.
 *
 * Request OCR yang timeout bisa saja tetap diproses server, jadi jangan langsung
 * mengulang request bila kuota Anda terbatas.
 */
final class ConnectionException extends RuntimeException implements VerifaidException
{
}
