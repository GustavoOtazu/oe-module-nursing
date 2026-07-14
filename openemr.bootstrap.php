<?php

/**
 * Bootstrap for the OpenEMR Nursing Module
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Modules\Nursing;

/**
 * @global \OpenEMR\Core\ModulesClassLoader $classLoader
 */
$classLoader->registerNamespaceIfNotExists('OpenEMR\\Modules\\Nursing\\', __DIR__ . DIRECTORY_SEPARATOR . 'src');

/**
 * @global \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
 */
$bootstrap = new Bootstrap($eventDispatcher);
$bootstrap->subscribeToEvents();
