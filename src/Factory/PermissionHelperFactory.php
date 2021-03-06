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

use Doctrine\ORM\EntityManager;
use Popov\Simpler\SimplerHelper;
use Popov\ZfcPermission\Acl\Acl;
use Popov\ZfcUser\Model\User;
use Zend\Expressive\Helper\UrlHelper;
use \Zend\View\Helper\Url;
use Zend\ServiceManager\ServiceLocatorInterface;
use Popov\ZfcUser\Event\Authentication;
use Popov\ZfcPermission\PermissionHelper;
use Popov\ZfcCurrent\CurrentHelper;
use Popov\ZfcUser\Auth\Auth;

class PermissionHelperFactory
{
    public function __invoke(ServiceLocatorInterface $sm)
    {
        $acl = $sm->get(Acl::class);
        $config = $sm->get('config');
        $currentHelper = $sm->get(CurrentHelper::class);
        $simplerHelper = $sm->get(SimplerHelper::class);
        $urlHelper = $sm->get('ViewHelperManager')->get(Url::class);
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $authService = $sm->get(Auth::class);

        //$manager = $sm->get('ModuleManager');
        //$modules = $currentHelper->getLoadedModules();

        //$userPlugin = $this->getAuthService();
        #$user = ($userPlugin->hasIdentity() && ($user = $userPlugin->getIdentity())) ? $user : false;


        $auth = new PermissionHelper($currentHelper, $simplerHelper, $urlHelper);
        //$auth->setUsePermission(isset($modules['Popov\ZfcPermission']));
        $auth->setAuthService($authService);
        //$auth->setRequest($currentHelper);
        $auth->setConfig($config);
        $auth->setDbAdapter($dbAdapter);
        $auth->setAcl($acl);

        if ($authService->hasIdentity()) {
            $user = $sm->get(EntityManager::class)->find(User::class, $authService->getIdentity());
            $auth->setUser($user);
        }
        //$auth->setRoles($auth->getDbRoles($dbAdapter));

        return $auth;
    }
}