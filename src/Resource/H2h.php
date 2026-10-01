<?php

declare(strict_types=1);

namespace Verifaid\Resource;

use Verifaid\Exception\ApiException;
use Verifaid\Exception\ConnectionException;

/**
 * OCR dokumen untuk klien Host-to-Host (sv_h2h_...).
 *
 * Jenis dokumen yang bisa dipakai bergantung pada paket: BASIC hanya KTP,
 * BUSINESS menambah SIM, NPWP, dan BPJS, PREMIUM mencakup semuanya termasuk KK.
 * Kuota hanya terpotong bila ekstraksi berhasil.
 */
final class H2h extends DocumentResource
{
    /**
     * Sisa kuota dan informasi paket klien H2H.
     *
     * @return array{
     *     client_name: string,
     *     client_email: string,
     *     tier: string,
     *     remaining_hits: int,
     *     total_hits_all_time: int,
     *     status: string,
     *     check_at: string
     * }
     *
     * @throws ApiException|ConnectionException
     */
    public function quota(): array
    {
        return $this->client->request('POST', 'h2h/quota')->getData();
    }

    protected function prefix(): string
    {
        return 'h2h';
    }
}
