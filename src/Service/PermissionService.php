<?php
namespace Popov\ZfcPermission\Service;

use Zend\Server\Reflection;
use Zend\Stdlib\Exception\InvalidArgumentException;
use Zend\Mvc\Controller\AbstractController;
use Doctrine\ORM\EntityRepository;
use Popov\Agere\Service\AbstractEntityService;
use Popov\Reflection\Service\ReflectionService;
use Popov\Logs\Event\Logs as LogsEvent;
use Popov\Users\Model\Users as User;
use Popov\Roles\Model\Roles as Role;
use Popov\Entity\Model\Entity;
use Popov\Entity\Controller\Plugin\ModulePlugin;
use Agere\Simpler\Plugin\SimplerPlugin;
use Popov\Fields\Model\FieldsPages as FieldPage;
use Popov\ZfcPermission\Model\Permission;
use Popov\ZfcPermission\Model\Repository\PermissionRepository;
use Popov\ZfcPermission\Service\PermissionAccessService;
use Agere\Core\Service\DomainServiceAbstract;
use Zend\Filter\Word\SeparatorToCamelCase;

/**
 * @method PermissionRepository getRepository()
 */
class PermissionService extends DomainServiceAbstract
{
    protected $entity = Permission::class;

    /** @var PermissionAccessService */
    protected $permissionAccessService;

    /** @var ModulePlugin */
    protected $modulePlugin;

    /** @var SimplerPlugin */
    protected $simplerPlugin;

    public function __construct(
        SimplerPlugin $simplerPlugin,
        ModulePlugin $modulePlugin,
        PermissionAccessService $permissionAccessService
    ) {
        $this->permissionAccessService = $permissionAccessService;
        $this->modulePlugin = $modulePlugin;
        $this->simplerPlugin = $simplerPlugin;
    }

    public function getModulePlugin()
    {
        return $this->modulePlugin;
    }

    public function getSimpler()
    {
        return $this->simplerPlugin;
    }

    public function getPermissionAccessService()
    {
        return $this->permissionAccessService;
    }

    /**
     * @return array
     */
    public function getTypeFields()
    {
        return ['', 'required', 'edit', 'hide', 'permission'];
    }

    /**
     * @param string $name
     * @return string
     */
    public function getTypeField($name)
    {
        $args = $this->getTypeFields();

        return (isset($args[$name])) ? $args[$name] : '';
    }

    /**
     * @param string $type
     * @param null|int $entityId
     * @param bool $parent
     * @return mixed
     */
    public function getItemsCollection($type, $entityId = null, $parent = false)
    {
        $repository = $this->getRepository();

        return $repository->findAllItems($type, $entityId, $parent);
    }

    /**
     * @param string $target
     * @param string $type
     * @param int $parent
     * @param string $typeField
     * @return array
     */
    public function getItemsByParent($target, $type, $parent, $typeField = 'required')
    {
        $repository = $this->getRepository();

        return $repository->findItemsByParent($target, $type, $parent, $typeField);
    }

