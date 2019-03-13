<?php
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    // if we are checked out as a stand-alone project
    $loader = __DIR__ . '/../vendor/autoload.php';
} else if (file_exists(__DIR__ . '/../../../../vendor/autoload.php')) {
    // if we are within the vendor directory of another project
    $loader = __DIR__ . '/../../../../vendor/autoload.php';
}
 
if (!isset($loader) || !$loader = @include($loader)) {
    echo <<<EOM
You must set up the project dependencies by running the following commands:
 
    curl -s http://getcomposer.org/installer | php
    php composer.phar install
 
EOM;
 
    exit(1);
}
