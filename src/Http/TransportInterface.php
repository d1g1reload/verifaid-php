<?php

declare(strict_types=1);

namespace Verifaid\Http;

use Verifaid\Exception\ConnectionException;

/**
 * Pengirim HTTP yang dipakai Client. Bawaannya CurlTransport; ganti lewat
 * opsi "transport" bila ingin memakai HTTP client lain atau untuk testing.
 */
interface TransportInterface
{
    /**
     * Kirim request dan kembalikan respons apa adanya, termasuk respons 4xx/5xx.
     *
     * @throws ConnectionException Bila server tidak bisa dihubungi atau koneksi terputus.
     */
    public function send(Request $request): Response;
}
