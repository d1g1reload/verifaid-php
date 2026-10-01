<?php

declare(strict_types=1);

namespace Verifaid;

use Verifaid\Exception\ApiException;
use Verifaid\Exception\ConnectionException;
use Verifaid\Exception\InvalidArgumentException;
use Verifaid\Http\CurlTransport;
use Verifaid\Http\Request;
use Verifaid\Http\Response;
use Verifaid\Http\TransportInterface;
use Verifaid\Resource\H2h;
use Verifaid\Resource\Ocr;
use Verifaid\Resource\Payment;

/**
 * Titik masuk SDK VerifAID.
 *
 *     $verifaid = new \Verifaid\Client('sv_live_xxx');
 *     $ktp = $verifaid->ocr()->ktp('/path/ke/ktp.jpg');
 */
final class Client
{
    public const VERSION = '1.0.0';

    public const DEFAULT_BASE_URL = 'https://verifaid.my.id/api/v1/';

    private const DEFAULT_OPTIONS = [
        'base_url'        => self::DEFAULT_BASE_URL,
        'timeout'         => 120,
        'connect_timeout' => 10,
        'environment'     => null,
        'transport'       => null,
    ];

    private const ENVIRONMENTS = ['sandbox', 'production'];

    private string $apiKey;

    private string $baseUrl;

    private ?string $environment;

    private TransportInterface $transport;

    private ?Ocr $ocr = null;

    private ?H2h $h2h = null;

    private ?Payment $payment = null;

    /**
     * @param string $apiKey API key dari dashboard VerifAID: sv_live_... (OCR),
     *                       sv_h2h_... (H2H), SB-Mid-... atau PR-Mid-... (Payment Gateway).
     * @param array{
     *     base_url?: string,
     *     timeout?: int|float,
     *     connect_timeout?: int|float,
     *     environment?: 'sandbox'|'production'|null,
     *     transport?: TransportInterface|null
     * } $options
     */
    public function __construct(string $apiKey, array $options = [])
    {
        $apiKey = trim($apiKey);
        if ($apiKey === '') {
            throw new InvalidArgumentException('API key tidak boleh kosong.');
        }

        $unknown = array_diff_key($options, self::DEFAULT_OPTIONS);
        if ($unknown !== []) {
            throw new InvalidArgumentException(sprintf(
                'Opsi tidak dikenal: %s. Opsi yang tersedia: %s.',
                implode(', ', array_keys($unknown)),
                implode(', ', array_keys(self::DEFAULT_OPTIONS))
            ));
        }

        $options = array_merge(self::DEFAULT_OPTIONS, $options);

        $environment = $options['environment'];
        if ($environment !== null) {
            $environment = strtolower((string) $environment);
            if (!in_array($environment, self::ENVIRONMENTS, true)) {
                throw new InvalidArgumentException('Opsi "environment" harus "sandbox" atau "production".');
            }
        }

        $transport = $options['transport'];
        if ($transport !== null && !$transport instanceof TransportInterface) {
            throw new InvalidArgumentException(sprintf('Opsi "transport" harus mengimplementasikan %s.', TransportInterface::class));
        }

        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim((string) $options['base_url'], '/') . '/';
        $this->environment = $environment;
        $this->transport = $transport ?? new CurlTransport((float) $options['timeout'], (float) $options['connect_timeout']);
    }

    /**
     * OCR dokumen untuk API key self-service (sv_live_...).
     */
    public function ocr(): Ocr
    {
        return $this->ocr ??= new Ocr($this);
    }

    /**
     * OCR dokumen dan cek kuota untuk klien Host-to-Host (sv_h2h_...).
     */
    public function h2h(): H2h
    {
        return $this->h2h ??= new H2h($this);
    }

    /**
     * Payment Gateway QRIS, Virtual Account, saldo, dan penarikan dana (SB-Mid-... / PR-Mid-...).
     */
    public function payment(): Payment
    {
        return $this->payment ??= new Payment($this);
    }

    /**
     * Environment Payment Gateway: dari opsi "environment", atau ditebak dari prefix API key.
     *
     * @return 'sandbox'|'production'|null null bila tidak bisa ditentukan.
     */
    public function environment(): ?string
    {
        if ($this->environment !== null) {
            return $this->environment;
        }

        if (strpos($this->apiKey, 'SB-Mid-') === 0) {
            return 'sandbox';
        }

        if (strpos($this->apiKey, 'PR-Mid-') === 0) {
            return 'production';
        }

        return null;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Kirim request ke API VerifAID. Dipakai oleh semua resource, dan bisa dipanggil
     * langsung untuk endpoint yang belum punya method khusus di SDK ini.
     *
     * @param string                    $path  Path relatif terhadap base URL, misalnya "ocr/ktp".
     * @param array<string, mixed>|null $json  Body JSON.
     * @param array<string, Image>      $files Field multipart berisi gambar.
     *
     * @throws ApiException        Bila API membalas dengan error.
     * @throws ConnectionException Bila server tidak bisa dihubungi.
     */
    public function request(string $method, string $path, ?array $json = null, array $files = []): Response
    {
        $request = new Request(
            strtoupper($method),
            $this->baseUrl . ltrim($path, '/'),
            [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept'        => 'application/json',
                'User-Agent'    => sprintf('verifaid-php/%s PHP/%s', self::VERSION, PHP_VERSION),
            ],
            $json,
            $files
        );

        $response = $this->transport->send($request);

        if (!$response->isSuccessful()) {
            throw ApiException::fromResponse($response);
        }

        return $response;
    }
}
