<?php

declare(strict_types=1);

namespace Verifaid\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Verifaid\Client;
use Verifaid\Exception\ApiException;
use Verifaid\Exception\AuthenticationException;
use Verifaid\Exception\BadRequestException;
use Verifaid\Exception\NotFoundException;
use Verifaid\Exception\PermissionDeniedException;
use Verifaid\Exception\QuotaExceededException;
use Verifaid\Exception\RateLimitException;
use Verifaid\Exception\ServerException;
use Verifaid\Exception\UnprocessableEntityException;
use Verifaid\Exception\VerifaidException;
use Verifaid\Http\Response;
use Verifaid\Tests\Support\FakeTransport;

final class ExceptionTest extends TestCase
{
    /**
     * @dataProvider statusProvider
     *
     * @param class-string<ApiException> $expectedClass
     */
    public function testMapsStatusCodeToException(int $status, string $expectedClass): void
    {
        $exception = $this->captureException(
            (new FakeTransport())->pushJson($status, ['status' => 'error', 'code' => $status, 'message' => 'Pesan dari server'])
        );

        $this->assertSame($expectedClass, get_class($exception));
        $this->assertInstanceOf(VerifaidException::class, $exception);
        $this->assertSame('Pesan dari server', $exception->getMessage());
        $this->assertSame($status, $exception->getCode());
        $this->assertSame($status, $exception->getStatusCode());
    }

    /**
     * @return array<string, array{int, class-string<ApiException>}>
     */
    public function statusProvider(): array
    {
        return [
            '400' => [400, BadRequestException::class],
            '401' => [401, AuthenticationException::class],
            '402' => [402, QuotaExceededException::class],
            '403' => [403, PermissionDeniedException::class],
            '404' => [404, NotFoundException::class],
            '405' => [405, ApiException::class],
            '422' => [422, UnprocessableEntityException::class],
            '429' => [429, RateLimitException::class],
            '500' => [500, ServerException::class],
            '503' => [503, ServerException::class],
        ];
    }

    public function testRateLimitExposesRetryAfter(): void
    {
        $exception = $this->captureException(
            (new FakeTransport())->pushJson(429, ['status' => 'error', 'message' => 'Terlalu banyak request'], ['Retry-After' => '37'])
        );

        $this->assertInstanceOf(RateLimitException::class, $exception);
        $this->assertSame(37, $exception->getRetryAfter());
    }

    public function testRateLimitWithoutRetryAfterHeader(): void
    {
        $exception = $this->captureException(
            (new FakeTransport())->pushJson(429, ['status' => 'error', 'message' => 'Terlalu banyak request'])
        );

        $this->assertInstanceOf(RateLimitException::class, $exception);
        $this->assertNull($exception->getRetryAfter());
    }

    public function testExposesErrorData(): void
    {
        $exception = $this->captureException(
            (new FakeTransport())->pushJson(422, [
                'status'  => 'error',
                'message' => 'Ditolak payment gateway',
                'data'    => ['errCode' => '91'],
            ])
        );

        $this->assertSame(['errCode' => '91'], $exception->getErrorData());
    }

    public function testHandlesNonJsonErrorBody(): void
    {
        $exception = $this->captureException(
            (new FakeTransport())->push(new Response(502, [], '<html>Bad Gateway</html>'))
        );

        $this->assertInstanceOf(ServerException::class, $exception);
        $this->assertStringContainsString('HTTP 502', $exception->getMessage());
        $this->assertSame('<html>Bad Gateway</html>', $exception->getResponse()->getBody());
    }

    public function testTreatsNonJsonSuccessAsError(): void
    {
        $exception = $this->captureException(
            (new FakeTransport())->push(new Response(200, [], 'Database error'))
        );

        $this->assertSame(ApiException::class, get_class($exception));
        $this->assertStringContainsString('tanpa body JSON', $exception->getMessage());
    }

    public function testTreatsErrorStatusInBodyAsError(): void
    {
        $exception = $this->captureException(
            (new FakeTransport())->pushJson(200, ['status' => 'error', 'message' => 'Gagal'])
        );

        $this->assertSame('Gagal', $exception->getMessage());
    }

    private function captureException(FakeTransport $transport): ApiException
    {
        $client = new Client('sv_live_x', ['transport' => $transport]);

        try {
            $client->request('POST', 'ocr/ktp');
        } catch (ApiException $e) {
            return $e;
        }

        $this->fail('Seharusnya melempar ApiException.');
    }
}
