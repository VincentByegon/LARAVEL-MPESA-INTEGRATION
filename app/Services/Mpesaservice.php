<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MpesaService
{
    protected string $consumerKey;
    protected string $consumerSecret;
    protected string $shortCode;
    protected string $baseUrl;
    protected string $callbackUrl;

    public function __construct()
    {
        $this->consumerKey    = config('Mpesa.consumer_key');
        $this->consumerSecret = config('Mpesa.consumer_secret');
        $this->shortCode      = config('Mpesa.shortcode');
        $this->baseUrl        = config('Mpesa.base_url');
        $this->callbackUrl    = config('Mpesa.callback_url');
    }

    /**
     * Get OAuth access token (cached for 55 minutes).
     */
    public function getAccessToken(): string
    {
        return Cache::remember('mpesa_access_token', 3300, function () {
            $credentials = base64_encode("{$this->consumerKey}:{$this->consumerSecret}");

            $response = Http::withHeaders([
                'Authorization' => "Basic {$credentials}",
            ])->get("{$this->baseUrl}/oauth/v1/generate?grant_type=client_credentials");

            if ($response->failed()) {
                Log::error('M-Pesa token fetch failed', ['response' => $response->body()]);
                throw new \Exception('Failed to get M-Pesa access token: ' . $response->body());
            }

            return $response->json('access_token');
        });
    }

    /**
     * Register C2B Validation and Confirmation URLs with Safaricom.
     * Call this ONCE when deploying. Safaricom will POST to these URLs on payment.
     */
    public function registerC2BUrls(): array
    {
        $token = $this->getAccessToken();

        $payload = [
            'ShortCode'       => $this->shortCode,
            'ResponseType'    => 'Completed',  // "Completed" skips validation, auto-accepts all payments
            'ConfirmationURL' => "{$this->callbackUrl}/api/mpesa/confirmation",
            'ValidationURL'   => "{$this->callbackUrl}/api/mpesa/validation",
        ];

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/mpesa/c2b/v1/registerurl", $payload);

        Log::info('M-Pesa C2B URL Registration', [
            'payload'  => $payload,
            'response' => $response->json(),
        ]);

        return $response->json();
    }

    /**
     * Simulate a C2B payment (sandbox only — for testing).
     */
    public function simulateC2BPayment(string $phone, float $amount, string $billRefNumber = 'TEST'): array
    {
        $token = $this->getAccessToken();

        $payload = [
            'ShortCode'     => $this->shortCode,
            'CommandID'     => 'CustomerBuyGoodsOnline',  // For Till Number
            'Amount'        => (int) $amount,
            'Msisdn'        => $phone,                    // e.g. 254712345678
            'BillRefNumber' => $billRefNumber,
        ];

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/mpesa/c2b/v1/simulate", $payload);

        Log::info('M-Pesa C2B Simulation', [
            'payload'  => $payload,
            'response' => $response->json(),
        ]);

        return $response->json();
    }

    /**
     * Query transaction status (optional utility).
     */
    public function checkTransactionStatus(string $transactionId): array
    {
        $token = $this->getAccessToken();

        $payload = [
            'Initiator'          => config('mpesa.initiator_name'),
            'SecurityCredential' => config('mpesa.security_credential'),
            'CommandID'          => 'TransactionStatusQuery',
            'TransactionID'      => $transactionId,
            'PartyA'             => $this->shortCode,
            'IdentifierType'     => '1',
            'ResultURL'          => "{$this->callbackUrl}/api/mpesa/status-result",
            'QueueTimeOutURL'    => "{$this->callbackUrl}/api/mpesa/status-timeout",
            'Remarks'            => 'Check transaction status',
            'Occasion'           => '',
        ];

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/mpesa/transactionstatus/v1/query", $payload);

        return $response->json();
    }
}