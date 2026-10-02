<?php

declare(strict_types=1);

namespace Verifaid\Tests\Unit;

use PHPUnit\Framework\TestCase;
use stdClass;
use Verifaid\Client;
use Verifaid\Exception\InvalidArgumentException;
use Verifaid\Tests\Support\FakeTransport;

final class ClientTest extends TestCase
{
    public function testRejectsEmptyApiKey(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Client('   ');
    }

    public function testRejectsUnknownOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Opsi tidak dikenal: baseUrl');

        new Client('sv_live_x', ['baseUrl' => 'https://example.com']);
    }

    public function testRejectsInvalidTransport(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Client('sv_live_x', ['transport' => new stdClass()]);
    }

    public function testUsesProductionBaseUrlByDefault(): void
    {
        $this->assertSame('https://verifaid.my.id/api/v1/', (new Client('sv_live_x'))->getBaseUrl());
    }

    /**
     * @dataProvider baseUrlProvider
     */
    public function testJoinsBaseUrlAndPath(string $baseUrl, string $path): void
    {
        $transport = (new FakeTransport())->pushSuccess();
        $client = new Client('sv_live_x', ['base_url' => $baseUrl, 'transport' => $transport]);

        $client->request('post', $path);

        $this->assertSame('POST', $transport->lastRequest()->getMethod());
        $this->assertSame('https://api.test/api/v1/h2h/quota', $transport->lastRequest()->getUrl());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function baseUrlProvider(): array
    {
        return [
            'dengan garis miring' => ['https://api.test/api/v1/', 'h2h/quota'],
            'tanpa garis miring'  => ['https://api.test/api/v1', 'h2h/quota'],
            'path diawali miring' => ['https://api.test/api/v1/', '/h2h/quota'],
        ];
    }

    public function testSendsAuthenticationHeaders(): void
    {
        $transport = (new FakeTransport())->pushSuccess();
        $client = new Client('  sv_live_abc  ', ['transport' => $transport]);

        $client->request('GET', 'anything');

        $headers = $transport->lastRequest()->getHeaders();
        $this->assertSame('Bearer sv_live_abc', $headers['Authorization']);
        $this->assertSame('application/json', $headers['Accept']);
        $this->assertStringStartsWith('verifaid-php/' . Client::VERSION . ' PHP/', $headers['User-Agent']);
    }

    public function testRequestReturnsFullResponse(): void
    {
        $transport = (new FakeTransport())->pushSuccess(['foo' => 'bar'], 'Berhasil');
        $client = new Client('sv_live_x', ['transport' => $transport]);

        $response = $client->request('POST', 'custom/endpoint', ['a' => 1]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Berhasil', $response->getMessage());
        $this->assertSame(['foo' => 'bar'], $response->getData());
        $this->assertSame(['a' => 1], $transport->lastRequest()->getJson());
    }

    public function testResourcesAreReused(): void
    {
        $ocrClient = new Client('sv_live_x');
        $h2hClient = new Client('sv_h2h_x');

        $this->assertSame($ocrClient->ocr(), $ocrClient->ocr());
        $this->assertSame($h2hClient->h2h(), $h2hClient->h2h());
    }

    public function testOcrRejectsH2hKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Gunakan $verifaid->h2h(), bukan $verifaid->ocr()');

        (new Client('sv_h2h_x'))->ocr();
    }

    public function testH2hRejectsSelfServiceKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Gunakan $verifaid->ocr(), bukan $verifaid->h2h()');

        (new Client('sv_live_x'))->h2h();
    }

    public function testKeyWithUnknownPrefixIsAllowedOnBothResources(): void
    {
        $client = new Client('custom_key');

        $this->assertNotNull($client->ocr());
        $this->assertNotNull($client->h2h());
    }
}
