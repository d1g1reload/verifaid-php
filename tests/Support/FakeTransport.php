<?php

declare(strict_types=1);

namespace Verifaid\Tests\Support;

use RuntimeException;
use Verifaid\Http\Request;
use Verifaid\Http\Response;
use Verifaid\Http\TransportInterface;

final class FakeTransport implements TransportInterface
{
    /** @var list<Request> */
    public array $requests = [];

    /** @var list<Response> */
    private array $queue = [];

    /**
     * @param array<string, mixed> $data
     */
    public function pushSuccess(array $data = [], string $message = 'OK'): self
    {
        return $this->pushJson(200, ['status' => 'success', 'code' => 200, 'message' => $message, 'data' => $data]);
    }

    /**
     * @param array<string, mixed>  $body
     * @param array<string, string> $headers
     */
    public function pushJson(int $status, array $body, array $headers = []): self
    {
        return $this->push(new Response($status, $headers, (string) json_encode($body)));
    }

    public function push(Response $response): self
    {
        $this->queue[] = $response;

        return $this;
    }

    public function send(Request $request): Response
    {
        $this->requests[] = $request;

        if ($this->queue === []) {
            throw new RuntimeException('FakeTransport: tidak ada respons di antrean.');
        }

        return array_shift($this->queue);
    }

    public function lastRequest(): Request
    {
        if ($this->requests === []) {
            throw new RuntimeException('FakeTransport: belum ada request.');
        }

        return $this->requests[count($this->requests) - 1];
    }
}
