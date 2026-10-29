<?php

declare(strict_types=1);

namespace ZapZap;

/**
 * Bound helper for WhatsApp actions on a specific instance.
 *
 * All paths are proxied to /{instanceId}/... on the ZapZap API.
 */
class Instance
{
    /** @var Client */
    private $client;

    /** @var string */
    private $instanceId;

    /** @var Group */
    public $group;

    public function __construct(Client $client, string $instanceId)
    {
        $this->client = $client;
        $this->instanceId = $instanceId;
        $this->group = new Group($this);
    }

    public function getId(): string
    {
        return $this->instanceId;
    }

    private function path(string $suffix): string
    {
        return '/' . rawurlencode($this->instanceId) . $suffix;
    }

    /**
     * POST to /{instanceId}{suffix} (low-level escape hatch).
     *
     * @param array<string,mixed>|null $body
     * @return mixed
     */
    public function post(string $suffix, ?array $body = null)
    {
        return $this->client->request('POST', $this->path($suffix), $body);
    }

    /**
     * GET /{instanceId}{suffix} (low-level escape hatch).
     *
     * @param array<string,mixed>|null $query
     * @return mixed
     */
    public function get(string $suffix, ?array $query = null)
    {
        return $this->client->request('GET', $this->path($suffix), null, $query);
    }

    // -- messaging ----------------------------------------------------------

    /**
     * POST /{id}/send/text - send a plain text message.
     *
     * @param string|null $mentions "all" or comma-separated numbers (groups only).
     * @param int|null    $delay    Delay in ms before sending.
     * @param string|null $replyId  Message id to reply to.
     * @return mixed
     */
    public function sendText(string $number, string $text, ?string $mentions = null, ?int $delay = null, ?string $replyId = null)
    {
        return $this->post('/send/text', [
            'number' => $number,
            'text' => $text,
            'mentions' => $mentions,
            'delay' => $delay,
            'replyid' => $replyId,
        ]);
    }

    /**
     * POST /{id}/send/media - send media from a public URL.
     *
     * @param string      $type     image|video|videoplay|audio|ptt|myaudio|ptv|document|sticker
     * @param string      $file     Public URL of the file.
     * @param string|null $text     Caption (images and videos).
     * @param string|null $mentions "all" or comma-separated numbers (groups only).
     * @param string|null $docName  File name shown in chat; required for document.
     * @param int|null    $delay    Delay in ms before sending.
     * @return mixed
     */
    public function sendMedia(string $number, string $type, string $file, ?string $text = null, ?string $mentions = null, ?string $docName = null, ?int $delay = null)
    {
        return $this->post('/send/media', [
            'number' => $number,
            'type' => $type,
            'file' => $file,
            'text' => $text,
            'mentions' => $mentions,
            'docName' => $docName,
            'delay' => $delay,
        ]);
    }

    /**
     * POST /{id}/send/menu - send an interactive menu (buttons/list/poll).
     *
     * $number is required; all other fields are passed through verbatim so the
     * full UazAPI menu payload is supported without the SDK constraining it.
     *
     * @param array<string,mixed> $options
     * @return mixed
     */
    public function sendMenu(string $number, array $options = [])
    {
        $options['number'] = $number;
        return $this->post('/send/menu', $options);
    }

    /**
     * POST /{id}/message/react - react to a message with an emoji.
     *
     * @param string $id    Message id in OWNER:MSGID format
     *                      (e.g. 5511991515364:2A6E2F02CE4125FDBA1B).
     * @param string $emoji Reaction emoji; empty string removes the reaction.
     * @return mixed
     */
    public function react(string $number, string $id, string $emoji)
    {
        return $this->post('/message/react', [
            'number' => $number,
            'id' => $id,
            'emoji' => $emoji,
        ]);
    }

    /**
     * GET /{id}/instance/status - WhatsApp connection status.
     *
     * @return mixed
     */
    public function status()
    {
        return $this->get('/instance/status');
    }
}
