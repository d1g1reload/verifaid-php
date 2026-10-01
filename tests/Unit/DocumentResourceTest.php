<?php

declare(strict_types=1);

namespace Verifaid\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Verifaid\Client;
use Verifaid\Exception\InvalidArgumentException;
use Verifaid\Image;
use Verifaid\Resource\DocumentResource;
use Verifaid\Tests\Support\FakeTransport;
use Verifaid\Tests\Support\Images;

final class DocumentResourceTest extends TestCase
{
    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            @unlink($path);
        }
    }

    /**
     * @dataProvider documentProvider
     */
    public function testUploadsImageToDocumentEndpoint(string $resource, string $document): void
    {
        $data = ['nik' => '3201010101010001', 'full_name' => 'BUDI SANTOSO'];
        $transport = (new FakeTransport())->pushSuccess($data);
        $client = new Client('sv_live_x', ['transport' => $transport]);
        $path = $this->tempImage(Images::jpeg());

        /** @var DocumentResource $api */
        $api = $client->{$resource}();
        $result = $api->{$document}($path);

        $request = $transport->lastRequest();
        $this->assertSame($data, $result);
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame("https://verifaid.my.id/api/v1/{$resource}/{$document}", $request->getUrl());
        $this->assertNull($request->getJson());
        $this->assertSame(['image'], array_keys($request->getFiles()));
        $this->assertSame($path, $request->getFiles()['image']->getPath());
        $this->assertSame('image/jpeg', $request->getFiles()['image']->getMimeType());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function documentProvider(): array
    {
        $cases = [];
        foreach (['ocr', 'h2h'] as $resource) {
            foreach (['ktp', 'sim', 'npwp', 'bpjs', 'kk'] as $document) {
                $cases["{$resource}/{$document}"] = [$resource, $document];
            }
        }

        return $cases;
    }

    public function testAcceptsUploadedFileWithClientOriginalName(): void
    {
        $transport = (new FakeTransport())->pushSuccess();
        $client = new Client('sv_live_x', ['transport' => $transport]);

        // Meniru UploadedFile Laravel/Symfony: path sementara tanpa ekstensi, nama asli terpisah.
        $upload = new class ($this->tempImage(Images::png(), 'tmp')) extends SplFileInfo {
            public function getClientOriginalName(): string
            {
                return 'ktp-budi.png';
            }
        };

        $client->ocr()->ktp($upload);

        $image = $transport->lastRequest()->getFiles()['image'];
        $this->assertSame('ktp-budi.png', $image->getFilename());
        $this->assertSame('image/png', $image->getMimeType());
    }

    public function testAcceptsImageObject(): void
    {
        $transport = (new FakeTransport())->pushSuccess();
        $client = new Client('sv_live_x', ['transport' => $transport]);
        $image = Image::fromString(Images::webp(), 'npwp');

        $client->ocr()->npwp($image);

        $this->assertSame($image, $transport->lastRequest()->getFiles()['image']);
    }

    public function testRejectsInvalidImageBeforeSending(): void
    {
        $transport = new FakeTransport();
        $client = new Client('sv_live_x', ['transport' => $transport]);

        try {
            $client->ocr()->ktp($this->tempImage(Images::gif()));
            $this->fail('Seharusnya melempar InvalidArgumentException.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame([], $transport->requests);
        }
    }

    public function testH2hQuota(): void
    {
        $data = ['client_name' => 'PT Contoh', 'tier' => 'PREMIUM', 'remaining_hits' => 950];
        $transport = (new FakeTransport())->pushSuccess($data);
        $client = new Client('sv_h2h_x', ['transport' => $transport]);

        $this->assertSame($data, $client->h2h()->quota());

        $request = $transport->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https://verifaid.my.id/api/v1/h2h/quota', $request->getUrl());
        $this->assertNull($request->getJson());
        $this->assertSame([], $request->getFiles());
    }

    private function tempImage(string $contents, string $extension = 'jpg'): string
    {
        $path = Images::write($contents, $extension);
        $this->tempFiles[] = $path;

        return $path;
    }
}
