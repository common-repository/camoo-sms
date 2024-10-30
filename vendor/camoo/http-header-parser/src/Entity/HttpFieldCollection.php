<?php
/**
 * Author: jairo.rodriguez <jairo@bfunky.net>
 */

namespace BFunky\HttpParser\Entity;

use BFunky\HttpParser\Exception\HttpFieldNotFoundOnCollection;

class HttpFieldCollection
{
    /**
     * HttpFieldCollection constructor.
     *
     * @param HttpField[] $httpFields
     */
    public function __construct(private array $httpFields = [])
    {
        foreach ($this->httpFields as $index => $httpField) {
            $this->httpFields[$httpField->getName()] = $httpField;
            unset($this->httpFields[$index]);
        }
    }

    public function add(HttpField $obj): void
    {
        if (array_key_exists($obj->getName(), $this->httpFields)) {
            if (!is_array($this->httpFields[$obj->getName()])) {
                $firstValue = $this->httpFields[$obj->getName()];
                $this->httpFields[$obj->getName()] = [];
                $this->httpFields[$obj->getName()][] = $firstValue;
            }
            $this->httpFields[$obj->getName()][] = $obj;

            return;
        }
        $this->httpFields[$obj->getName()] = $obj;
    }

    /** @return array<HttpField> */
    public function getHttpFields(): array
    {
        return $this->httpFields;
    }

    /** @throws HttpFieldNotFoundOnCollection */
    public function delete(string $key): void
    {
        $this->checkKeyExists($key);
        unset($this->httpFields[$key]);
    }

    /** @throws HttpFieldNotFoundOnCollection */
    public function get(string $key): HttpField|array
    {
        $this->checkKeyExists($key);

        return $this->httpFields[$key];
    }

    public static function fromHttpFieldArray(array $httpFields): self
    {
        return new self($httpFields);
    }

    /** @throws HttpFieldNotFoundOnCollection */
    private function checkKeyExists(string $key): void
    {
        if (!array_key_exists($key, $this->httpFields)) {
            throw new  HttpFieldNotFoundOnCollection('Field ' . $key . ' not found');
        }
    }
}
