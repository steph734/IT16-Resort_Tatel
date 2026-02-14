<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class PaymongoService
{
    private Client $client;
    private string $secretKey;
    private string $baseUrl = 'https://api.paymongo.com/v1';

    public function __construct()
    {
        $this->secretKey = config('paymongo.secret_key');
        $this->baseUrl = rtrim(config('paymongo.base_url'), '/');
        $this->client = new Client([
            'base_uri' => $this->baseUrl . '/',
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($this->secretKey . ':'),
            ],
            'http_errors' => false,
            'timeout' => 20,
        ]);
    }

    /**
     * Create a PayMongo Payment Link.
     * Amount must be in centavos.
     *
     * @param int    $amountCentavos
     * @param string $description
     * @param array  $metadata
     * @return array{checkout_url?:string, id?:string}
     */
    public function createPaymentLink(int $amountCentavos, string $description, array $metadata = []): array
    {
        $payload = [
            'data' => [
                'attributes' => [
                    'amount' => $amountCentavos,
                    'description' => $description,
                    'remarks' => $metadata['remarks'] ?? null,
                    'metadata' => $metadata,
                ],
            ],
        ];

        $resp = $this->client->post('links', [
            'json' => $payload,
        ]);

        $status = $resp->getStatusCode();
        $body = json_decode((string) $resp->getBody(), true);

        if ($status >= 200 && $status < 300 && isset($body['data'])) {
            $attributes = $body['data']['attributes'] ?? [];
            $linkId = $body['data']['id'] ?? null;
            // PayMongo may return any of these depending on product/version
            $checkoutUrl = $attributes['checkout_url']
                ?? $attributes['short_url']
                ?? $attributes['url']
                ?? null;

            return array_filter([
                'checkout_url' => $checkoutUrl,
                'id' => $linkId,
            ]);
        }

        Log::error('PayMongo create link failed', [
            'status' => $status,
            'body' => $body,
        ]);

        throw new \RuntimeException('Failed to create PayMongo payment link.');
    }

    /**
     * Create a PayMongo Checkout Session (single-use checkout page) with success and cancel URLs.
     * Restrict payment_method_types as needed (e.g., ['gcash', 'online_banking_bdo']).
     *
     * @param int    $amountCentavos
     * @param string $description
     * @param array  $metadata
     * @param array  $paymentMethodTypes
     * @param string $successUrl
     * @param string $cancelUrl
     * @param string|null $referenceNumber
     * @return array{checkout_url?:string, id?:string}
     */
    public function createCheckoutSession(
        int $amountCentavos,
        string $description,
        array $metadata,
        array $paymentMethodTypes,
        string $successUrl,
        string $cancelUrl,
        ?string $referenceNumber = null
    ): array {
        $payload = [
            'data' => [
                'attributes' => [
                    'description' => $description,
                    'line_items' => [[
                        'name' => $description,
                        'amount' => $amountCentavos,
                        'currency' => 'PHP',
                        'quantity' => 1,
                        'description' => $metadata['purpose'] ?? null,
                    ]],
                    'payment_method_types' => array_values(array_unique(array_filter($paymentMethodTypes))),
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                    'metadata' => $metadata,
                    'reference_number' => $referenceNumber,
                    'show_description' => true,
                    'show_line_items' => true,
                    'send_email_receipt' => true,
                ],
            ],
        ];

        $resp = $this->client->post('checkout_sessions', [
            'json' => $payload,
        ]);

        $status = $resp->getStatusCode();
        $body = json_decode((string) $resp->getBody(), true);

        if ($status >= 200 && $status < 300 && isset($body['data'])) {
            $attributes = $body['data']['attributes'] ?? [];
            $id = $body['data']['id'] ?? null;
            $checkoutUrl = $attributes['checkout_url'] ?? null;

            return array_filter([
                'checkout_url' => $checkoutUrl,
                'id' => $id,
            ]);
        }

        Log::error('PayMongo checkout session failed', [
            'status' => $status,
            'body' => $body,
        ]);

        throw new \RuntimeException('Failed to create PayMongo checkout session.');
    }

    /**
     * Retrieve a PayMongo checkout session by ID.
     * Returns associative array with attributes or empty array on failure.
     */
    public function getCheckoutSession(string $sessionId): array
    {
        $resp = $this->client->get('checkout_sessions/' . $sessionId);
        $status = $resp->getStatusCode();
        $body = json_decode((string) $resp->getBody(), true);
        if ($status >= 200 && $status < 300 && isset($body['data']['attributes'])) {
            return $body['data']['attributes'];
        }
        Log::warning('Failed to retrieve PayMongo checkout session', [
            'session_id' => $sessionId,
            'status' => $status,
            'body' => $body,
        ]);
        return [];
    }
}
