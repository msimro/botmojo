<?php

declare(strict_types=1);

namespace BotMojo;

require_once __DIR__ . '/../vendor/autoload.php';

use BotMojo\Bootstrap\Bootstrap;
use BotMojo\Config\Config;

// Load configuration
$config = new Config(__DIR__ . '/../config');

// Create and run application
$app = new Bootstrap($config);
$response = $app->handle();

// Send response
$response->send();
