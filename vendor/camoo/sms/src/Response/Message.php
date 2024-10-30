<?php

declare(strict_types=1);

namespace Camoo\Sms\Response;

use Camoo\Sms\Http\Response;
use Camoo\Sms\Lib\NormalizeMessageResponse;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;

/**
 * Class Message
 *
 * @property string            $id
 * @property int               $status
 * @property string            $to
 * @property float             $message_price
 * @property string            $message
 * @property DateTimeInterface $created_at
 * @property string            $sms_sender
 * @property string            $reference
 * @property DateTimeInterface $status_time
 *
 * @author CamooSarl
 */
final class Message extends ObjectResponse
{
    private array $data;

    public function __construct(Response $response)
    {
        parent::__construct($response);
        $normalize = new NormalizeMessageResponse($response->getJson());
        $smsResponseData = $normalize->get();
        $this->data = !empty($smsResponseData) ? $smsResponseData['sms'] : [];
    }

    public function getId(): ?string
    {
        if (array_key_exists('id', $this->data)) {
            return $this->data['id'];
        }

        if (array_key_exists('message_id', $this->data)) {
            return $this->data['message_id'];
        }

        return array_key_exists('messages', $this->data) ?
            implode(',', array_column($this->data['messages'], 'message_id')) : null;
    }

    public function getStatus(): string
    {
        if (array_key_exists('status', $this->data)) {
            return $this->data['status'];
        }

        return array_key_exists('messages', $this->data) ?
            implode(',', array_column($this->data['messages'], 'status')) : 'sent';
    }

    public function getTo(): ?string
    {
        if (array_key_exists('to', $this->data)) {
            return $this->data['to'];
        }

        return array_key_exists('messages', $this->data) ?
            implode(',', array_column($this->data['messages'], 'to')) : null;
    }

    public function getMessagePrice(): ?float
    {
        if (array_key_exists('message_price', $this->data)) {
            return (float)$this->data['message_price'];
        }

        $values = array_key_exists('messages', $this->data) ?
            array_column($this->data['messages'], 'message_price') : null;
        if (empty($values)) {
            return null;
        }

        return (float)$values[0];
    }

    public function getMessage(): ?string
    {
        if (array_key_exists('message', $this->data)) {
            return $this->data['message'];
        }

        $values = array_key_exists('messages', $this->data) ?
            array_column($this->data['messages'], 'message') : null;
        if (empty($values)) {
            return null;
        }

        return $values[0] ?? null;
    }

    /** @throws Exception */
    public function getCreatedAt(): ?DateTime
    {
        if (empty($this->data['created'])) {
            return null;
        }

        return new DateTime($this->data['created'], new DateTimeZone('Africa/Douala'));
    }

    public function getSmsSender(): ?string
    {
        return $this->data['sms_sender'] ?? null;
    }

    public function getReference(): ?string
    {
        return $this->data['reference'] ?? null;
    }

    /** @throws Exception */
    public function getStatusTime(): ?DateTime
    {
        if (empty($this->data['status_time'])) {
            return null;
        }

        return new DateTime($this->data['status_time'], new DateTimeZone('Africa/Douala'));
    }

    public function jsonSerialize(): array
    {
        return $this->_data;
    }
}
