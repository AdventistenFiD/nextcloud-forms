<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use OCP\App\IAppManager;
use OCP\Server;

if (!defined('PHPUNIT_RUN')) {
	define('PHPUNIT_RUN', 1);
}

require_once __DIR__ . '/../../../lib/base.php';
require_once __DIR__ . '/../../../tests/autoload.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/stubs-circles.php';

$dataDir = \OC::$server->getConfig()->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data');
$profilerDir = $dataDir . '/__profiler';
if (!is_dir($profilerDir)) {
	@mkdir($profilerDir, 0777, true);
}
@chmod($profilerDir, 0777);

Server::get(IAppManager::class)->loadApp('forms');
