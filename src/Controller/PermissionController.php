<?php
namespace Popov\ZfcPermission\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class PermissionController extends AbstractActionController {

	public $serviceName = 'PermissionService';
	public $controllerRedirect = 'permission';
	public $actionRedirect = 'index';


	public function updateUrlsAction() {
		$sm = $this->getServiceLocator();
		$config = $sm->get('Config');

		/** @var \Popov\ZfcPermission\Service\PermissionService $service */
		$service = $sm->get($this->serviceName);
        $routesToControllers = $service->routeToControllerNormalization($config['controllers']);
		
        $translate = $service->run($routesToControllers);
        // Write to file
        $file = getcwd() . '/module/Popov/Permission/language/translate.phtml';
        $fileContent = file($file);
        $fileContent = array_unique(array_merge($fileContent, $translate));
        file_put_contents($file, implode("\n", $fileContent));
        // Update Module Permission
        $service->updatePermission(__CLASS__);
	}

}