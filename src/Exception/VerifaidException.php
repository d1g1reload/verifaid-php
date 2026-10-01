<?php

declare(strict_types=1);

namespace Verifaid\Exception;

use Throwable;

/**
 * Ditandai oleh semua exception dari SDK ini, sehingga bisa ditangkap sekaligus:
 *
 *     catch (\Verifaid\Exception\VerifaidException $e) { ... }
 */
interface VerifaidException extends Throwable
{
}
