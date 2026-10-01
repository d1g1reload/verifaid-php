# Changelog

Semua perubahan penting pada package ini dicatat di sini. Format mengikuti
[Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/) dan penomoran versi
mengikuti [Semantic Versioning](https://semver.org/lang/id/).

## [1.0.0] - 2026-10-01

### Ditambahkan

- OCR e-KTP, SIM, NPWP, BPJS, dan Kartu Keluarga untuk API key self-service (`ocr()`).
- OCR dan cek kuota untuk klien Host-to-Host (`h2h()`).
- Payment Gateway: tagihan QRIS dan Virtual Account, riwayat transaksi, saldo, dan penarikan dana (`payment()`), dengan pemilihan sandbox/production otomatis dari prefix API key.
- Input gambar dari path, `SplFileInfo` (termasuk UploadedFile Laravel/Symfony), isi file mentah, dan base64.
- Exception terpisah untuk setiap jenis error API, termasuk `getRetryAfter()` untuk rate limit.

[1.0.0]: https://github.com/d1g1reload/verifaid-php/releases/tag/v1.0.0
