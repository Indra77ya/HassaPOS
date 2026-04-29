<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$method = new ReflectionMethod('App\Utils\TransactionUtil', 'getOpeningClosingStock');
echo "Parameters: " . $method->getNumberOfParameters() . "\n";
foreach ($method->getParameters() as $p) {
    echo $p->getName() . "\n";
}
