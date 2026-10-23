<?php

// Kept so that the existing server crontab keeps working: runs `bin/console app:purge-expired`.
// Prefer calling the command directly in the crontab (see README):
// 0 * * * * /usr/bin/php8.4-cli ~/bigupload/website/bin/console app:purge-expired --no-interaction

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;

require_once dirname(__DIR__, 2) . '/vendor/autoload_runtime.php';

return function (array $context) {
    $application = new Application(new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']));
    $application->setDefaultCommand('app:purge-expired', true);

    return $application;
};
