<?php

declare(strict_types=1);

namespace Camoo\Sms\Entity;

final class TableMapping
{
    private const DEFAULT = [
        'message' => 'message',
        'to' => 'recipient',
        'message_id' => 'message_id',
        'from' => 'sender',
        'response' => 'response',
    ];

    public function __construct(
        public readonly string $messageField,
        public readonly string $recipientField,
        public readonly string $messageIdField,
        public readonly string $senderField,
        public readonly string $responseField
    ) {
    }

    public static function default(): self
    {
        return new self(
            self::DEFAULT[FieldName::MESSAGE->value],
            self::DEFAULT[FieldName::RECIPIENT->value],
            self::DEFAULT[FieldName::MESSAGE_ID->value],
            self::DEFAULT[FieldName::SENDER_ID->value],
            self::DEFAULT[FieldName::RESPONSE->value],
        );
    }

    public function toArray(): array
    {
        return[
            FieldName::MESSAGE->value => $this->messageField,
            FieldName::RECIPIENT->value => $this->recipientField,
            FieldName::MESSAGE_ID->value => $this->messageIdField,
            FieldName::SENDER_ID->value => $this->senderField,
            FieldName::RESPONSE->value => $this->responseField,
        ];
    }
}
