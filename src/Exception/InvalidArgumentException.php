<?php

declare(strict_types=1);

namespace Verifaid\Exception;

/**
 * Input tidak valid di sisi SDK, sebelum request dikirim. Contoh: file gambar
 * tidak ditemukan, format gambar tidak didukung, atau opsi Client salah.
 */
final class InvalidArgumentException extends \InvalidArgumentException implements VerifaidException
{
}
