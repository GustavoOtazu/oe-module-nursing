<?php

/**
 * Module Manager Listener for the Nursing Module.
 *
 * Called by Laminas Module Manager for lifecycle actions (install, enable, disable, etc.).
 * Do not declare a namespace — Laminas Module Manager requires this.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

use OpenEMR\Common\Database\SqlQueryException;
use OpenEMR\Core\AbstractModuleActionListener;
use OpenEMR\Services\Utils\SQLUpgradeService;

class ModuleManagerListener extends AbstractModuleActionListener
{
    public function __construct()
    {
        parent::__construct();
    }

    public function moduleManagerAction($methodName, $modId, string $currentActionStatus = 'Success'): string
    {
        if (method_exists(self::class, $methodName)) {
            return self::$methodName($modId, $currentActionStatus);
        }
        return $currentActionStatus;
    }

    public static function getModuleNamespace(): string
    {
        return 'OpenEMR\\Modules\\Nursing\\';
    }

    public static function initListenerSelf(): ModuleManagerListener
    {
        return new self();
    }

    private function install($modId, $currentActionStatus): mixed
    {
        return self::runInstallSql($currentActionStatus, 'install');
    }

    /**
     * install.sql is idempotent (every block is guarded by #IfNotTable, #IfNotRow or
     * #IfMissingColumn), so running it again only adds what an older install is missing.
     */
    private static function runInstallSql(string $currentActionStatus, string $action): string
    {
        try {
            $sqlUpgradeService = new SQLUpgradeService();
            $sqlUpgradeService->setThrowExceptionOnError(true);
            $sqlUpgradeService->setRenderOutputToScreen(false);
            $sqlUpgradeService->upgradeFromSqlFile('install.sql', __DIR__ . '/sql');
        } catch (SqlQueryException $e) {
            error_log("Nursing module $action error: " . $e->getMessage());
            return $e->getMessage();
        }
        return $currentActionStatus;
    }

    private function enable($modId, $currentActionStatus): mixed
    {
        return $currentActionStatus;
    }

    private function disable($modId, $currentActionStatus): mixed
    {
        return $currentActionStatus;
    }

    private function unregister($modId, $currentActionStatus): mixed
    {
        return $currentActionStatus;
    }

    private function install_sql($modId, $currentActionStatus): mixed
    {
        return $currentActionStatus;
    }

    private function upgrade_sql($modId, $currentActionStatus): mixed
    {
        // Adds the tables and forms introduced after the first release
        // (fluid balance, nutrition, SOFA, APACHE II, care plan).
        return self::runInstallSql($currentActionStatus, 'upgrade');
    }
}
