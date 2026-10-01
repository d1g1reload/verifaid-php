<?php

declare(strict_types=1);

namespace Verifaid\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Verifaid\Exception\InvalidArgumentException;
use Verifaid\Image;
use Verifaid\Tests\Support\Images;

final class ImageTest extends TestCase
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
     * @dataProvider formatProvider
     */
    public function testDetectsFormatFromContent(string $contents, string $expectedMime): void
    {
        // Ekstensi sengaja dibuat salah: deteksi harus dari isi file.
        $image = Image::fromPath($this->tempImage($contents, 'bin'));

        $this->assertSame($expectedMime, $image->getMimeType());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function formatProvider(): array
    {
        return [
            'jpeg' => [Images::jpeg(), 'image/jpeg'],
            'png'  => [Images::png(), 'image/png'],
            'webp' => [Images::webp(), 'image/webp'],
        ];
    }

    public function testRejectsUnsupportedFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JPEG, PNG, atau WEBP');

        Image::fromPath($this->tempImage(Images::gif()));
    }

    public function testRejectsMissingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak ditemukan');

        Image::fromPath(sys_get_temp_dir() . '/verifaid-tidak-ada.jpg');
    }

    public function testUsesBasenameAsFilename(): void
    {
        $path = $this->tempImage(Images::jpeg());

        $this->assertSame(basename($path), Image::fromPath($path)->getFilename());
    }

    public function testAddsExtensionWhenFilenameHasNone(): void
    {
        $image = Image::fromPath($this->tempImage(Images::png()), 'ktp');

        $this->assertSame('ktp.png', $image->getFilename());
    }

    public function testFromStringWritesTemporaryFileAndRemovesIt(): void
    {
        $image = Image::fromString(Images::jpeg(), 'sim.jpg');
        $path = $image->getPath();

        $this->assertFileExists($path);
        $this->assertSame(Images::jpeg(), file_get_contents($path));
        $this->assertSame('sim.jpg', $image->getFilename());

        unset($image);

        $this->assertFileDoesNotExist($path);
    }

    public function testFromPathDoesNotRemoveOriginalFile(): void
    {
        $path = $this->tempImage(Images::jpeg());

        $image = Image::fromPath($path);
        unset($image);

        $this->assertFileExists($path);
    }

    public function testFromBase64(): void
    {
        $image = Image::fromBase64(base64_encode(Images::png()));

        $this->assertSame('image/png', $image->getMimeType());
        $this->assertSame('image.png', $image->getFilename());
        $this->assertSame(Images::png(), file_get_contents($image->getPath()));
    }

    public function testFromBase64WithDataUri(): void
    {
        $dataUri = 'data:image/webp;base64,' . chunk_split(base64_encode(Images::webp()), 20);

        $image = Image::fromBase64($dataUri, 'kk');

        $this->assertSame('image/webp', $image->getMimeType());
        $this->assertSame('kk.webp', $image->getFilename());
    }

    public function testFromBase64RejectsInvalidString(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Image::fromBase64('bukan base64!!');
    }

    public function testFromAcceptsSplFileInfo(): void
    {
        $path = $this->tempImage(Images::jpeg());

        $image = Image::from(new SplFileInfo($path));

        $this->assertSame($path, $image->getPath());
        $this->assertSame(basename($path), $image->getFilename());
    }

    public function testFromRejectsOtherTypes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('integer diberikan');

        Image::from(123);
    }

    private function tempImage(string $contents, string $extension = 'jpg'): string
    {
        $path = Images::write($contents, $extension);
        $this->tempFiles[] = $path;

        return $path;
    }
}
