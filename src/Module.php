<?php
namespace Popov\ZfcPermission;

use Popov\ZfcCurrent\CurrentHelper;
use Zend\ModuleManager\ModuleManager;
use	Zend\EventManager\Event;
use	Zend\Mvc\MvcEvent;
use Zend\Http\Request as HttpRequest;
use Zend\Mvc\Controller\AbstractController;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\ConsoleUsageProviderInterface;
use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\ModuleManager\Feature\ConsoleBannerProviderInterface;
use	Popov\Reflection\Service\ReflectionService;

class Module implements ConfigProviderInterface, ConsoleUsageProviderInterface, ConsoleBannerProviderInterface
{
	/** @var \Zend\ServiceManager\ServiceManager $sm */
	protected $sm;

    public function getConfig()
    {
        $config = include __DIR__ . '/../config/module.config.php';
        $config['service_manager'] = $config['dependencies'];
        unset($config['dependencies']);
        unset($config['templates']);

        return $config;
    }

	/**
	 * @param MvcEvent $e
	 */
	public function onBootstrap(MvcEvent $e)
	{
        if ($e->getRequest() instanceof HttpRequest) {
            $app = $e->getApplication();
            $container = $app->getServiceManager();
            $eventManager = $app->getEventManager();
            $sharedEvents = $eventManager->getSharedManager();

            //$eventManager->attach(MvcEvent::EVENT_DISPATCH, function(MvcEvent $e) use ($container) {
            $sharedEvents->attach(AbstractController::class,MvcEvent::EVENT_DISPATCH, function(MvcEvent $e) use ($container) {
                $area = $container->get(CurrentHelper::class)->currentRouteParams()['area'] ?? null;
                if ('admin' !== $area) {
                    return;
                }

                $permissionHelper = $container->get(PermissionHelper::class);
                $permissionHelper->init();
                if ($isDenied = $permissionHelper->checkPermission()) {
                    $e->stopPropagation(true);
                    if ($redirect = $permissionHelper->getRedirect()) {
                        $url = $e->getRouter()->assemble($redirect['params'], ['name' => $redirect['route']]);

                        $response = $e->getResponse();
                        $response->getHeaders()->addHeaderLine('Location', $url);
                        $response->setStatusCode(302);
                        $response->sendHeaders();
                    } else {
                        $response = $e->getResponse();
                        $viewModel = $e->getViewModel();
                        $viewModel->setTemplate('admin-permission::denied');
                        $response ->setStatusCode(403);
                    }
                }
            }, 4000);
        }
	}

    /**
     * @todo Here is example how to check access and show "Permission denied" page
     * @link https://stackoverflow.com/questions/19651959/forward-to-another-controller-action-from-module-php
     */
    /*public function onBootstrap($e)
    {
        $app = $e->getApplication();
        $acl = $app->getServiceManager()->get('ACL'); // get your ACL here

        if (!$acl->isAllowed()) {
            $em = $app->getEventManager();
            $em->attach(MvcEvent::EVENT_DISPATCH, function($e) {
                $routeMatch = $e->getRouteMatch();

                $routeMatch->setParam('controller', 'my-403-controller');
                $routeMatch->setParam('action', 'my-403-action');
            }, 1000);
        }
    }*/
    /**
     * You should use unique event name and attach in relaible module
     * @deprecated
     */
	public function init(ModuleManager $mm)
	{
	    return;

		$mm->getEventManager()->getSharedManager()
			->attach('Popov\Documents\Controller\DocumentsController', ['documents.savePermissionPage'],
				function(Event $evt)
				{
					$this->_savePermissionPage($evt);
				}
			);

		$mm->getEventManager()->getSharedManager()
			->attach('Popov\Brand\Controller\BrandController', ['brand.savePermissionPage'],
				function(Event $evt)
				{
					$this->_savePermissionPage($evt);
				}
			);

		$mm->getEventManager()->getSharedManager()
			->attach('Popov\ZfcPermission\Controller\PermissionController', ['permission.updatePermission'],
				function(Event $evt)
				{
					$this->_updateSettings($evt);
					$this->_updateFields($evt);
				}
			);

		$mm->getEventManager()->getSharedManager()
			->attach('Popov\Status\Service\StatusService', ['status.updatePermission'],
				function(Event $evt)
				{
					$this->_updateSettings($evt);
					$this->_updateFields($evt);
				}
			);

		$mm->getEventManager()->getSharedManager()
			->attach('Popov\Roles\Service\RolesService', ['roles.updatePermission'],
				function(Event $evt)
				{
					$this->_updateSettings($evt);
					$this->_updateFields($evt);
				}
			);
	}

	protected function _savePermissionPage($evt)
	{
		$targetClass = get_class($evt->getTarget());

		$reflection = new ReflectionService();
		$data = $reflection->getClassInfo($targetClass);

		$servicePermission = $this->sm->get('PermissionService');

		$servicePermission->save([
			'id'		=> $evt->getParam('id', 0),
			'target'	=> $evt->getParam('target'),
			'entityId'	=> $evt->getParam('entityId', 0),
			'type'		=> $evt->getParam('type', 'action'),
			'module'	=> $evt->getParam('module', $data['module']),
			'parent'	=> $evt->getParam('parent', 0),
		]);
	}

