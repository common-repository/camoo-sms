<?php

declare(strict_types=1);

namespace Camoo\Sms\Http;

use Psr\Http\Message\ResponseInterface;
use SimpleXMLElement;
use stdClass;
use Throwable;

/**
 * Class Response
 *
 * @author CamooSarl
 */
class Response
{
    /** @var string */
    public const BAD_STATUS = 'KO';

    /** @var string */
    public const GOOD_STATUS = 'OK';

    private const SUCCESS_HTTP_CODE = 200;

    private const JSON_EXTENSION = 'json';

    private const XML_EXTENSION = 'xml';

    protected array|stdClass $data;

    public function __construct(private readonly ResponseInterface $response, private readonly ?string $format = null)
    {
        $this->assertData();
    }

    public function getBody(): string
    {
        return (string)$this->response->getBody();
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function getJson(): array
    {
        if ($this->getStatusCode() !== self::SUCCESS_HTTP_CODE) {
            $message = $this->getBody() ?: 'request failed!';

            return ['status' => static::BAD_STATUS, 'message' => $message];
        }

        return array_merge(['status' => static::GOOD_STATUS], $this->data);
    }

    public function getXml(): ?string
    {
        if ($this->format !== self::XML_EXTENSION) {
            return null;
        }
        if ($this->getBody() === '') {
            return null;
        }

        return $this->decodeXml($this->getBody());
    }

    private function assertData(): void
    {
        $extension = $this->format ?? self::JSON_EXTENSION;
        $default = [];
        if ($extension !== self::JSON_EXTENSION) {
            $this->data = $default;

            return;
        }
        $dataValue = $this->response->getJson();
        if (empty($dataValue)) {
            $this->data = $default;

            return;
        }
        $this->data = $dataValue;
    }

    private function decodeXml(string $body): ?string
    {
        $data = null;
        try {
            $xml = new SimpleXMLElement($body);
            $data = $xml->asXML() ?: null;
        } catch (Throwable $exception) {
            echo $exception->getMessage();
        }

        return $data;
    }
}
