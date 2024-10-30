<?php
/**
 * Author: jairo.rodriguez <jairo@bfunky.net>
 */

namespace BFunky\HttpParser;

use BFunky\HttpParser\Entity\HttpDataValidation;
use BFunky\HttpParser\Entity\HttpField;
use BFunky\HttpParser\Entity\HttpFieldCollection;
use BFunky\HttpParser\Entity\HttpHeaderInterface;
use BFunky\HttpParser\Exception\HttpFieldNotFoundOnCollection;
use BFunky\HttpParser\Exception\HttpParserBadFormatException;

abstract class AbstractHttpParser implements HttpParserInterface
{
    protected string $httpRaw;

    protected HttpHeaderInterface $httpHeader;

    protected HttpFieldCollection $httpFieldCollection;

    /** HttpParser constructor. */
    public function __construct(?HttpFieldCollection $httpFieldCollection = null)
    {
        $this->httpFieldCollection = $httpFieldCollection ?? HttpFieldCollection::fromHttpFieldArray([]);
    }

    public function parse(string $rawHttpHeader): void
    {
        $this->process($rawHttpHeader);
    }

    /** @throws HttpFieldNotFoundOnCollection */
    public function get(string $headerFieldName): string
    {
        $httpField = $this->httpFieldCollection->get($headerFieldName);
        if (is_array($httpField)) {
            $values = array_map(fn (HttpField $line): string => trim($line->getValue()), $httpField);

            return implode("\n", $values);
        }

        return $httpField->getValue();
    }

    public function getHeader(): HttpHeaderInterface
    {
        return $this->httpHeader;
    }

    /** @throws HttpParserBadFormatException */
    protected function process(string $rawHttp): void
    {
        $this->setHttpRaw($rawHttp);
        $this->extract();
    }

    /**
     * Split the http string
     *
     * @throws HttpParserBadFormatException
     */
    protected function extract(): void
    {
        $headers = explode("\n", $this->httpRaw);
        foreach ($headers as $headerLine) {
            if (trim($headerLine) === '') {
                continue;
            }
            if (HttpDataValidation::isField($headerLine)) {
                $this->addField($headerLine);
            } else {
                $this->addHeader($headerLine);
            }
        }
    }

    /** @throws HttpParserBadFormatException */
    protected function addHeader(string $headerLine): void
    {
        $data = explode(' ', $headerLine);
        $data = array_merge($data, ['', '', '']);
        HttpDataValidation::checkHeaderOrRaiseError($data[0], $data[1], $data[2]);
        $this->setHttpHeader($data[0], $data[1], $data[2]);
    }

    protected function addField(string $headerLine): void
    {
        [$fieldKey, $fieldValue] = $this->splitRawLine($headerLine);
        $this->httpFieldCollection->add(HttpField::fromKeyAndValue($fieldKey, $fieldValue));
    }

    abstract protected function setHttpHeader(string $method, string $path, string $protocol): void;

    protected function splitRawLine(string $line): array
    {
        $parts = [];
        if (str_contains($line, ': ')) {
            $parts = explode(': ', $line);
        } else {
            if (str_contains($line, ':')) {
                $parts = explode(':', $line);
            }
        }

        return $parts;
    }

    protected function setHttpRaw(string $httpRaw): HttpParserInterface
    {
        $this->httpRaw = $httpRaw;

        return $this;
    }
}
