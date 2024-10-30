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

namespace CamooSms\Gateway\Domain\Controller;

/**
 * @template RET of mixed
 */
interface ControllerInterface
{
    /** @return RET */
    public function render();
}
