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

namespace CamooSms\Gateway\Controller;

use Camoo\Sms\Entity\Credential;
use CAMOO_SMS\Option;
use CamooSms\Gateway\Application\Command\TopupCommand;
use CamooSms\Gateway\Application\Command\TopupCommandHandler;
use CamooSms\Gateway\Domain\Controller\ControllerInterface;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * @implements ControllerInterface<void>
 */
final class TopupController extends AppController
{
    public function render(): void
    {
        if (isset($_POST['topupAccount']) &&
            isset($_POST['camoo_topup']) &&
            wp_verify_nonce($_POST['camoo_topup'], 'camoo_topup')) {
            $phoneNumber = trim(sanitize_text_field($_POST['camoo_phone']));
            $amount = trim(sanitize_text_field($_POST['camoo_amount']));
            $username = Option::getOption('gateway_username');
            $password = Option::getOption('gateway_password');
            try {
                $handler = new TopupCommandHandler();
                $handler->handle(new TopupCommand(new Credential($username, $password), $phoneNumber, (float)$amount));
                echo "<div class='updated'><p>" . __(
                    'Topup request sent successfully! Please check your phone to complete the instructions',
                    'wp-camoo-sms'
                ) . '</p></div>';
            } catch (Throwable $exception) {
                $this->displayError($exception->getMessage());
            }
        }

        include_once WP_CAMOO_SMS_DIR . 'includes/admin/topup/account.php';
    }

    private function displayError(string $message): void
    {
        $class = 'notice notice-error';

        if (str_contains($message, '_error')) {
            $validation = json_decode($message, true);
            $messages = json_decode($validation['_error'][0], true);
            foreach ($messages as $content) {
                $message = __($content[0], 'wp-camoo-sms');

                printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
            }

            return;
        }
        $message = __($message, 'wp-camoo-sms');

        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }
}
