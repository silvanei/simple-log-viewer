<?php

declare(strict_types=1);

if (getenv('APP_ENV') === false) {
    putenv('APP_ENV=production');
}

putenv('APP_ROOT=' . dirname(__FILE__));
putenv('TEMPLATES_ROOT=' . dirname(__FILE__) . '/templates/');
