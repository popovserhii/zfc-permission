<?php
/**
 * @category Agere
 * @package Agere_Permission
 * @author Popov Sergiy <popov@agere.com.ua>
 * @datetime: 21.10.2016 18:05
 */
namespace Popov\ZfcPermission\Service\Factory;

use Interop\Container\ContainerInterface;
use Popov\ZfcPermission\Service\PermissionPageBindService;

class PermissionPageBindServiceFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $cpm = $container->get('ControllerPluginManager');
        $simplerPlugin = $cpm->get('simpler');

        $permissionPageBindService = new PermissionPageBindService($simplerPlugin);

        return $permissionPageBindService;
    }
}