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
            'ConfirmationURL' => "{$this->callbackUrl}/api/payments/confirmation",
            'ValidationURL'   => "{$this->callbackUrl}/api/payments/validation",
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
        'ShortCode' => $this->shortCode,
        'CommandID' => 'CustomerPayBillOnline',
        'Amount'    => (int) $amount,
        'Msisdn'    => $phone,
    ];

    $response = Http::withToken($token)
        ->post("{$this->baseUrl}/mpesa/c2b/v2/simulate", $payload);

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
            'ResultURL'          => "{$this->callbackUrl}/api/payments/status-result",
            'QueueTimeOutURL'    => "{$this->callbackUrl}/api/payments/status-timeout",
            'Remarks'            => 'Check transaction status',
            'Occasion'           => '',
        ];

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/payments/transactionstatus/v1/query", $payload);

        return $response->json();
    }
    public function stkPush(string $phone, float $amount): array
{
    $token     = $this->getAccessToken();
    $timestamp = now()->format('YmdHis');
    $passkey   = env('MPESA_PASSKEY');
    $shortcode = $this->shortCode;
    
    $password  = base64_encode($shortcode . $passkey . $timestamp);

    $payload = [
        'BusinessShortCode' => $shortcode,
        'Password'          => $password,
        'Timestamp'         => $timestamp,
        'TransactionType'   => 'CustomerPayBillOnline',
        'Amount'            => (int) $amount,
        'PartyA'            => $phone,
        'PartyB'            => $shortcode,
        'PhoneNumber'       => $phone,
        'CallBackURL' => "{$this->callbackUrl}/api/payments/stk-callback",
        'AccountReference'  => 'MyBusiness',
        'TransactionDesc'   => 'Payment for services',
    ];

    $response = Http::withToken($token)
        ->post("{$this->baseUrl}/mpesa/stkpush/v1/processrequest", $payload);

    Log::info('STK Push', [
        'payload'  => $payload,
        'response' => $response->json(),
    ]);

    return $response->json();
}
}