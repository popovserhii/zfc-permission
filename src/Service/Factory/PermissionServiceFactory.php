<?php
/**
 * @category Agere
 * @package Agere_Permission
 * @author Popov Sergiy <popov@agere.com.ua>
 * @datetime: 21.10.2016 18:05
 */
namespace Popov\ZfcPermission\Service\Factory;

use Interop\Container\ContainerInterface;
use Popov\ZfcPermission\Service\PermissionService;

class PermissionServiceFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $cpm = $container->get('ControllerPluginManager');
        $modulePlugin = $cpm->get('module');
        $simplerPlugin = $cpm->get('simpler');
        $permissionAccessService = $container->get('PermissionAccessService');

        $permissionService = new PermissionService($simplerPlugin, $modulePlugin, $permissionAccessService);

        return $permissionService;
    }
}