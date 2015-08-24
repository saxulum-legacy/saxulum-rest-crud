<?php

$loader = require __DIR__.'/../vendor/autoload.php';
$loader->setPsr4('Saxulum\Tests\RestCrud\\', __DIR__);

use \Doctrine\Common\Annotations\AnnotationRegistry;

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
