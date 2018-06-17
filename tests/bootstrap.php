<?php

require __DIR__ . '/../../../vendor/autoload.php';

Tester\Environment::setup();

$configurator = new Nette\Configurator;
$configurator->setDebugMode(false);
$configurator->setTempDirectory(__DIR__ . '/../temp');
$configurator->createRobotLoader()
	->addDirectory(__DIR__ . '/../../../app')
    ->addDirectory(__DIR__ . '/../../../vendor')
    ->addDirectory(__DIR__ . '/../../../nuttilea')
	->register();

$configurator->addConfig(__DIR__ . '/../../../app/config/config.neon');
$configurator->addConfig(__DIR__ . '/../../../app/config/config.local.neon');
return $configurator->createContainer();
