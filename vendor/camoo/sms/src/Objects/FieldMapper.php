<?php

declare(strict_types=1);

namespace Camoo\Sms\Objects;

use Camoo\Sms\Entity\FieldName;
use Camoo\Sms\Entity\TableMapping;
use Camoo\Sms\Response\Message;

final class FieldMapper
{
    public function __construct(
        private readonly TableMapping $tableMapping,
        private readonly Message $messageResponse,
        private readonly ?string $senderId = null
    ) {
    }

    public function get(): array
    {
        $fields = [];
        foreach ($this->tableMapping->toArray() as $driverKey => $appKey) {
            if (empty($appKey)) {
                continue;
            }
            if ($driverKey === FieldName::MESSAGE->value) {
                $fields[$appKey] = $this->messageResponse->getMessage();
            }
            if ($driverKey === FieldName::RECIPIENT->value) {
                $fields[$appKey] = $this->messageResponse->getTo();
            }
            if ($driverKey === FieldName::MESSAGE_ID->value) {
                $fields[$appKey] = $this->messageResponse->getId();
            }
            if ($driverKey === FieldName::SENDER_ID->value) {
                $fields[$appKey] = $this->senderId ?? '';
            }
            if ($driverKey === FieldName::RESPONSE->value) {
                $fields[$appKey] = json_encode($this->messageResponse->jsonSerialize());
            }
        }

        return $fields;
    }
}
