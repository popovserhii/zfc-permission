<?php
/**
 * @category Agere
 * @package Agere_Permission
 * @author Popov Sergiy <popov@agere.com.ua>
 * @datetime: 21.10.2016 18:05
 */
namespace Popov\ZfcPermission\View\Helper\Factory;

use Interop\Container\ContainerInterface;
use Popov\ZfcPermission\View\Helper\Permission as PermissionHelper;

class PermissionHelperFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $sm = $container->getServiceLocator();
        $permissionService = $sm->get('PermissionService');
        $fieldsPagesService = $sm->get('FieldsPagesService');
        $permissionSettingsPagesService = $sm->get('PermissionSettingsPagesService');
        $permissionPageBindService = $sm->get('PermissionPageBindService');
        $statusService = $sm->get('StatusService');
        $rolesService = $sm->get('RolesService');
        $permissionAccessService = $sm->get('PermissionAccessService');
        $access = $permissionAccessService->getAccess();

        $permissionHelper = new PermissionHelper(
            $permissionService,
            $fieldsPagesService,
            $permissionSettingsPagesService,
            $permissionPageBindService,
            $statusService,
            $rolesService,
            $access
        );

        return $permissionHelper;
    }
}