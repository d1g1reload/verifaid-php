<?php

declare(strict_types=1);

namespace Verifaid\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Verifaid\Client;
use Verifaid\Exception\InvalidArgumentException;
use Verifaid\Tests\Support\FakeTransport;

final class PaymentTest extends TestCase
{
    private FakeTransport $transport;

    protected function setUp(): void
    {
        $this->transport = (new FakeTransport())->pushSuccess(['ok' => true]);
    }

    public function testCreateQrisUsesSandboxForSandboxKey(): void
    {
        $result = $this->client('SB-Mid-abc')->payment()->createQris('INV-001', 25000);

        $this->assertSame(['ok' => true], $result);
        $this->assertRequest('POST', 'sandbox/qris', ['merchant_order_id' => 'INV-001', 'amount' => 25000]);
    }

    public function testCreateVaUsesProductionForProductionKey(): void
    {
        $this->client('PR-Mid-abc')->payment()->createVa('INV-002', 50000, 'BRIVA');

        $this->assertRequest('POST', 'production/va', [
            'merchant_order_id' => 'INV-002',
            'amount'            => 50000,
            'bank_code'         => 'BRIVA',
        ]);
    }

    public function testTransactionsSendsFilters(): void
    {
        $filters = ['page' => 2, 'limit' => 50, 'status' => 'success', 'start_date' => '2026-09-01', 'end_date' => '2026-09-30'];

        $this->client('SB-Mid-abc')->payment()->transactions($filters);

        $this->assertRequest('POST', 'sandbox/history', $filters);
    }

    public function testBalanceUsesGet(): void
    {
        $this->client('PR-Mid-abc')->payment()->balance();

        $this->assertRequest('GET', 'production/balance', null);
    }

    public function testWithdraw(): void
    {
        $this->client('PR-Mid-abc')->payment()->withdraw(100000, 'BCA', '1234567890', 'BUDI SANTOSO');

        $this->assertRequest('POST', 'payment/withdraw', [
            'amount'         => 100000,
            'bank_name'      => 'BCA',
            'account_number' => '1234567890',
            'account_name'   => 'BUDI SANTOSO',
        ]);
    }

    public function testWithdrawals(): void
    {
        $this->client('PR-Mid-abc')->payment()->withdrawals(['status' => 'pending']);

        $this->assertRequest('POST', 'payment/history', ['status' => 'pending']);
    }

    public function testEnvironmentOptionOverridesKeyPrefix(): void
    {
        $this->client('custom-key', ['environment' => 'sandbox'])->payment()->balance();

        $this->assertRequest('GET', 'sandbox/balance', null);
    }

    public function testThrowsWhenEnvironmentIsUnknown(): void
    {
        $transport = new FakeTransport();
        $client = new Client('sv_live_abc', ['transport' => $transport]);

        try {
            $client->payment()->createQris('INV-003', 25000);
            $this->fail('Seharusnya melempar InvalidArgumentException.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('SB-Mid-', $e->getMessage());
            $this->assertSame([], $transport->requests);
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    private function client(string $apiKey, array $options = []): Client
    {
        return new Client($apiKey, $options + ['transport' => $this->transport]);
    }

    /**
     * @param array<string, mixed>|null $json
     */
    private function assertRequest(string $method, string $path, ?array $json): void
    {
        $request = $this->transport->lastRequest();

        $this->assertSame($method, $request->getMethod());
        $this->assertSame('https://verifaid.my.id/api/v1/' . $path, $request->getUrl());
        $this->assertSame($json, $request->getJson());
    }
}
