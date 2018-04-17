<?php
/**
 * Authentication Factory
 *
 * @category Popov
 * @package Agere_User
 * @author Serhii Popov <popow.serhii@gmail.com>
 * @datetime: 12.03.2016 1:44
 */
namespace Popov\ZfcPermission\Factory;

use Popov\Simpler\SimplerHelper;
use Zend\Expressive\Helper\UrlHelper;
use Zend\ServiceManager\ServiceLocatorInterface;
use Popov\ZfcUser\Event\Authentication;
use Popov\ZfcPermission\PermissionHelper;
use Popov\ZfcCurrent\CurrentHelper;
use Popov\ZfcUser\Auth\Auth;
class PermissionHelperFactory
{
    public function __invoke(ServiceLocatorInterface $sm)
    {
        $acl = $sm->get('Acl');
        $config = $sm->get('config');
        $currentHelper = $sm->get(CurrentHelper::class);
        $simplerHelper = $sm->get(SimplerHelper::class);
        $urlHelper = $sm->get(UrlHelper::class);
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $authService = $sm->get(Auth::class);

        //$manager = $sm->get('ModuleManager');
        //$modules = $currentHelper->getLoadedModules();


        $auth = new PermissionHelper($currentHelper, $simplerHelper, $urlHelper);
        //$auth->setUsePermission(isset($modules['Popov\ZfcPermission']));
        $auth->setAuthService($authService);
        //$auth->setRequest($currentHelper);
        $auth->setConfig($config);
        $auth->setDbAdapter($dbAdapter);
        $auth->setAcl($acl);
        //$auth->setRoles($auth->getDbRoles($dbAdapter));

        return $auth;
    }
}