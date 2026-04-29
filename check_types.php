<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Utils\TransactionUtil;

$tu = app(TransactionUtil::class);
$business_id = 1; // dummy

echo "getOpeningClosingStock: " . gettype($tu->getOpeningClosingStock($business_id, date('Y-m-d'), null)) . "\n";
echo "getPurchaseTotals: " . gettype($tu->getPurchaseTotals($business_id)) . "\n";
echo "getSellTotals: " . gettype($tu->getSellTotals($business_id)) . "\n";
echo "getProfitLossDetails: " . gettype($tu->getProfitLossDetails($business_id, null, '2020-01-01', '2020-12-31')) . "\n";
