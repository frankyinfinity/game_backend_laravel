<?php
require 'vendor/autoload.php';

$classes = [
    'Docker\API\Model\ContainersCreatePostBodyExposedPortsItem',
    'Docker\API\Model\Port',
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "$class exists\n";
    } else {
        echo "$class NOT found\n";
    }
}
