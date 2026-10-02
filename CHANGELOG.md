# Changelog

Semua perubahan penting pada package ini dicatat di sini. Format mengikuti
[Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/) dan penomoran versi
mengikuti [Semantic Versioning](https://semver.org/lang/id/).

## [1.0.2] - 2026-10-02

### Diubah

- `ocr()` menolak key `sv_h2h_` dan `h2h()` menolak key `sv_live_` dengan `InvalidArgumentException` yang menyebutkan resource yang benar, menggantikan error 401 "API key tidak valid" dari server.
- README: key `sv_h2h_` dikirim tim VerifAID lewat email setelah invoice aktivasi lunas, bukan dibuat di dashboard.

## [1.0.1] - 2026-10-01

### Diubah

- Deskripsi paket kini berbahasa Inggris.
- Email kontak paket diganti ke dgireloadpay@gmail.com.

## [1.0.0] - 2026-10-01

### Ditambahkan

- OCR e-KTP, SIM, NPWP, BPJS, dan Kartu Keluarga untuk API key self-service (`ocr()`).
- OCR dan cek kuota untuk klien Host-to-Host (`h2h()`).
- Input gambar dari path, `SplFileInfo` (termasuk UploadedFile Laravel/Symfony), isi file mentah, dan base64.
- Exception terpisah untuk setiap jenis error API, termasuk `getRetryAfter()` untuk rate limit.

[1.0.2]: https://github.com/d1g1reload/verifaid-php/releases/tag/v1.0.2
[1.0.1]: https://github.com/d1g1reload/verifaid-php/releases/tag/v1.0.1
[1.0.0]: https://github.com/d1g1reload/verifaid-php/releases/tag/v1.0.0
