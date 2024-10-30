<?php

declare(strict_types=1);

namespace Camoo\Sms\Console;

use Camoo\Sms\Constants;
use Camoo\Sms\Entity\PersonalizedContent;
use Camoo\Sms\Lib\Utils;

class PersonalizationCommandHandler
{
    public function handle(PersonalizationCommand $command): PersonalizedContent
    {
        $asPersonalizeMsgFind = Constants::PERSONALIZE_MSG_KEYS;
        $destination = $command->destination;
        $message = $command->message;
        $name = '';
        if (is_array($destination) && !empty($destination['name'])) {
            $name = Utils::satanizer($destination['name']);
        }
        $asPersonalizeMsgReplace = [$name];

        if ($message !== null) {
            $message = str_replace($asPersonalizeMsgFind, $asPersonalizeMsgReplace, $message);
        }

        if (is_array($destination) && !empty($destination['mobile'])) {
            $destination = $destination['mobile'];
        }

        return new PersonalizedContent($destination, $message);
    }
}
