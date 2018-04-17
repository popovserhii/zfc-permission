<?php
/**
 * @category Popov
 * @package Agere_Permission
 * @author Popov Sergiy <popov@agere.com.ua>
 * @datetime: 21.10.2016 18:05
 */
namespace Popov\ZfcPermission\View\Helper\Factory;

use Interop\Container\ContainerInterface;
use Popov\Simpler\SimplerHelper;
use Popov\ZfcPermission\View\Helper\PermissionHelper;

class PermissionHelperFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $permissionService = $container->get('PermissionService');
        $fieldsPagesService = $container->get('FieldsPagesService');
        $permissionSettingsPagesService = $container->get('PermissionSettingsPagesService');
        $permissionPageBindService = $container->get('PermissionPageBindService');
        $rolesService = $container->get('RoleService');
        $permissionAccessService = $container->get('PermissionAccessService');
        $access = $permissionAccessService->getAccess();
        $simplerHelper = $container->get(SimplerHelper::class);
        //$statusService = $container->get('StatusService');

        $permissionHelper = new PermissionHelper(
            $permissionService,
            $fieldsPagesService,
            $permissionSettingsPagesService,
            $permissionPageBindService,
            $rolesService,
            $simplerHelper,
            $access
            #$statusService
        );

        return $permissionHelper;
    }
}