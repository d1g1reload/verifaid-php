<?php

declare(strict_types=1);

namespace Verifaid\Http;

use CURLFile;
use Verifaid\Exception\ConnectionException;
use Verifaid\Exception\InvalidArgumentException;

/**
 * Transport bawaan berbasis ext-curl, tanpa dependensi tambahan.
 */
final class CurlTransport implements TransportInterface
{
    private float $timeout;

    private float $connectTimeout;

    /**
     * @param float $timeout        Batas waktu total per request, dalam detik. OCR bisa butuh puluhan detik.
     * @param float $connectTimeout Batas waktu membuka koneksi, dalam detik.
     */
    public function __construct(float $timeout = 120.0, float $connectTimeout = 10.0)
    {
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;
    }

    public function send(Request $request): Response
    {
        $handle = curl_init($request->getUrl());
        if ($handle === false) {
            throw new ConnectionException('Gagal menginisialisasi cURL.');
        }

        $headers = $request->getHeaders();
        // Tanpa ini cURL menunggu "100 Continue" sebelum mengirim upload gambar.
        $headers['Expect'] = '';

        $responseHeaders = [];
        $options = [
            CURLOPT_CUSTOMREQUEST     => $request->getMethod(),
            CURLOPT_RETURNTRANSFER    => true,
            CURLOPT_FOLLOWLOCATION    => false,
            CURLOPT_TIMEOUT_MS        => (int) round($this->timeout * 1000),
            CURLOPT_CONNECTTIMEOUT_MS => (int) round($this->connectTimeout * 1000),
            CURLOPT_HEADERFUNCTION    => static function ($handle, string $line) use (&$responseHeaders): int {
                // Blok header baru (mis. setelah "100 Continue") menggantikan yang lama.
                if (strncmp($line, 'HTTP/', 5) === 0) {
                    $responseHeaders = [];
                }

                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }

                return strlen($line);
            },
        ];

        $files = $request->getFiles();
        $json = $request->getJson();

        if ($files !== []) {
            $fields = [];
            foreach ($files as $field => $image) {
                $fields[$field] = new CURLFile($image->getPath(), $image->getMimeType(), $image->getFilename());
            }
            $options[CURLOPT_POSTFIELDS] = $fields;
        } elseif ($json !== null) {
            $body = json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($body === false) {
                $this->close($handle);
                throw new InvalidArgumentException('Gagal meng-encode body JSON: ' . json_last_error_msg());
            }
            $options[CURLOPT_POSTFIELDS] = $body;
            $headers['Content-Type'] = 'application/json';
        } elseif ($request->getMethod() !== 'GET') {
            // Kirim "Content-Length: 0" agar POST tanpa body tidak ditolak server.
            $options[CURLOPT_POSTFIELDS] = '';
        }

        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $value === '' ? $name . ':' : $name . ': ' . $value;
        }
        $options[CURLOPT_HTTPHEADER] = $headerLines;

        curl_setopt_array($handle, $options);

        $body = curl_exec($handle);

        if ($body === false) {
            $error = curl_error($handle);
            $errno = curl_errno($handle);
            $this->close($handle);

            throw new ConnectionException(
                sprintf('Gagal terhubung ke API VerifAID: %s (cURL error %d).', $error, $errno),
                $errno
            );
        }

        $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $this->close($handle);

        return new Response($statusCode, $responseHeaders, (string) $body);
    }

    /**
     * @param resource|\CurlHandle $handle
     */
    private function close($handle): void
    {
        // Sejak PHP 8.0 handle ditutup otomatis, dan curl_close() deprecated di PHP 8.5.
        if (PHP_VERSION_ID < 80000) {
            curl_close($handle);
        }
    }
}
