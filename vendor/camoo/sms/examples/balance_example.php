<?php

declare(strict_types=1);

use Camoo\Sms\Balance;

require_once dirname(__DIR__) . '/vendor/autoload.php';
/**
 * @Brief read current balance
 */
// Step 1: create balance instance
/** @var Balance $oBalance */
$oBalance = Balance::create('YOUR_API_KEY', 'YOUR_API_SECRET');

// Step2: retrieve your current balance
$response = $oBalance->get();

// get balance value (since version 3.2.0)
$balance = $response->getBalance();
// get balance Currency (since version 3.2.0)
$currency = $response->getCurrency();
