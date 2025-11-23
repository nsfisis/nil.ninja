<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Nsfisis\TinyPhpHttpd\App;

(new App('0.0.0.0', 8080))->run();
