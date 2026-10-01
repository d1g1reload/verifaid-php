<?php

declare(strict_types=1);

namespace Verifaid;

use SplFileInfo;
use Verifaid\Exception\InvalidArgumentException;

/**
 * Gambar dokumen yang akan diunggah. Format dideteksi dari isi file (bukan
 * dari ekstensi), dan hanya JPEG, PNG, serta WEBP yang diterima.
 *
 * Semua method OCR menerima path file, objek SplFileInfo (termasuk
 * UploadedFile Laravel/Symfony), atau objek Image dari fromString()/fromBase64().
 */
final class Image
{
    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    private string $path;

    private string $mimeType;

    private string $filename;

    private bool $temporary;

    private function __construct(string $path, string $mimeType, string $filename, bool $temporary)
    {
        $this->path = $path;
        $this->mimeType = $mimeType;
        $this->filename = self::normalizeFilename($filename, $mimeType);
        $this->temporary = $temporary;
    }

    public function __destruct()
    {
        if ($this->temporary && is_file($this->path)) {
            @unlink($this->path);
        }
    }

    /**
     * @param string|SplFileInfo|Image $image Path file, SplFileInfo, atau Image.
     */
    public static function from($image): self
    {
        if ($image instanceof self) {
            return $image;
        }

        if ($image instanceof SplFileInfo) {
            // UploadedFile disimpan di path sementara tanpa nama asli.
            $filename = method_exists($image, 'getClientOriginalName')
                ? (string) $image->getClientOriginalName()
                : $image->getFilename();

            return self::fromPath($image->getPathname(), $filename);
        }

        if (is_string($image)) {
            return self::fromPath($image);
        }

        throw new InvalidArgumentException(sprintf(
            'Gambar harus berupa path file, SplFileInfo, atau %s; %s diberikan.',
            self::class,
            is_object($image) ? get_class($image) : gettype($image)
        ));
    }

    /**
     * @param string|null $filename Nama file yang dikirim ke server. Bawaannya nama file dari path.
     */
    public static function fromPath(string $path, ?string $filename = null): self
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidArgumentException(sprintf('File gambar tidak ditemukan atau tidak bisa dibaca: %s', $path));
        }

        $handle = fopen($path, 'rb');
        $head = $handle === false ? false : fread($handle, 12);
        if ($handle !== false) {
            fclose($handle);
        }

        if ($head === false) {
            throw new InvalidArgumentException(sprintf('Gagal membaca file gambar: %s', $path));
        }

        return new self($path, self::detectMimeType($head), $filename ?? basename($path), false);
    }

    /**
     * Buat gambar dari isi file mentah (binary), misalnya hasil file_get_contents()
     * atau unduhan dari storage. Disimpan ke file sementara yang otomatis dihapus.
     */
    public static function fromString(string $contents, string $filename = 'image'): self
    {
        $mimeType = self::detectMimeType(substr($contents, 0, 12));

        $path = tempnam(sys_get_temp_dir(), 'verifaid_');
        if ($path === false || file_put_contents($path, $contents) === false) {
            throw new InvalidArgumentException('Gagal menulis gambar ke file sementara.');
        }

        return new self($path, $mimeType, $filename, true);
    }

    /**
     * Buat gambar dari string base64, dengan atau tanpa prefix data URI
     * ("data:image/jpeg;base64,...") seperti yang dikirim dari browser atau aplikasi mobile.
     */
    public static function fromBase64(string $base64, string $filename = 'image'): self
    {
        $base64 = trim($base64);

        if (strncmp($base64, 'data:', 5) === 0) {
            $comma = strpos($base64, ',');
            $base64 = $comma === false ? '' : substr($base64, $comma + 1);
        }

        $contents = base64_decode(preg_replace('/\s+/', '', $base64) ?? '', true);
        if ($contents === false || $contents === '') {
            throw new InvalidArgumentException('String base64 gambar tidak valid.');
        }

        return self::fromString($contents, $filename);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    private static function detectMimeType(string $head): string
    {
        if (strncmp($head, "\xFF\xD8\xFF", 3) === 0) {
            return 'image/jpeg';
        }

        if (strncmp($head, "\x89PNG\r\n\x1A\n", 8) === 0) {
            return 'image/png';
        }

        if (strncmp($head, 'RIFF', 4) === 0 && substr($head, 8, 4) === 'WEBP') {
            return 'image/webp';
        }

        throw new InvalidArgumentException('Format gambar tidak didukung. Gunakan JPEG, PNG, atau WEBP.');
    }

    private static function normalizeFilename(string $filename, string $mimeType): string
    {
        $filename = basename(str_replace('\\', '/', trim($filename)));
        if ($filename === '' || $filename === '.') {
            $filename = 'image';
        }

        if (pathinfo($filename, PATHINFO_EXTENSION) === '') {
            $filename .= '.' . self::EXTENSIONS[$mimeType];
        }

        return $filename;
    }
}
