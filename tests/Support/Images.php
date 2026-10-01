<?php

declare(strict_types=1);

namespace Verifaid\Tests\Support;

/**
 * Isi file palsu yang cukup untuk dikenali sebagai JPEG, PNG, atau WEBP.
 */
final class Images
{
    public static function jpeg(): string
    {
        return "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00" . str_repeat("\x00", 32);
    }

    public static function png(): string
    {
        return "\x89PNG\r\n\x1A\n" . str_repeat("\x00", 32);
    }

    public static function webp(): string
    {
        return "RIFF\x24\x00\x00\x00WEBPVP8 " . str_repeat("\x00", 32);
    }

    public static function gif(): string
    {
        return 'GIF89a' . str_repeat("\x00", 32);
    }

    /**
     * Tulis isi ke file sementara dan kembalikan path-nya.
     */
    public static function write(string $contents, string $extension = 'jpg'): string
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'verifaid_test_' . bin2hex(random_bytes(6)) . '.' . $extension;
        file_put_contents($path, $contents);

        return $path;
    }
}
