<?php
/**
 * This is an example illustrating how to top up your account from your own application.
 */

declare(strict_types=1);

use Camoo\Sms\TopUp;

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Step 1: create Top up instance
/** @var TopUp&\Camoo\Sms\Objects\TopUp $topup */
$topup = TopUp::create('YOUR_API_KEY', 'YOUR_API_SECRET');

// Step 2 Assign phone number and amount
$topup->amount = 3000;
$topup->phonenumber = '612345678';

// Step 3 Call add to top up your account. You will then receive a notification to complete the process.
$response = $topup->add();

var_dump($response);

/*
// Step 4 :
- Dial *126*1# for MTN Cameroon
- Choose option to authorize the transaction
- Enter your MTN Mobile Money PIN
- Choose the option to approve the Payment
- Choose option and confirm
*/
