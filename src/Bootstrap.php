<?php

/**
 * Bootstrap for the OpenEMR Nursing Module.
 *
 * Subscribes to OpenEMR events to register menu items, patient cards,
 * Twig template paths, and form file resolution.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\Nursing;

use OpenEMR\Events\Core\TwigEnvironmentEvent;
use OpenEMR\Events\Encounter\LoadEncounterFormFilterEvent;
use OpenEMR\Events\Patient\Summary\Card\SectionEvent;
use OpenEMR\Menu\MenuEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Loader\FilesystemLoader;

class Bootstrap
{
    private const MODULE_DIR = 'oe-module-nursing';

    /** Nursing form directories that this module provides */
    private const FORM_DIRS = [
        'curaciones',
        'aplicaciones',
        'cuidados',
        'evaluaciones',
        'registro_vm',
    ];

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function subscribeToEvents(): void
    {
        $this->eventDispatcher->addListener(MenuEvent::MENU_UPDATE, $this->addNursingMenu(...));
        $this->eventDispatcher->addListener(SectionEvent::EVENT_HANDLE, $this->addNursingCard(...));
        $this->eventDispatcher->addListener(TwigEnvironmentEvent::EVENT_CREATED, $this->addTemplateOverrideLoader(...));
        $this->eventDispatcher->addListener(LoadEncounterFormFilterEvent::EVENT_NAME, $this->redirectFormPaths(...));
    }

    /**
     * Add "Nursing" top-level menu item pointing to the inpatient dashboard.
     */
    public function addNursingMenu(MenuEvent $event): MenuEvent
    {
        $menu = $event->getMenu();

        $menuItem = new \stdClass();
        $menuItem->requirement = 0;
        $menuItem->target = 'nur';
        $menuItem->menu_id = 'nur0';
        $menuItem->label = xlt('Nursing');
        $menuItem->url = '/interface/modules/custom_modules/' . self::MODULE_DIR . '/public/dashboard/lista_internados.php';
        $menuItem->children = [];
        $menuItem->acl_req = ['patients', 'med'];
        $menuItem->global_req = [];

        $menu[] = $menuItem;
        $event->setMenu($menu);

        return $event;
    }

    /**
     * Add the NursingCard to the patient demographics "secondary" section.
     */
    public function addNursingCard(SectionEvent $event): void
    {
        if ($event->getSection() !== 'secondary') {
            return;
        }

        $pid = (int) ($_SESSION['pid'] ?? 0);
        if ($pid <= 0) {
            return;
        }

        $event->addCard(new NursingCard($pid));
    }

    /**
     * Register the module's templates directory with the Twig filesystem loader.
     */
    public function addTemplateOverrideLoader(TwigEnvironmentEvent $event): void
    {
        try {
            $twig = $event->getTwigEnvironment();
            $loader = $twig->getLoader();
            if ($loader instanceof FilesystemLoader) {
                $loader->prependPath(__DIR__ . '/../templates');
            }
        } catch (\Exception $e) {
            error_log("Nursing module: failed to register Twig template path: " . $e->getMessage());
        }
    }

    /**
     * Redirect form file resolution for nursing forms from interface/forms/
     * to the module's public/forms/ directory.
     */
    public function redirectFormPaths(LoadEncounterFormFilterEvent $event): void
    {
        $formDir = $event->getFormName();
        if (!in_array($formDir, self::FORM_DIRS, true)) {
            return;
        }

        $modulePath = dirname(__DIR__) . '/public/forms/' . $formDir . '/';
        $event->setDir($modulePath);
    }
}