	protected function _updateFields($evt)
	{
		/** @var \Popov\ZfcPermission\Service\PermissionService $service */
		$service = $this->sm->get('PermissionService');

		// Table fields_pages
		/** @var \Popov\Fields\Service\FieldsPagesService $serviceFp */
		$serviceFp = $this->sm->get('FieldsPagesService');
		$fieldsPages = $serviceFp->getNotAddPermission();

		$service->runFields($fieldsPages, [
			'type'		=> 'field',
			'typeField'	=> 'permission',
		]);

		// Table permission_page_bind
		/** @var \Popov\ZfcPermission\Service\PermissionPageBindService $servicePageBind */
		$servicePageBind = $this->sm->get('PermissionPageBindService');
		$itemsPageBind = $servicePageBind->getNotAddPermission();

		$service->runFields($itemsPageBind, [
			'type'		=> 'settings',
			'typeField'	=> '',
		]);
	}

	protected function _updateSettings($evt)
	{
		/** @var \Popov\ZfcPermission\Service\PermissionService $service */
		$service = $this->sm->get('PermissionService');

        /** @var \Popov\Simpler\Plugin\SimplerPlugin $simpler */
        $simpler = $this->sm->get('ControllerPluginManager')->get('simpler');

		// Table permission_settings_pages
		/** @var \Popov\ZfcPermission\Service\PermissionSettingsPagesService $servicePermissionPages */
		$servicePermissionPages = $this->sm->get('PermissionSettingsPagesService');
		$pagesEntity = $servicePermissionPages->getSettingsEntity();
		$settingsPage = $servicePermissionPages->getSettingsByPage('', '', 'id');

		// Table permission_page_bind
		/** @var \Popov\ZfcPermission\Service\PermissionPageBindService $servicePageBind */
		$servicePageBind = $this->sm->get('PermissionPageBindService');

		// Table status
		/** @var \Popov\Status\Service\StatusService $statusService */
		$statusService = $this->sm->get('StatusService');

		// Table roles
		/** @var \Popov\Roles\Service\RolesService $rolesService */
		$rolesService = $this->sm->get('RolesService');

		// Table fields_pages
		/** @var \Popov\Fields\Service\FieldsPagesService $fieldsPagesService */
		$fieldsPagesService = $this->sm->get('FieldsPagesService');

		$saveData = [];
		foreach ($pagesEntity as $pageEntity) {
			//\Zend\Debug\Debug::dump($pageEntity['name']); die(__METHOD__);
			$itemsPageBind = $servicePageBind->getItemsBySettingsId($pageEntity[0]->getId());
			//$itemsPageBind = $servicePageBind->toArrayKeyField('childrenId', $itemsPageBind, true);
			$itemsPageBind = $simpler->setContext($itemsPageBind)->asAssociate('childrenId', true);
			foreach ($itemsPageBind as $keyChildren => $itemPageBind) {
				if ($keyChildren == 0 && $pageEntity['name'] == '') {
					unset($itemsPageBind[$keyChildren]);
					continue;
				}
				//$itemsPageBind[$keyChildren] = $servicePageBind->toArrayKeyVal('entityId');
				$itemsPageBind[$keyChildren] = $simpler->setContext($itemPageBind)->asArray('entityId');
			}
			switch ($pageEntity['settingsMnemo']) {
				case 'status':
				case 'removeDependingStatus':
					$page = explode('/', $pageEntity['page']);
					$items = $statusService->getItemsCollection($page[0], '0');
					break;
				case 'roles':
					$items = $rolesService->getItemsCollection();
					break;
				case 'fields':
				case 'editNotEmptyFields':
				case 'uploadFile':
				case 'deleteFile':
				case 'deleteCreatedFile':
					$items = $fieldsPagesService->getFieldsByPage($pageEntity['page']);
					break;
			}
			foreach ($items as $item) {
				$tmp = is_object($item) ? $item : $item[0];
				foreach ($itemsPageBind as $keyChildren => $itemsEntity) {
					if (!in_array($tmp->getId(), $itemsEntity)) {
						$saveData[] = [
							'permissionSettingsPages'   => $pageEntity[0],
							'permissionSettingsPagesId' => $pageEntity[0]->getId(),
							'childrenId'                => $keyChildren,
							'entityId'                  => $tmp->getId(),
						];
					}
				}
			}
		}
		$servicePageBind->saveData($saveData);
	}

    public function getConsoleBanner(Console $console) {
        return 'Permission Module';
    }

    public function getConsoleUsage(Console $console) {
        return [
            'Usage:',
            'permission [<command>] [flags] [options]' => '',

            'Command:',
            ['prompt', 		                    'Run prompt dialog'],

            'Flags:',
            //['--env', 							'Prepare environment for all projects'],

            ['add', 							'Add new permission'],
            ['--prompt',	 				    'Use prompt for command'],
            ['--type=',   						'Permission type'],
            ['--target=', 						'Permission target. In most case equivalent of url'],
            //['--no-create-repo',	 			'Do not create repository'],
            //['--verbose|-v',	 				'Detailed information about creating project'],
            //['--debug|-d',		 				'Enable debug mode'],

        ];
    }
}
