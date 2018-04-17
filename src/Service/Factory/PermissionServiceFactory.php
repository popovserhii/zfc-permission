<?php
/**
 * @category Popov
 * @package Agere_Permission
 * @author Popov Sergiy <popov@agere.com.ua>
 * @datetime: 21.10.2016 18:05
 */
namespace Popov\ZfcPermission\Service\Factory;

use Interop\Container\ContainerInterface;
use Popov\Simpler\SimplerHelper;
use Popov\ZfcEntity\Helper\ModuleHelper;
use Popov\ZfcFields\Service\FieldsPagesService;
use Popov\ZfcPermission\Service\PermissionPageBindService;
use Popov\ZfcPermission\Service\PermissionService;
use Popov\ZfcPermission\Service\PermissionSettingsPagesService;
use Popov\ZfcRole\Service\RoleService;

class PermissionServiceFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $moduleHelper = $container->get(ModuleHelper::class);
        $simplerHelper = $container->get(SimplerHelper::class);
        $permissionAccessService = $container->get('PermissionAccessService');
        $permissionSettingsPagesService = $container->get(PermissionSettingsPagesService::class);
        $permissionPageBindService = $container->get(PermissionPageBindService::class);
        $roleService = $container->get(RoleService::class);
        $fieldsPagesService = $container->get(FieldsPagesService::class);

        $permissionService = new PermissionService(
            $simplerHelper,
            $moduleHelper,
            $permissionAccessService,
            $permissionSettingsPagesService,
            $permissionPageBindService,
            $roleService,
            $fieldsPagesService
        );

        return $permissionService;
    }
}