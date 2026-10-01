<?php

declare(strict_types=1);

namespace Verifaid\Resource;

/**
 * OCR dokumen untuk API key self-service (sv_live_...).
 *
 * Kuota terpotong 1 hit setelah gambar lolos cek kejernihan. Gambar yang
 * ditolak karena buram tidak memotong kuota.
 */
final class Ocr extends DocumentResource
{
    protected function prefix(): string
    {
        return 'ocr';
    }
}
