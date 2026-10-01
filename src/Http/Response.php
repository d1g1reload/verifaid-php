<?php

declare(strict_types=1);

namespace Verifaid\Http;

/**
 * Respons API VerifAID. Bentuk body standarnya:
 *
 *     {"status": "success", "code": 200, "message": "...", "data": {...}}
 */
final class Response
{
    private int $statusCode;

    /** @var array<string, string> Nama header dalam huruf kecil. */
    private array $headers;

    private string $body;

    /** @var array<string, mixed>|null */
    private ?array $json;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(int $statusCode, array $headers, string $body)
    {
        $this->statusCode = $statusCode;
        $this->headers = array_change_key_case($headers, CASE_LOWER);
        $this->body = $body;

        $decoded = json_decode($body, true);
        $this->json = is_array($decoded) ? $decoded : null;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Body yang sudah di-decode, atau null bila body bukan JSON.
     *
     * @return array<string, mixed>|null
     */
    public function json(): ?array
    {
        return $this->json;
    }

    public function getMessage(): string
    {
        $message = $this->json['message'] ?? '';

        return is_string($message) ? $message : '';
    }

    /**
     * Isi field "data" dari body, atau array kosong bila tidak ada.
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $data = $this->json['data'] ?? [];

        return is_array($data) ? $data : [];
    }

    /**
     * True bila HTTP 2xx, body berupa JSON, dan status di body bukan "error".
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200
            && $this->statusCode < 300
            && $this->json !== null
            && ($this->json['status'] ?? 'success') !== 'error';
    }
}
