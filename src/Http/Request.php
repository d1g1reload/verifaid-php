<?php

declare(strict_types=1);

namespace Verifaid\Http;

use Verifaid\Image;

final class Request
{
    private string $method;

    private string $url;

    /** @var array<string, string> */
    private array $headers;

    /** @var array<string, mixed>|null */
    private ?array $json;

    /** @var array<string, Image> */
    private array $files;

    /**
     * @param array<string, string>     $headers
     * @param array<string, mixed>|null $json
     * @param array<string, Image>      $files
     */
    public function __construct(string $method, string $url, array $headers = [], ?array $json = null, array $files = [])
    {
        $this->method = $method;
        $this->url = $url;
        $this->headers = $headers;
        $this->json = $json;
        $this->files = $files;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getJson(): ?array
    {
        return $this->json;
    }

    /**
     * @return array<string, Image>
     */
    public function getFiles(): array
    {
        return $this->files;
    }
}
