<?php

declare(strict_types=1);

namespace Verifaid\Tests\Integration;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Verifaid\Client;
use Verifaid\Exception\ConnectionException;
use Verifaid\Exception\RateLimitException;
use Verifaid\Exception\ServerException;
use Verifaid\Image;
use Verifaid\Tests\Support\Images;

/**
 * Menguji CurlTransport terhadap server bawaan PHP yang berjalan di localhost.
 */
final class CurlTransportTest extends TestCase
{
    /** @var resource|null */
    private static $process;

    private static string $baseUrl;

    public static function setUpBeforeClass(): void
    {
        $port = self::findFreePort();
        $router = dirname(__DIR__) . '/Fixtures/server.php';

        $process = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:' . $port, $router],
            [0 => ['pipe', 'r'], 1 => ['file', self::nullDevice(), 'w'], 2 => ['file', self::nullDevice(), 'w']],
            $pipes
        );

        if (!is_resource($process)) {
            throw new RuntimeException('Gagal menjalankan server PHP untuk integration test.');
        }

        self::$process = $process;
        self::$baseUrl = 'http://127.0.0.1:' . $port . '/api/v1/';

        $deadline = microtime(true) + 10;
        while (microtime(true) < $deadline) {
            $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
            if ($socket !== false) {
                fclose($socket);

                return;
            }
            usleep(100000);
        }

        throw new RuntimeException('Server PHP untuk integration test tidak kunjung siap.');
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$process)) {
            proc_terminate(self::$process);
            proc_close(self::$process);
        }
    }

    public function testUploadsImageAsMultipart(): void
    {
        $image = Image::fromString(Images::png(), 'ktp-budi.png');

        $data = $this->client()->request('POST', 'ocr/ktp', null, ['image' => $image])->getData();

        $this->assertSame('POST', $data['method']);
        $this->assertSame('/api/v1/ocr/ktp', $data['path']);
        $this->assertSame('Bearer sv_live_test', $data['authorization']);
        $this->assertSame('application/json', $data['accept']);
        $this->assertStringStartsWith('verifaid-php/', $data['user_agent']);
        $this->assertStringStartsWith('multipart/form-data', $data['content_type']);
        $this->assertNull($data['expect']);
        $this->assertSame([
            'name'  => 'ktp-budi.png',
            'type'  => 'image/png',
            'size'  => strlen(Images::png()),
            'error' => UPLOAD_ERR_OK,
            'md5'   => md5(Images::png()),
        ], $data['files']['image']);
    }

    public function testOcrResourceUploadsFileFromPath(): void
    {
        $path = Images::write(Images::jpeg());

        try {
            $data = $this->client()->ocr()->sim($path);
        } finally {
            @unlink($path);
        }

        $this->assertSame('/api/v1/ocr/sim', $data['path']);
        $this->assertSame('image/jpeg', $data['files']['image']['type']);
        $this->assertSame(basename($path), $data['files']['image']['name']);
    }

    public function testSendsJsonBody(): void
    {
        $data = $this->client()->request('POST', 'custom/endpoint', ['nama' => 'Budi', 'jumlah' => 3])->getData();

        $this->assertSame('/api/v1/custom/endpoint', $data['path']);
        $this->assertSame('application/json', $data['content_type']);
        $this->assertSame(['nama' => 'Budi', 'jumlah' => 3], json_decode($data['body'], true));
    }

    public function testSendsGetWithoutBody(): void
    {
        $data = $this->client()->request('GET', 'custom/endpoint')->getData();

        $this->assertSame('GET', $data['method']);
        $this->assertSame('/api/v1/custom/endpoint', $data['path']);
        $this->assertSame('', $data['body']);
    }

    public function testSendsEmptyPostWithContentLength(): void
    {
        $data = $this->client('sv_h2h_test')->h2h()->quota();

        $this->assertSame('POST', $data['method']);
        $this->assertSame('0', $data['content_length']);
    }

    public function testReadsRetryAfterHeader(): void
    {
        try {
            $this->client()->request('POST', 'rate-limited');
            $this->fail('Seharusnya melempar RateLimitException.');
        } catch (RateLimitException $e) {
            $this->assertSame(429, $e->getStatusCode());
            $this->assertSame(42, $e->getRetryAfter());
            $this->assertSame('Terlalu banyak request.', $e->getMessage());
        }
    }

    public function testHandlesHtmlErrorPage(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('HTTP 502');

        $this->client()->request('GET', 'bad-gateway');
    }

    public function testThrowsConnectionExceptionWhenServerIsDown(): void
    {
        $client = new Client('sv_live_test', [
            'base_url'        => 'http://127.0.0.1:' . self::findFreePort() . '/api/v1/',
            'connect_timeout' => 2,
        ]);

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Gagal terhubung ke API VerifAID');

        $client->h2h()->quota();
    }

    private function client(string $apiKey = 'sv_live_test'): Client
    {
        return new Client($apiKey, ['base_url' => self::$baseUrl, 'timeout' => 10]);
    }

    private static function findFreePort(): int
    {
        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if ($server === false) {
            throw new RuntimeException('Tidak bisa mencari port kosong: ' . $errstr);
        }

        $name = (string) stream_socket_get_name($server, false);
        fclose($server);

        return (int) substr($name, strrpos($name, ':') + 1);
    }

    private static function nullDevice(): string
    {
        return DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null';
    }
}
