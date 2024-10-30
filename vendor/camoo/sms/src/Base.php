<?php

declare(strict_types=1);

namespace Camoo\Sms;

use Camoo\Sms\Console\AddBulkCommand;
use Camoo\Sms\Console\AddBulkCommandHandler;
use Camoo\Sms\Console\EncryptCommand;
use Camoo\Sms\Console\EncryptCommandHandler;
use Camoo\Sms\Entity\CallbackDto;
use Camoo\Sms\Entity\Credential;
use Camoo\Sms\Entity\ObjectEntity;
use Camoo\Sms\Entity\ObjectHandler;
use Camoo\Sms\Exception\CamooSmsException;
use Camoo\Sms\Http\Command\ExecuteRequestCommand;
use Camoo\Sms\Http\Command\ExecuteRequestCommandHandler;
use Camoo\Sms\Objects\ObjectEntityInterface;
use Exception;

/**
 * Class Base
 */
class Base implements ObjectHandlerInterface
{
    protected string $endpoint = Constants::END_POINT_URL;

    protected static ?ObjectEntityInterface $dataObject = null;

    /** @var string|null $resourceName The resource name as it is known at the server */
    protected ?string $resourceName = null;

    protected static array $credentials = [];

    /* @var mixed configs */
    protected static array $configData = [];

    protected static mixed $instance = null;

    /** @var string Target version for "Classic" Camoo API */
    protected string $camooClassicApiVersion = Constants::END_POINT_VERSION;

    protected ?string $responseJson = null;

    private array $errors = [];

    public function __construct(private readonly ?ExecuteRequestCommandHandler $requestCommandHandler = null)
    {
    }

    public function __get(string $property): mixed
    {
        $payload = Objects\Base::create()->get($this->getDataObject());

        return $payload[$property] ?? null;
    }

    public function __set(string $property, mixed $value): void
    {
        try {
            Objects\Base::create()->set($property, $value, $this->getDataObject());
        } catch (CamooSmsException $err) {
            $this->errors[] = $err->getMessage();
        }
    }

    public function setResourceName(string $resourceName): void
    {
        $this->resourceName = $resourceName;
    }

    public function getResourceName(): ?string
    {
        return $this->resourceName;
    }

    public static function create(
        ?string $apiKey = null,
        ?string $apiSecret = null,
        ?ExecuteRequestCommandHandler $handler = null
    ): object {
        self::$credentials = array_combine(Constants::CREDENTIAL_ELEMENTS, [$apiKey, $apiSecret]);
        $className = get_called_class();
        $asCaller = explode('\\', $className);
        $caller = array_pop($asCaller);
        $objectClass = self::getObject($caller);
        if ($objectClass instanceof ObjectEntityInterface) {
            self::$dataObject = $objectClass;
        }

        self::$instance = self::getObjectHandler($handler, $caller);

        return self::$instance;
    }

    public static function clear(): void
    {
        static::$instance = null;
    }

    public function getDataObject(): ?ObjectEntityInterface
    {
        return self::$dataObject;
    }

    public function getConfigs(): array
    {
        return self::$configData;
    }

    public function setCredential(Credential $credential): self
    {
        self::$credentials = $credential->toArray();

        return $this;
    }

    public function getCredentials(): array
    {
        return self::$credentials;
    }

    /** Returns payload for a request */
    public function getData(string $validator = 'default'): array
    {
        try {
            $rowData = Objects\Base::create()->get($this->getDataObject(), $validator);
            $dataObject = $this->getDataObject();
            if ($dataObject instanceof \Camoo\Sms\Objects\Message && array_key_exists('message', $rowData) &&
                $dataObject->encrypt === true) {
                $publicFile = !array_key_exists('pgp_public_file', $rowData) ? null : $rowData['pgp_public_file'];
                $rowData['message'] = $this->encryptMessage($rowData['message'], $publicFile);
            }

            return $rowData;
        } catch (CamooSmsException $exception) {
            $this->errors[] = $exception->getMessage();
        }

        return [];
    }

    /**
     * Returns the CAMOO API URL
     *
     * @author Camoo Sarl
     **/
    public function getEndPointUrl(): string
    {
        $sUrlTmp = $this->endpoint . Constants::DS . $this->camooClassicApiVersion . Constants::DS;
        $sResource = '';
        if ($this->getResourceName() !== null && $this->getResourceName() !== 'sms') {
            $sResource = Constants::DS . $this->getResourceName();
        }

        $responseFormat = $this->responseJson ?? Constants::JSON_RESPONSE_FORMAT;

        return sprintf($sUrlTmp . 'sms' . $sResource . '%s', '.' . $responseFormat);
    }

    /**
     * Execute request with credentials
     *
     * @throws CamooSmsException
     *
     * @author Camoo Sarl
     */
    public function execRequest(string $type, bool $withData = true, ?string $validator = null): Http\Response
    {
        $data = [];
        if ($withData === true && empty($this->getErrors())) {
            $data = null === $validator ? $this->getData() : $this->getData($validator);
        }
        if (!empty($this->getErrors())) {
            throw new CamooSmsException(['_error' => $this->getErrors()]);
        }

        $command = new ExecuteRequestCommand(
            $type,
            $this->getEndPointUrl(),
            $data,
            new Credential($this->getCredentials()['api_key'], $this->getCredentials()['api_secret'])
        );
        $handler = $this->requestCommandHandler ?? new ExecuteRequestCommandHandler();

        return $handler->handle($command);
    }

    public function setResponseFormat(string $format): void
    {
        $format = strtolower($format);
        if (!in_array($format, ['json', 'xml'], true)) {
            return;
        }

        $this->responseJson = $format;
    }

    /** @throws Exception */
    public function execBulk(CallbackDto|array $callBack, ?string $phpBinPath = null): ?int
    {
        $oClassObj = $this->getDataObject();
        if (!$oClassObj->has('to') || empty($oClassObj->to)) {
            return null;
        }

        $handler = new AddBulkCommandHandler();

        return $handler->handle(
            new AddBulkCommand(
                new Credential($this->getCredentials()['api_key'], $this->getCredentials()['api_secret']),
                $this->getData(),
                $callBack,
                $phpBinPath
            )
        );
    }

    protected function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Encrypt message using PGP
     *
     * @return string encrypted $sMessage
     */
    protected function encryptMessage(string $message, ?string $publicFile = null): string
    {
        $publicFile = $publicFile ?? dirname(__DIR__) . Constants::DS .
        'config' . Constants::DS . 'keys' . Constants::DS . 'cert.pem';

        try {
            $handler = new EncryptCommandHandler();

            return $handler->handle(new EncryptCommand($publicFile, $message));
        } catch (Exception $exception) {
            $this->errors[] = $exception->getMessage();

            return $message;
        }
    }

    private static function getObjectHandler(
        ?ExecuteRequestCommandHandler $handler,
        string $caller
    ): ObjectHandlerInterface {
        foreach (ObjectHandler::cases() as $objectHandler) {
            if (strtoupper($caller) === $objectHandler->name) {
                return $objectHandler->getInstance($handler);
            }
        }

        return new static($handler);
    }

    private static function getObject(string $caller): ?ObjectEntityInterface
    {
        foreach (ObjectEntity::cases() as $objectEntity) {
            if (strtoupper($caller) === $objectEntity->name) {
                return $objectEntity->getInstance();
            }
        }

        return null;
    }
}