    /**
     * @param string $target
     * @param int $entity
     * @param string $type
     * @param int $parent
     * @return mixed
     */
    public function getOneItem($target, $entity, $type, $parent = 0)
    {
        $repository = $this->getRepository();

        return $repository->findOneItem($target, $entity, $type, $parent);
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function getItemById($id)
    {
        $repository = $this->getRepository();

        return $repository->findOneByIdAndCreateNew($id);
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function save($data)
    {
        $oneItem = $this->getItemById($data['id']);
        unset($data['id']);
        // Default values
        if (!isset($data['parent'])) {
            $data['parent'] = 0;
        }
        if (!isset($data['typeField'])) {
            $data['typeField'] = '';
        }
        if (!isset($data['required'])) {
            $data['required'] = '0';
        }
        // End Default values
        foreach ($data as $field => $val) {
            $method = 'set' . ucfirst($field);
            $oneItem->$method($val);
        }
        $repository = $this->getRepository();
        $repository->save($oneItem);

        return $oneItem;
    }

    /**
     * @param object $items
     * @param $data
     * @param string $key
     */
    public function saveData($items, $data, $key)
    {
        $repository = $this->getRepository();
        foreach ($data as $values) {
            if (!empty($values['id']) && isset($items[$values[$key]])) {
                $oneItem = $items[$values[$key]];
            } else {
                $oneItem = $repository->createOneItem();
            }
            unset($values['id']);
            foreach ($values as $field => $val) {
                $method = 'set' . ucfirst($field);
                $oneItem->$method($val);
            }
            $repository->addItem($oneItem);
        }
        if (isset($oneItem)) {
            $repository->saveData();
        }
    }

    /**
     * @param int $id
     */
    public function deleteById($id)
    {
        $repository = $this->getRepository();
        $itemAccess = $this->getPermissionAccessService()->getItem($id, 'permissionId');
        if (!$itemAccess || !$itemAccess->getId()) {
            $item = $this->getItemById($id);
            if ($item->getId()) {
                $repository->delete($item);
            }
        }
    }

    /**
     * @param object $item
     */
    public function delete($item)
    {
        $repository = $this->getRepository();
        $repository->delete($item);
    }

    /**
     * @param object $items
     */
    public function deleteData($items)
    {
        $repository = $this->getRepository();
        foreach ($items as $item) {
            $repository->addRemove($item);
        }
        $repository->saveData();
    }

    /**
     * Set data into a table with a config
     *
     * @param $config
     * @return array
     */
    public function run($config)
    {
        $translate = [];
        $excludePages = ['files/get'];
        $repository = $this->getRepository();
        $reflection = new ReflectionService();
        $simplerPlugin = $this->getSimpler();
        $items = $this->getItemsCollection('action', 0);
        $items = $simplerPlugin->setContext($items)->asAssociate('target');
        foreach ($config as $key => $controller) {
            $class = Reflection::reflectClass($controller);
            $methods = $class->getMethods();
            foreach ($methods as $method) {
                $methodName = $method->getName();
                if (strpos($methodName, 'Action')) {
                    $className = $class->getMethod($methodName)->class;
                    if ($controller == $className) {
                        $methodName = str_replace('Action', '', $methodName);
                        $action = preg_replace('/([a-z]+)+([A-Z])/', '$1-$2', $methodName);
                        $action = strtolower($action);
                        $classInfo = $reflection->getClassInfo($className);
                        $target = $key . '/' . $action;
                        $moduleName = $classInfo['module'];
                        if (!isset($items[$target]) && !in_array($target, $excludePages)) {
                            /** @var \Popov\ZfcPermission\Model\Permission $oneItem */
                            $oneItem = $this->getObjectModel();
                            $oneItem->setTarget($target);
                            $oneItem->setEntityId(0);
                            $oneItem->setType('action');
                            $oneItem->setModule($moduleName);
                            $oneItem->setParent(0);
                            $oneItem->setTypeField('');
                            $oneItem->setRequired(0);
                            $repository->addItem($oneItem);
                            $translate[] = '$this->translate("' . $key . '");';
                            $translate[] = '$this->translate("' . $action . '");';
                            $translate[] = '$this->translate("' . $key . '::' . $action . '");';
                        }
                    }
                }
            }
        }
        if (isset($oneItem)) {
            $repository->saveData();
        }

        return $translate;
    }

    /**
     * Set data into a table with a tables: fields_pages, permission_page_bind
     *
     * @param $items
     * @param $args
     */
    public function runFields($items, $args)
    {
        $modulePlugin = $this->getModulePlugin();
        $repository = $this->getRepository();
        $tmpTarget = '';
        /** @var \Popov\Fields\Model\FieldsPages $item [0] */
        foreach ($items as $item) {
            // Set param module name
            if (!$tmpTarget || $tmpTarget != $item['page']) {
                list($moduleMnemo, $action) = explode('/', $item['page']); // invoice/edit
                //$module = $modulePlugin->getBy(lcfirst((new SeparatorToCamelCase('-'))->filter($moduleMnemo)), 'mnemo');
                //$module = $modulePlugin->getBy($modulePlugin->toAlias($moduleMnemo), 'mnemo');
                //$module = $modulePlugin->getBy($moduleMnemo, 'mnemo');
				//\Zend\Debug\Debug::dump($moduleMnemo); die(__METHOD__);
                $entity = $modulePlugin->getEntityPlugin()->getEntityService()->getRepository()->findOneBy(['mnemo' => [
                    $moduleMnemo,
                    $modulePlugin->toAlias($moduleMnemo)
                ]]);
                if ($entity && $entity->getModule()) {
                    // new realization
                    $moduleName = $entity->getModule()->getName();
                } else {
                    // old realization
                    $moduleName = $entity->getNamespace();
                }

                $tmpTarget = $item['page'];
            }
			//\Zend\Debug\Debug::dump($moduleName); die(__METHOD__);
            /** @var Permission $permission */
            $permission = $this->getObjectModel();
            $permission->setTarget($item['page']);
            $permission->setEntityId($item[0]->getId());
            $permission->setType($args['type']);
            $permission->setModule($moduleName);
            $permission->setParent(0);
            $permission->setTypeField($args['typeField']);
            $permission->setRequired(0);
            $repository->addItem($permission);
        }
        if (isset($permission)) {
            $repository->saveData();
        }
    }

    public function routeToControllerNormalization($config)
    {
        $controllers = [];
        $checked = [];
        foreach ($config as $group => $subConfig) {
            foreach ($subConfig as $route => $controller) {
                if (class_exists($controller)
                    && is_subclass_of($controller, AbstractController::class)
                    && empty($checked[$controller]) // is not checked in prev iterations
                    && !class_exists($route) // is not class name then is route
                    && preg_match('/^[a-zA-Z0-9-_]+$/', $route) // exclude non route value
                ) {
                    $controllers[$route] = $controller;
                    $checked[$controller] = true;
                }
            }
        }

        return $controllers;
    }

    /**
     * @param User $user
     * @param $permissionsTree
     * @return array
     */
    public function mergeUserPermission(User $user, $permissionsTree)
    {
        $tree = [];
        foreach ($permissionsTree as $type => $rolesTree) {
            $tree[$type] = [];
            foreach ($user->getRoles() as $role) {
                // @todo Користувач може мати декілька ролей. По одній можуть бути виставлені права,
                // а по іншій звичайний доступ до екшена.
                // Виходить ніяких додаткових пташечок не стоїть, у випадку з розсилкою при зміні статусу,
                // користувачу не потрібно мати додаткові права на зміну статусу, щоб отримати розсилку.
                // Він просто може переглядати відповідну інформацію в системі.
                if (isset($rolesTree[$role->getId()])) {
                    $tree[$type] = array_merge($tree[$type], $rolesTree[$role->getId()]);
                }
            }
        }

        return $tree;
    }

    /**
     * Get Human Readable Permissions Tree for User
     *
     * Notice. This method actual only for type "setting"
     * If you need "field" type
     *
     * @param Entity $entity
     * @param User $user
     * param string $type Available: settings, fields
     * @return bool|array Array if access is set and false if have not access
     */
    public function getHumanReadablePermissionTree(Entity $entity, User $user/*, $type = 'settings'*/)
    {
        static $tree = [];
        if (!isset($tree[$entity->getId()])) {
            $om = $this->getObjectManager();
            /** @var \Popov\ZfcPermission\Model\Repository\PermissionSettingsRepository $repo */
            $repo = $om->getRepository('Popov\ZfcPermission\Model\PermissionSettings');
            if (!count($user->getRoles())) {
                return false;
            }
            $rolesId = [];
            foreach ($user->getRoles() as $role) {
                $rolesId[] = '0' . $role->getId() . '0';
            }
            $permissions = $repo->getPlainPermissions(['roleId' => $rolesId, 'moduleId' => $entity->getId()/*, 'type' => $type*/]);
            foreach ($permissions as $p) {
                $tree[$p['module']][$p['type']][$p['setting']][$p['entity']] = $p['permission'];
            }
            //\Zend\Debug\Debug::dump([$rolesId, $module->getId(), $tree]); die(__METHOD__);
        }

        return $tree;
    }

    /**
     * Get Human Readable Permissions Tree for Roles
     *
     * @param Module $module
     * @param int|int[]|Role|Role[] $roles
     * param string $type Available: settings, fields
     * @return bool|array Array if access is set and false if have not access
     */
    public function getHumanReadablePermissionsTree(Module $module, $roles)
    {
        $mask = '0%s0';
        $roleIds = [];
        if (is_array($roles) || $roles instanceof \Traversable) {
            foreach ($roles as $role) {
                if ($role instanceof Role) {
                    $roleIds[] = sprintf($mask, $role->getId());
                } else {
                    $roleIds[] = sprintf($mask, $role);
                }
            }
        } elseif (is_int($roles)) {
            $roleIds[] = sprintf($mask, $roles);
        } else {
            throw new InvalidArgumentException(sprintf(
                '%s expects $role parameter is Traversable, array or integer. Type %s has been passed.',
                __METHOD__,
                gettype($roles)
            ));
        }
        $om = $this->getObjectManager();
        /** @var \Popov\ZfcPermission\Model\Repository\PermissionSettingsRepository $repo */
        $repo = $om->getRepository('Popov\ZfcPermission\Model\PermissionSettings');
        $permissions =
            $repo->getPlainPermissions(['roleId' => $roleIds, 'moduleId' => $module->getId()/*, 'type' => $type*/]);
        $tree = [];
        foreach ($permissions as $p) {
            $p['mask'] = substr($p['mask'], 1, (strlen($p['mask']) - 2));
            //$tree[$permission['module']][$permission['type']][$permission['setting']][$permission['entity']] = $permission['permission'];
            $tree[$p['module']][$p['type']][$p['mask']][$p['setting']][$p['entity']] = $p['permission'];
        }

        return $tree;
    }



    //------------------------------------------Events------------------------------------------
    /**
     * Module Permission
     *
     * @param $class
     * @param $params
     */
    public function updatePermission($class, $params = [])
    {
        $event = new LogsEvent();
        $event->events($class)->trigger('permission.updatePermission', $this, $params);
    }
}