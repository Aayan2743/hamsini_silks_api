<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PhonePeService
{
    protected string $merchantId;
    protected string $clientId;
    protected string $clientSecret;
    protected string $clientVersion;
    protected string $baseUrl;

    public function __construct()
    {
        $this->merchantId    = config('phonepe.merchant_id');
        $this->clientId      = config('phonepe.client_id');
        $this->clientSecret  = config('phonepe.client_secret');
        $this->clientVersion = config('phonepe.client_version');
        $this->baseUrl       = config('phonepe.base_url');
    }

    /**
     * Generate OAuth Token
     */
    protected function getAccessToken(): string
    {
        return Cache::remember('phonepe_access_token', 50 * 60, function () {

            $response = Http::asForm()->post(
                $this->baseUrl . '/v1/oauth/token',
                [
                    'client_id'      => $this->clientId,
                    'client_secret'  => $this->clientSecret,
                    'client_version' => $this->clientVersion,
                    'grant_type'     => 'client_credentials',
                ]
            );

            if (! $response->successful()) {
                throw new \Exception('Token Error: ' . $response->body());
            }

            return $response->json()['access_token'];
        });
    }

    /**
     * Create Payment (Checkout V2)
     */
    public function createPayment(int $amount, string $merchantOrderId, string $redirectUrl)
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => 'O-Bearer ' . $token,
            'Content-Type'  => 'application/json',
        ])->post($this->baseUrl . '/checkout/v2/pay', [
            "merchantId"      => $this->merchantId,
            "merchantOrderId" => $merchantOrderId,
            "amount"          => $amount * 100,
            "redirectUrl"     => $redirectUrl,
            "paymentFlow"     => [
                "type" => "PG_CHECKOUT",
            ],
        ]);

        if (! $response->successful()) {
            throw new \Exception('Payment Error: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Check Order Status
     */
    public function checkOrderStatus(string $merchantOrderId)
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => 'O-Bearer ' . $token,
        ])->get(
            $this->baseUrl . "/checkout/v2/order/{$merchantOrderId}/status"
        );

        return $response->json();
    }
}
