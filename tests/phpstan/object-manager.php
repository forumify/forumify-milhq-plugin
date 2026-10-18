<?php

declare(strict_types=1);

use Forumify\Testing\Bootstrap;
use Forumify\Testing\ForumifyTestKernel;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

Bootstrap::boot(dirname(__DIR__, 2));

$kernel = new ForumifyTestKernel('test', true, dirname(__DIR__));
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
