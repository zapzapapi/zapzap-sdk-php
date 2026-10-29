<?php

declare(strict_types=1);

namespace ZapZap;

/**
 * Client for the ZapZap public API (https://zapzapapi.com).
 *
 * Example:
 *   $client = new \ZapZap\Client($apiKey, $apiSecret);
 *   $account = $client->account();
 *   $client->instance($id)->sendText('5511999999999', 'Hello!');
 */
class Client
{
    public const DEFAULT_BASE_URL = 'https://api.zapzapapi.com/api/v1';

    /** @var string */
    private $apiKey;

    /** @var string */
    private $apiSecret;

    /** @var string */
    private $baseUrl;

    /** @var int */
    private $timeout;

    public function __construct(
        string $apiKey,
        string $apiSecret,
        string $baseUrl = self::DEFAULT_BASE_URL,
        int $timeout = 30
    ) {
        if ($apiKey === '' || $apiSecret === '') {
            throw new \InvalidArgumentException('apiKey and apiSecret are required');
        }
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    // -- transport ----------------------------------------------------------

    /**
     * Perform a request and return the decoded JSON body.
     *
     * @param array<string,mixed>|null $body
     * @param array<string,mixed>|null $query
     * @return mixed
     * @throws ZapZapException
     */
    public function request(string $method, string $path, ?array $body = null, ?array $query = null)
    {
        $url = $this->baseUrl . $path;
        if ($query !== null) {
            $clean = self::clean($query);
            if (!empty($clean)) {
                $url .= '?' . http_build_query($clean);
            }
        }

        $ch = curl_init();
        $headers = [
            'x-api-key: ' . $this->apiKey,
            'x-api-secret: ' . $this->apiSecret,
            'Accept: application/json',
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        if ($body !== null) {
            $payload = json_encode(self::clean($body));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new ZapZapException('Request failed: ' . $err);
        }

        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return $this->handle($status, is_string($raw) ? $raw : '');
    }

    /**
     * @return mixed
     * @throws ZapZapException
     */
    private function handle(int $status, string $raw)
    {
        $decoded = json_decode($raw, true);
        $body = (json_last_error() === JSON_ERROR_NONE) ? $decoded : ($raw !== '' ? $raw : null);

        if ($status < 200 || $status >= 300) {
            $message = 'HTTP ' . $status;
            if (is_array($body)) {
                if (isset($body['error']) && is_string($body['error'])) {
                    $message = $body['error'];
                } elseif (isset($body['message']) && is_string($body['message'])) {
                    $message = $body['message'];
                }
            } elseif (is_string($body) && $body !== '') {
                $message = $body;
            }
            throw new ZapZapException($message, $status, $body);
        }

        return $body;
    }

    /**
     * Remove null values so optional fields are not sent.
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private static function clean(array $data): array
    {
        return array_filter($data, static function ($v) {
            return $v !== null;
        });
    }

    // -- account ------------------------------------------------------------

    /**
     * GET /account - account data: name, email, balance, billing_day, status.
     *
     * @return mixed
     */
    public function account()
    {
        return $this->request('GET', '/account');
    }

    // -- instance management ------------------------------------------------

    /**
     * GET /instances - list all WhatsApp instances.
     *
     * @return mixed
     */
    public function listInstances()
    {
        return $this->request('GET', '/instances');
    }

    /**
     * GET /instances/cost - cost to create a new instance.
     *
     * @return mixed
     */
    public function creationCost()
    {
        return $this->request('GET', '/instances/cost');
    }

    /**
     * POST /instances - create a new instance (debits balance).
     *
     * @param array<string,mixed>|null $metadata Free-form data echoed in webhooks.
     * @return mixed
     */
    public function createInstance(string $name, ?string $systemName = null, ?array $metadata = null)
    {
        return $this->request('POST', '/instances', [
            'name' => $name,
            'systemName' => $systemName,
            'metadata' => $metadata,
        ]);
    }

    /**
     * GET /instances/{id} - details of a single instance.
     *
     * @return mixed
     */
    public function getInstance(string $instanceId)
    {
        return $this->request('GET', '/instances/' . rawurlencode($instanceId));
    }

    /**
     * PUT /instances/{id} - update webhook_url and/or metadata.
     *
     * @param array<string,mixed>|null $metadata
     * @return mixed
     */
    public function updateInstance(string $instanceId, ?string $webhookUrl = null, ?array $metadata = null)
    {
        return $this->request('PUT', '/instances/' . rawurlencode($instanceId), [
            'webhook_url' => $webhookUrl,
            'metadata' => $metadata,
        ]);
    }

    /**
     * DELETE /instances/{id} - soft delete; generates an activation-fee waiver.
     *
     * @return mixed
     */
    public function deleteInstance(string $instanceId)
    {
        return $this->request('DELETE', '/instances/' . rawurlencode($instanceId));
    }

    /**
     * GET /instances/{id}/qrcode - QR code to connect WhatsApp.
     *
     * @return mixed
     */
    public function qrcode(string $instanceId)
    {
        return $this->request('GET', '/instances/' . rawurlencode($instanceId) . '/qrcode');
    }

    // -- webhook ------------------------------------------------------------

    /**
     * GET /instances/{id}/webhook - the configured webhook URL.
     *
     * @return mixed
     */
    public function getWebhook(string $instanceId)
    {
        return $this->request('GET', '/instances/' . rawurlencode($instanceId) . '/webhook');
    }

    /**
     * PUT /instances/{id}/webhook - set/update the webhook URL.
     *
     * @param string|null $events        Events filter (e.g. "messages,status"). Empty = all.
     * @param bool|null   $excludeMessages Exclude own messages.
     * @return mixed
     */
    public function setWebhook(string $instanceId, string $webhookUrl, ?string $events = null, ?bool $excludeMessages = null)
    {
        return $this->request('PUT', '/instances/' . rawurlencode($instanceId) . '/webhook', [
            'webhook_url' => $webhookUrl,
            'events' => $events,
            'excludeMessages' => $excludeMessages,
        ]);
    }

    /**
     * POST /instances/{id}/webhook/test - send a test event to the webhook.
     *
     * @return mixed
     */
    public function testWebhook(string $instanceId, ?string $url = null)
    {
        return $this->request('POST', '/instances/' . rawurlencode($instanceId) . '/webhook/test', [
            'url' => $url,
        ]);
    }

    // -- per-instance entry point -------------------------------------------

    /**
     * Return a helper bound to a specific instance for WhatsApp actions.
     */
    public function instance(string $instanceId): Instance
    {
        return new Instance($this, $instanceId);
    }
}
