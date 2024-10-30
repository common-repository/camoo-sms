<?php

declare(strict_types=1);
/**
 * @author CAMOO SARL <sms@camoo.sarl>
 *
 * @version 3.0.0
 *
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace CamooSms\Gateway\Application\Command;

use Camoo\Sms\TopUp;
use CamooSms\Gateway\Application\Exception\TopupException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

final class TopupCommandHandler
{
    private TopUp $topupHandler;

    public function __construct(private readonly ?TopUp $topUp = null)
    {
        $this->topupHandler = $this->topUp ?? TopUp::create();
    }

    public function handle(TopupCommand $command): bool
    {
        $this->topupHandler->setCredential($command->credential);
        $this->topupHandler->amount = $command->amount;
        $this->topupHandler->phonenumber = $command->phonenumber;
        try {
            $this->topupHandler->add();
        } catch (Throwable $exception) {
            throw new TopupException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
        }

        return true;
    }
}
