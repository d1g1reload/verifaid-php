# VerifAID PHP SDK

[![Latest Version](https://img.shields.io/packagist/v/verifaid/verifaid-php.svg)](https://packagist.org/packages/verifaid/verifaid-php)
[![Tests](https://github.com/d1g1reload/verifaid-php/actions/workflows/tests.yml/badge.svg)](https://github.com/d1g1reload/verifaid-php/actions/workflows/tests.yml)
[![PHP Version](https://img.shields.io/packagist/php-v/verifaid/verifaid-php.svg)](https://packagist.org/packages/verifaid/verifaid-php)
[![License](https://img.shields.io/packagist/l/verifaid/verifaid-php.svg)](LICENSE)

SDK PHP resmi untuk [VerifAID](https://verifaid.my.id). Ekstrak data e-KTP, SIM, NPWP, BPJS, dan Kartu Keluarga dari foto dalam satu baris kode, serta terima pembayaran QRIS dan Virtual Account.

```php
$verifaid = new \Verifaid\Client('sv_live_xxx');

$ktp = $verifaid->ocr()->ktp('/path/ke/ktp.jpg');

echo $ktp['nik'];        // 3201010101010001
echo $ktp['full_name'];  // BUDI SANTOSO
```

## Persyaratan

- PHP 7.4 atau lebih baru (sudah diuji sampai PHP 8.4)
- Ekstensi `curl` dan `json`

SDK ini tidak punya dependensi lain, jadi tidak akan bentrok dengan package di proyek Anda.

## Instalasi

```bash
composer require verifaid/verifaid-php
```

## API key

Buat API key di [dashboard VerifAID](https://verifaid.my.id). Jenis key menentukan layanan yang bisa dipakai:

| Prefix key | Layanan | Dipakai lewat |
| --- | --- | --- |
| `sv_live_` | OCR self-service, kuota dari top-up | `$verifaid->ocr()` |
| `sv_h2h_` | OCR Host-to-Host untuk klien enterprise | `$verifaid->h2h()` |
| `SB-Mid-` | Payment Gateway, sandbox | `$verifaid->payment()` |
| `PR-Mid-` | Payment Gateway, production (uang riil) | `$verifaid->payment()` |

Simpan API key di environment variable, jangan di kode:

```php
$verifaid = new \Verifaid\Client(getenv('VERIFAID_API_KEY'));
```

## OCR dokumen

Setiap method mengirim satu gambar dan mengembalikan array berisi data dokumen.

| Method | Dokumen | Field utama |
| --- | --- | --- |
| `ktp($image)` | e-KTP | `nik`, `full_name`, `birth_place_date`, `address_ktp`, `rt_rw`, `village`, `subdistrict`, `city`, `province`, ... |
| `sim($image)` | SIM | `license_type`, `license_number`, `full_name`, `valid_until`, ... |
| `npwp($image)` | NPWP | `npwp_number`, `full_name`, `nik`, `address_npwp`, `kpp_name` |
| `bpjs($image)` | BPJS Kesehatan / KIS | `card_number`, `full_name`, `nik`, `birth_date`, `faskes_tingkat_1`, ... |
| `kk($image)` | Kartu Keluarga | `informasi_keluarga` (header KK) dan `anggota_keluarga` (daftar anggota) |

Daftar field lengkap ada di docblock setiap method, jadi IDE seperti PhpStorm dan VS Code bisa melengkapi nama field secara otomatis.

Field yang tidak ada atau tidak terbaca pada dokumen dikembalikan sebagai string kosong `""`, bukan dihilangkan.

```php
$kk = $verifaid->ocr()->kk('/path/ke/kk.jpg');

echo $kk['informasi_keluarga']['nomor_kk'];

foreach ($kk['anggota_keluarga'] as $anggota) {
    echo $anggota['nama_lengkap'] . ' - ' . $anggota['status_hubungan'] . PHP_EOL;
}
```

### Input gambar

Format yang diterima: JPEG, PNG, dan WEBP. Format dideteksi dari isi file, bukan dari ekstensinya. File yang formatnya salah ditolak sebelum dikirim, jadi tidak memakan kuota.

```php
use Verifaid\Image;

// Path file
$verifaid->ocr()->ktp('/path/ke/ktp.jpg');

// File upload Laravel / Symfony
$verifaid->ocr()->ktp($request->file('ktp'));

// Isi file mentah, misalnya dari S3
$verifaid->ocr()->ktp(Image::fromString($binary, 'ktp.jpg'));

// Base64, dengan atau tanpa prefix "data:image/jpeg;base64,"
$verifaid->ocr()->ktp(Image::fromBase64($request->input('foto_ktp')));
```

### Kuota

Satu ekstraksi memotong satu hit. Gambar yang ditolak karena terlalu buram tidak memotong kuota. Bila kuota habis, SDK melempar `QuotaExceededException`.

## Host-to-Host (H2H)

Untuk klien enterprise dengan key `sv_h2h_`. Method-nya sama dengan OCR, ditambah cek kuota:

```php
$verifaid = new \Verifaid\Client('sv_h2h_xxx');

$sim = $verifaid->h2h()->sim('/path/ke/sim.jpg');

$kuota = $verifaid->h2h()->quota();
echo $kuota['tier'];            // PREMIUM
echo $kuota['remaining_hits'];  // 950
```

Jenis dokumen yang bisa dipakai bergantung pada paket:

| Paket | Dokumen |
| --- | --- |
| BASIC | KTP |
| BUSINESS | KTP, SIM, NPWP, BPJS |
| PREMIUM | KTP, SIM, NPWP, BPJS, KK |

Meminta dokumen di luar paket menghasilkan `PermissionDeniedException`. Kuota H2H hanya terpotong bila ekstraksi berhasil.

## Payment Gateway

Gunakan key `SB-Mid-` untuk uji coba dan `PR-Mid-` untuk transaksi riil. SDK memilih endpoint sandbox atau production otomatis dari prefix key.

### Membuat tagihan

```php
$verifaid = new \Verifaid\Client('SB-Mid-xxx');

// QRIS
$tagihan = $verifaid->payment()->createQris('INV-2026-0001', 25000);
echo $tagihan['payment_url'];  // arahkan pelanggan ke halaman ini

// Virtual Account
$tagihan = $verifaid->payment()->createVa('INV-2026-0002', 150000, 'BRIVA');
echo $tagihan['va_number'];
```

`merchant_order_id` adalah ID pesanan dari sistem Anda dan akan dikirim balik saat pembayaran lunas. Nominal minimal Rp 10.000.

### Riwayat transaksi dan saldo

```php
$riwayat = $verifaid->payment()->transactions([
    'status'     => 'success',     // pending, success, failed, expired
    'start_date' => '2026-09-01',  // start_date dan end_date harus diisi berdua
    'end_date'   => '2026-09-30',
    'page'       => 1,
    'limit'      => 50,            // maksimal 100
]);

foreach ($riwayat['records'] as $trx) {
    echo $trx['merchant_order_id'] . ': ' . $trx['status'] . PHP_EOL;
}

$saldo = $verifaid->payment()->balance();
echo $saldo['available_balance'];
```

### Penarikan dana

Hanya bisa dengan key production. Minimal Rp 50.000; saldo langsung dipotong, dan biaya transfer diambil dari nominal tersebut.

```php
$verifaid = new \Verifaid\Client('PR-Mid-xxx');

$penarikan = $verifaid->payment()->withdraw(500000, 'BCA', '1234567890', 'BUDI SANTOSO');
echo $penarikan['reference_code'];

$daftar = $verifaid->payment()->withdrawals(['status' => 'pending']);
```

### Notifikasi pembayaran (webhook)

Saat tagihan lunas, VerifAID mengirim `POST` JSON ke Callback URL yang Anda atur di dashboard:

```json
{
    "merchant_order_id": "INV-2026-0001",
    "amount": 25000,
    "net_amount": 24825,
    "status": "success",
    "environment": "sandbox",
    "paid_at": "2026-10-01 14:30:00"
}
```

Contoh penerima webhook:

```php
$payload = json_decode(file_get_contents('php://input'), true);

$pesanan = Pesanan::where('kode', $payload['merchant_order_id'])->first();

if ($pesanan && $pesanan->total === $payload['amount'] && $pesanan->status === 'menunggu') {
    $pesanan->tandaiLunas();
}

http_response_code(200);
```

Untuk keamanan, pastikan `merchant_order_id` dan `amount` cocok dengan data di sistem Anda sebelum memproses pesanan, dan konfirmasi statusnya lewat `transactions()` untuk transaksi bernilai besar.

## Menangani error

Setiap error dari API dilempar sebagai exception. Pesannya diambil dari respons server, dan kode exception berisi status HTTP.

```php
use Verifaid\Exception\QuotaExceededException;
use Verifaid\Exception\RateLimitException;
use Verifaid\Exception\UnprocessableEntityException;
use Verifaid\Exception\VerifaidException;

try {
    $ktp = $verifaid->ocr()->ktp($path);
} catch (UnprocessableEntityException $e) {
    // Gambar buram atau bukan KTP. Minta pengguna memfoto ulang.
    return back()->withErrors(['ktp' => $e->getMessage()]);
} catch (QuotaExceededException $e) {
    // Kuota habis, perlu top-up.
} catch (RateLimitException $e) {
    sleep($e->getRetryAfter() ?? 60);
} catch (VerifaidException $e) {
    // Semua error lain dari SDK ini.
    report($e);
}
```

| Exception | Kapan terjadi |
| --- | --- |
| `BadRequestException` (400) | Parameter tidak lengkap atau tidak valid |
| `AuthenticationException` (401) | API key salah atau tidak ditemukan |
| `QuotaExceededException` (402) | Kuota hit habis |
| `PermissionDeniedException` (403) | Key/akun dinonaktifkan, dokumen di luar paket H2H, atau key sandbox dipakai di production |
| `NotFoundException` (404) | Endpoint tidak ada, biasanya karena `base_url` keliru |
| `UnprocessableEntityException` (422) | OCR: gambar buram atau jenis dokumen salah. Payment: tagihan ditolak payment gateway |
| `RateLimitException` (429) | Batas request per menit terlampaui; lihat `getRetryAfter()` |
| `ServerException` (5xx) | Gangguan di server VerifAID |
| `ConnectionException` | Server tidak bisa dihubungi (DNS, timeout, SSL) |
| `InvalidArgumentException` | Input salah sebelum request dikirim, misalnya file tidak ada atau format gambar tidak didukung |

Semua exception di atas mengimplementasikan `Verifaid\Exception\VerifaidException`. Exception dari API juga menyediakan `getStatusCode()`, `getErrorData()`, dan `getResponse()`.

SDK tidak mengulang request secara otomatis. Request OCR yang terkena timeout bisa saja tetap selesai diproses di server, sehingga mengulangnya bisa memotong kuota dua kali.

## Konfigurasi

```php
$verifaid = new \Verifaid\Client('sv_live_xxx', [
    'timeout'         => 120,        // detik, total per request
    'connect_timeout' => 10,         // detik, untuk membuka koneksi
    'base_url'        => 'https://verifaid.my.id/api/v1/',
    'environment'     => 'sandbox',  // hanya untuk Payment, bila tidak ingin ditebak dari prefix key
]);
```

| Opsi | Bawaan | Keterangan |
| --- | --- | --- |
| `timeout` | `120` | OCR dengan AI bisa butuh puluhan detik, jadi jangan terlalu kecil |
| `connect_timeout` | `10` | |
| `base_url` | `https://verifaid.my.id/api/v1/` | |
| `environment` | dari prefix key | `sandbox` atau `production` |
| `transport` | `CurlTransport` | Implementasi `Verifaid\Http\TransportInterface`, misalnya untuk testing |

## Contoh di Laravel

Daftarkan client di `AppServiceProvider`:

```php
use Verifaid\Client;

public function register(): void
{
    $this->app->singleton(Client::class, fn () => new Client(config('services.verifaid.key')));
}
```

Tambahkan di `config/services.php`:

```php
'verifaid' => [
    'key' => env('VERIFAID_API_KEY'),
],
```

Lalu pakai di controller:

```php
use Verifaid\Client;
use Verifaid\Exception\UnprocessableEntityException;

public function verifikasi(Request $request, Client $verifaid)
{
    $request->validate(['ktp' => 'required|image|max:5120']);

    try {
        $ktp = $verifaid->ocr()->ktp($request->file('ktp'));
    } catch (UnprocessableEntityException $e) {
        return back()->withErrors(['ktp' => $e->getMessage()]);
    }

    $request->user()->update([
        'nik'  => $ktp['nik'],
        'nama' => $ktp['full_name'],
    ]);

    return back()->with('status', 'KTP berhasil diverifikasi.');
}
```

## Memanggil endpoint lain

Untuk endpoint yang belum punya method khusus, gunakan `request()`. Autentikasi dan penanganan error tetap berlaku.

```php
$response = $verifaid->request('POST', 'endpoint/baru', ['kunci' => 'nilai']);

$response->getStatusCode();
$response->getMessage();
$response->getData();
```

## Pertanyaan umum

**`ConnectionException: SSL certificate problem: unable to get local issuer certificate`**

Instalasi PHP Anda belum punya daftar sertifikat CA, sering terjadi di XAMPP atau Laragon di Windows. Unduh [cacert.pem](https://curl.se/docs/caextract.html), lalu isi `curl.cainfo` di `php.ini` dengan path file tersebut.

**Request OCR timeout**

Naikkan opsi `timeout`. Pastikan juga `max_execution_time` PHP Anda lebih besar dari nilai tersebut.

## Pengembangan

```bash
composer install
composer test
```

Test integrasi menjalankan server bawaan PHP di localhost, jadi tidak memerlukan API key dan tidak menghubungi server VerifAID.

## Lisensi

MIT. Lihat [LICENSE](LICENSE).
