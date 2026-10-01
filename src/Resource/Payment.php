<?php

declare(strict_types=1);

namespace Verifaid\Resource;

use Verifaid\Client;
use Verifaid\Exception\ApiException;
use Verifaid\Exception\ConnectionException;
use Verifaid\Exception\InvalidArgumentException;

/**
 * Payment Gateway VerifAID: tagihan QRIS dan Virtual Account, riwayat
 * transaksi, saldo, dan penarikan dana.
 *
 * Endpoint sandbox atau production dipilih otomatis dari prefix API key
 * (SB-Mid-... atau PR-Mid-...), atau dari opsi "environment" pada Client.
 */
final class Payment
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Buat tagihan QRIS. Bayar lewat payment_url yang dikembalikan.
     *
     * @param string $merchantOrderId ID pesanan dari sistem Anda; dikirim balik di webhook.
     * @param int    $amount          Nominal dalam rupiah, minimal 10.000.
     *
     * @return array{
     *     merchant_order_id: string,
     *     amount: int,
     *     payment_method: string,
     *     payment_url: string,
     *     environment: string,
     *     status: string
     * }
     *
     * @throws InvalidArgumentException|ApiException|ConnectionException
     */
    public function createQris(string $merchantOrderId, int $amount): array
    {
        return $this->client->request('POST', $this->environment() . '/qris', [
            'merchant_order_id' => $merchantOrderId,
            'amount'            => $amount,
        ])->getData();
    }

    /**
     * Buat tagihan Virtual Account.
     *
     * @param string $merchantOrderId ID pesanan dari sistem Anda; dikirim balik di webhook.
     * @param int    $amount          Nominal dalam rupiah, minimal 10.000.
     * @param string $bankCode        Kode bank VA, misalnya "BRIVA", "BNIVA", atau "BSIVA".
     *
     * @return array{
     *     merchant_order_id: string,
     *     amount: int,
     *     payment_method: string,
     *     payment_url: string,
     *     va_number?: string,
     *     environment: string,
     *     status: string
     * }
     *
     * @throws InvalidArgumentException|ApiException|ConnectionException
     */
    public function createVa(string $merchantOrderId, int $amount, string $bankCode): array
    {
        return $this->client->request('POST', $this->environment() . '/va', [
            'merchant_order_id' => $merchantOrderId,
            'amount'            => $amount,
            'bank_code'         => $bankCode,
        ])->getData();
    }

    /**
     * Riwayat tagihan QRIS dan VA, terbaru lebih dulu.
     *
     * @param array{
     *     page?: int,
     *     limit?: int,
     *     status?: 'pending'|'success'|'failed'|'expired',
     *     start_date?: string,
     *     end_date?: string
     * } $filters Tanggal berformat YYYY-MM-DD dan hanya berlaku bila keduanya diisi. Limit maksimal 100.
     *
     * @return array{
     *     total_data: int,
     *     total_pages: int|float,
     *     current_page: int|float,
     *     limit: int,
     *     records: list<array{
     *         merchant_order_id: string,
     *         system_order_id: string,
     *         gross_amount: int,
     *         net_amount: int,
     *         status: string,
     *         created_at: string,
     *         completed_at: string|null
     *     }>
     * }
     *
     * @throws InvalidArgumentException|ApiException|ConnectionException
     */
    public function transactions(array $filters = []): array
    {
        return $this->client->request('POST', $this->environment() . '/history', $filters)->getData();
    }

    /**
     * Saldo dompet. Untuk API key production, ini saldo uang riil.
     *
     * @return array{
     *     available_balance: int,
     *     pending_balance: int,
     *     currency: string,
     *     last_updated: string
     * }
     *
     * @throws InvalidArgumentException|ApiException|ConnectionException
     */
    public function balance(): array
    {
        return $this->client->request('GET', $this->environment() . '/balance')->getData();
    }

    /**
     * Ajukan penarikan saldo ke rekening bank. Hanya bisa dengan API key production.
     * Saldo langsung dipotong sebesar $amount; biaya admin transfer dipotong dari nominal itu.
     *
     * @param int    $amount        Nominal dalam rupiah, minimal 50.000.
     * @param string $bankName      Nama bank tujuan, misalnya "BCA".
     * @param string $accountNumber Nomor rekening tujuan.
     * @param string $accountName   Nama pemilik rekening.
     *
     * @return array{
     *     withdrawal_id: int,
     *     reference_code: string,
     *     amount: int,
     *     admin_fee: int,
     *     net_amount: int,
     *     bank_name: string,
     *     account_number: string,
     *     status: string,
     *     created_at: string
     * }
     *
     * @throws ApiException|ConnectionException
     */
    public function withdraw(int $amount, string $bankName, string $accountNumber, string $accountName): array
    {
        return $this->client->request('POST', 'payment/withdraw', [
            'amount'         => $amount,
            'bank_name'      => $bankName,
            'account_number' => $accountNumber,
            'account_name'   => $accountName,
        ])->getData();
    }

    /**
     * Riwayat penarikan dana, terbaru lebih dulu.
     *
     * @param array{page?: int, limit?: int, status?: string} $filters Limit maksimal 50.
     *
     * @return array{
     *     total_data: int,
     *     total_pages: int|float,
     *     current_page: int,
     *     limit: int,
     *     records: list<array<string, mixed>>
     * }
     *
     * @throws ApiException|ConnectionException
     */
    public function withdrawals(array $filters = []): array
    {
        return $this->client->request('POST', 'payment/history', $filters)->getData();
    }

    private function environment(): string
    {
        $environment = $this->client->environment();

        if ($environment === null) {
            throw new InvalidArgumentException(
                'Environment Payment Gateway tidak diketahui. Gunakan API key berawalan SB-Mid- atau PR-Mid-, '
                . 'atau isi opsi "environment" dengan "sandbox" atau "production".'
            );
        }

        return $environment;
    }
}
