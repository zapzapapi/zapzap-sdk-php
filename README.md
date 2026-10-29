# ZapZap API - PHP SDK

Official PHP SDK for the [ZapZap API](https://zapzapapi.com), a multi-tenant
WhatsApp platform. Manage instances, connect numbers and send WhatsApp messages
from PHP.

- Base URL: `https://api.zapzapapi.com/api/v1`
- Authentication: `x-api-key` + `x-api-secret` headers
- Full reference: https://api.zapzapapi.com/llms.txt

## Installation

```bash
composer require zapzapapi/sdk
```

Requires PHP 7.4+ with the `curl` and `json` extensions.

## Authentication

Get your API key and secret from the ZapZap panel at
https://app.zapzapapi.com (Settings). Never hardcode them: load them from
environment variables or a secrets manager.

```php
use ZapZap\Client;

$client = new Client(
    getenv('ZAPZAP_API_KEY'),
    getenv('ZAPZAP_API_SECRET')
);
```

## Quickstart

```php
// Account
$account = $client->account();
echo $account['balance'];

// Instances
$instances = $client->listInstances();
$created = $client->createInstance('my-instance', null, ['clientId' => '123']);
$instanceId = $created['instance']['id'];

$qr = $client->qrcode($instanceId);   // scan with your phone
$client->updateInstance($instanceId, 'https://example.com/webhook');

// Webhook
$client->setWebhook($instanceId, 'https://example.com/webhook', 'messages,status');
print_r($client->getWebhook($instanceId));

// Messaging (bound to one instance)
$wa = $client->instance($instanceId);
$wa->status();
$wa->sendText('5511999999999', 'Hello from PHP!');
$wa->sendMedia('5511999999999', 'image', 'https://example.com/photo.jpg', 'Look!');
$wa->react('5511999999999', '5511991515364:2A6E2F02CE4125FDBA1B', '👍');

// Interactive menu (full payload passed through verbatim)
$wa->sendMenu('5511999999999', [
    'text' => 'Choose:',
    'choices' => ['Option A', 'Option B'],
]);

// Groups
$wa->group->list();
$wa->group->create('My Group', ['5511999999999', '5511888888888']);
```

## Error handling

Any non-2xx response or transport failure throws `ZapZap\ZapZapException`:

```php
use ZapZap\ZapZapException;

try {
    $client->createInstance('bad name!');
} catch (ZapZapException $e) {
    echo $e->getStatusCode();  // e.g. 402 (insufficient balance), 409 (name exists)
    echo $e->getMessage();     // message from the API body when available
    var_dump($e->getResponse()); // parsed response body
}
```

## API surface

| Area | Method | Endpoint |
|------|--------|----------|
| Account | `$client->account()` | `GET /account` |
| Instances | `$client->listInstances()` | `GET /instances` |
| Instances | `$client->creationCost()` | `GET /instances/cost` |
| Instances | `$client->createInstance($name, $systemName=null, $metadata=null)` | `POST /instances` |
| Instances | `$client->getInstance($id)` | `GET /instances/{id}` |
| Instances | `$client->updateInstance($id, $webhookUrl=null, $metadata=null)` | `PUT /instances/{id}` |
| Instances | `$client->deleteInstance($id)` | `DELETE /instances/{id}` |
| Instances | `$client->qrcode($id)` | `GET /instances/{id}/qrcode` |
| Webhook | `$client->getWebhook($id)` | `GET /instances/{id}/webhook` |
| Webhook | `$client->setWebhook($id, $url, $events=null, $excludeMessages=null)` | `PUT /instances/{id}/webhook` |
| Webhook | `$client->testWebhook($id, $url=null)` | `POST /instances/{id}/webhook/test` |
| Messaging | `$client->instance($id)->sendText($number, $text, ...)` | `POST /{id}/send/text` |
| Messaging | `$client->instance($id)->sendMedia($number, $type, $file, ...)` | `POST /{id}/send/media` |
| Messaging | `$client->instance($id)->sendMenu($number, $options)` | `POST /{id}/send/menu` |
| Messaging | `$client->instance($id)->react($number, $id, $emoji)` | `POST /{id}/message/react` |
| Status | `$client->instance($id)->status()` | `GET /{id}/instance/status` |
| Groups | `$client->instance($id)->group->*` | `POST /{id}/group/*` |

## License

MIT (c) GM Solutions Hub LTDA.
