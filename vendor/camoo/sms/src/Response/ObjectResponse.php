<?php

declare(strict_types=1);

namespace Camoo\Sms\Response;

use Camoo\Inflector\Inflector;
use Camoo\Sms\Exception\CamooSmsException;
use Camoo\Sms\Http\Response;

class ObjectResponse
{
    protected array $_data;

    public function __construct(Response $response)
    {
        $this->_data = $response->getJson();
    }

    /**
     * @codeCoverageIgnore
     *
     * Magic method for testing if properties exist.
     */
    public function __isset(string $property): bool
    {
        return isset($this->_data[$property]);
    }

    /**
     * @codeCoverageIgnore
     *
     * Magic getter for accessing properties directly.
     */
    public function __get(string $property): mixed
    {
        $getter = sprintf('get%s', Inflector::camelize($property));

        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        throw new CamooSmsException(sprintf("Undefined property %s::$%s.\n", __CLASS__, $property));
    }
}
