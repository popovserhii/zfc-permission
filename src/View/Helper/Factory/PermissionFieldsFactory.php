<?php
/**
 * @category Agere
 * @package Agere_Permission
 * @author Popov Sergiy <popov@agere.com.ua>
 * @datetime: 02.05.2016 21:00
 */
namespace Popov\ZfcPermission\View\Helper\Factory;

use Popov\ZfcPermission\View\Helper\PermissionFields;

class PermissionFieldsFactory
{
    public function __invoke($vhm)
    {
        $sm = $vhm->getServiceLocator();
        $route = $sm->get('Application')->getMvcEvent()->getRouteMatch();
        $userHelper = $vhm->get('user');

        return new PermissionFields($userHelper->getUser(), $route, $sm);
    }
}