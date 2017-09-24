<?php
/**
 * @category Agere
 * @package Agere_Permission
 * @author Sergiy Popov <popov@agere.com.ua>
 * @datetime: 22.12.2016 0:06
 */
namespace Popov\ZfcPermission\Controller\Factory;

use Interop\Container\ContainerInterface;
use Zend\Console\Console;
use Zend\Mvc\Router\Http\TreeRouteStack;
use Popov\ZfcPermission\Controller\PermissionConsoleController;

class PermissionConsoleControllerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $sm = $container->getServiceLocator();
        $rpm = $sm->get('RoutePluginManager'); //
        $routerConfig = $sm->get('Config')['router'];
        $permissionService = $sm->get('PermissionService');

        $routerConfig['route_plugins'] = $rpm;
        /** @var TreeRouteStack $treeRouteStack */
        $treeRouteStack = TreeRouteStack::factory($routerConfig);

        // @todo Create custom factory for Console which will be determine console type and if is Windows return Posix
        $console = Console::getInstance('Posix');

        $controller = new PermissionConsoleController($console, $treeRouteStack, $permissionService);

        return $controller;
    }
}