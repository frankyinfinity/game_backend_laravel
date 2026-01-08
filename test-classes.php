<?php
require 'vendor/autoload.php';

use Docker\API\Model\HostConfig;
use Docker\API\Model\PortBinding;

if (class_exists(HostConfig::class)) {
    echo "HostConfig exists\n";
} else {
    echo "HostConfig NOT found\n";
}

if (class_exists(PortBinding::class)) {
    echo "PortBinding exists\n";
} else {
    echo "PortBinding NOT found\n";
}
