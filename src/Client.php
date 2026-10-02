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

/**
 * Titik masuk SDK VerifAID.
 *
 *     $verifaid = new \Verifaid\Client('sv_live_xxx');
 *     $ktp = $verifaid->ocr()->ktp('/path/ke/ktp.jpg');
 */
final class Client
{
    public const VERSION = '1.0.2';

    public const DEFAULT_BASE_URL = 'https://verifaid.my.id/api/v1/';

    private const KEY_TYPES = [
        'ocr' => ['prefix' => 'sv_live_', 'label' => 'OCR self-service'],
        'h2h' => ['prefix' => 'sv_h2h_', 'label' => 'Host-to-Host'],
    ];

    private const DEFAULT_OPTIONS = [
        'base_url'        => self::DEFAULT_BASE_URL,
        'timeout'         => 120,
        'connect_timeout' => 10,
        'transport'       => null,
    ];

    private string $apiKey;

    private string $baseUrl;

    private TransportInterface $transport;

    private ?Ocr $ocr = null;

    private ?H2h $h2h = null;

    /**
     * @param string $apiKey API key VerifAID: sv_live_... (OCR, dibuat di dashboard) atau sv_h2h_... (H2H, dikirim lewat email).
     * @param array{
     *     base_url?: string,
     *     timeout?: int|float,
     *     connect_timeout?: int|float,
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

        $transport = $options['transport'];
        if ($transport !== null && !$transport instanceof TransportInterface) {
            throw new InvalidArgumentException(sprintf('Opsi "transport" harus mengimplementasikan %s.', TransportInterface::class));
        }

        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim((string) $options['base_url'], '/') . '/';
        $this->transport = $transport ?? new CurlTransport((float) $options['timeout'], (float) $options['connect_timeout']);
    }

    /**
     * OCR dokumen untuk API key self-service (sv_live_...).
     *
     * @throws InvalidArgumentException Bila API key adalah key H2H (sv_h2h_...).
     */
    public function ocr(): Ocr
    {
        $this->assertKeyMatches('ocr');

        return $this->ocr ??= new Ocr($this);
    }

    /**
     * OCR dokumen dan cek kuota untuk klien Host-to-Host (sv_h2h_...).
     *
     * @throws InvalidArgumentException Bila API key adalah key self-service (sv_live_...).
     */
    public function h2h(): H2h
    {
        $this->assertKeyMatches('h2h');

        return $this->h2h ??= new H2h($this);
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

    // Tiap jenis key hanya berlaku di endpoint-nya sendiri, dan server membalas key yang salah tempat
    // dengan 401 "API key tidak valid" biasa. Gagal di sini langsung menyebutkan resource yang benar.
    private function assertKeyMatches(string $resource): void
    {
        foreach (self::KEY_TYPES as $other => $type) {
            if ($other !== $resource && strpos($this->apiKey, $type['prefix']) === 0) {
                throw new InvalidArgumentException(sprintf(
                    'API key %s... adalah key %s. Gunakan $verifaid->%s(), bukan $verifaid->%s().',
                    $type['prefix'],
                    $type['label'],
                    $other,
                    $resource
                ));
            }
        }
    }
}
