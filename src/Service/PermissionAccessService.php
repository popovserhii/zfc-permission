<?php
namespace Popov\ZfcPermission\Service;

use Popov\Simpler\SimplerHelper;
use Popov\ZfcPermission\Acl\Acl;
use \Popov\ZfcPermission\Model\Permission;
use Popov\ZfcPermission\Model\PermissionAccess;
use Popov\ZfcPermission\Model\Repository\PermissionAccessRepository;
use Popov\ZfcCore\Service\DomainServiceAbstract;
use Popov\Simpler\Plugin\SimplerPlugin;

/**
 * @method PermissionAccessRepository getRepository()
 */
class PermissionAccessService extends DomainServiceAbstract
{
    protected $entity = PermissionAccess::class;

    /** @var SimplerPlugin */
    protected $simplerPlugin;

    public function __construct(SimplerHelper $simplerPlugin)
    {
        $this->simplerPlugin = $simplerPlugin;
    }

    public function getSimplerPlugin()
    {
        return $this->simplerPlugin;
    }

    public function getAccess()
    {
        return Acl::getAccess();
    }

    /**
     * @param string $target
     * @param int $entityId
     * @param string $type
     * @return array
     */
    public function getItemRolesArray($target, $entityId, $type)
    {
        $itemsArray = [];
        $repository = $this->getRepository();
        $items = $repository->findItemRoles($target, $entityId, $type);
        foreach ($items as $item) {
            $itemsArray[$item->getRoleId()] = $item;
        }

        return $itemsArray;
    }

    /**
     * @param string $target
     * @param string $type
     * @param string $roleId
     * @return array
     */
    public function getItemsUserArray($target, $type, $roleId)
    {
        $itemsArray = [];
        $repository = $this->getRepository();
        $items = $repository->findItemsUser($target, $type, $roleId);
        foreach ($items as $item) {
            $itemsArray[$item['parent']] = $item;
        }

        return $itemsArray;
    }

    /**
     * @param int $id
     * @param string $field
     * @return mixed
     */
    public function getItem($id, $field = 'id')
    {
        $repository = $this->getRepository();

        return $repository->findOneByIdAndField($id, $field);
    }

    /**
     * @param string $target
     * @param int $roleId
     * @return mixed
     */
    public function getAccessByTarget($target, $roleId)
    {
        $repository = $this->getRepository();

        return $repository->findAccessByTarget($target, $roleId);
    }

    /**
     * @param int $id
     * @param string $field
     * @param null|int $entityId
     * @param bool $toArray
     * @return mixed
     */
    public function getItemsByField($id, $field = 'id', $entityId = null, $toArray = false)
    {
        $repository = $this->getRepository();
        $result = $repository->findItemsByField($id, $field, $entityId);
        if ($toArray) {
            $result = $this->getSimplerPlugin()->setContext($result)->asAssociate('permissionId');
        }

        return $result;
    }

    /**
     * @param string $target
     * @param string $type
     * @param array $maskIds In database wrong name "roleId"
     * @param null|int|array $parentId
     * @return array
     */
    public function getItemsByRoleId($target, $type, array $maskIds, $parentId = null)
    {
        $result = [];
        $repository = $this->getRepository();
        $simplerPlugin = $this->getSimplerPlugin();
        //$data = $this->getValuesArray('id', $roleIds, '%s00');
        //$data = $simplerPlugin->setContext($roleIds)->getValuesArray('id', $roleIds, '%s00');
        $data = $simplerPlugin->setContext($maskIds)->addHandler(function ($value) {
            return sprintf('%s00', $value);
        })->asArrayValue('id');
        $items = $repository->findItemsByRoleId($target, $type, $data, $parentId);
        foreach ($items as $item) {
            $result[$item[0]->getRoleId() /* this must be named "maskId" */][] = $item['parent'];
        }

        return $result;
    }

    /**
     * @param string $target
     * @param int $entityId
     * @param string $type
     * @param int $parent
     * @param null|int $limitStart
     * @param null|int $pageOn
     * @return array
     */
    public function getByParent($target, $entityId, $type, $parent, $limitStart = null, $pageOn = null)
    {
        $repository = $this->getRepository();

        return $repository->findByParent($target, $entityId, $type, $parent, $limitStart, $pageOn);
    }

    /**
     * @param array $args , example ['field' => [$val, '>'], 'field2' => [$val, '>=']]
     * @param string $keyField
     * @return array
     */
    public function getItems(array $args = [], $keyField = '')
    {
        $repository = $this->getRepository();
        $items = $repository->findItems($args);
        if ($keyField != '') {
            //$items = $this->toArrayKeyField($keyField, $items);
            $simplerPlugin = $this->getSimplerPlugin();
            $items = $simplerPlugin->setContext($items)->asAssociate($keyField);
        }

        return $items;
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function insert($data)
    {
        $om = $this->getObjectManager();
        $repository = $this->getRepository();
        $class = $repository->getEntity();
        $oneItem = new $class;
        foreach ($data as $field => $val) {
            $method = 'set' . ucfirst($field);
            if ($field == 'permission') {
                $parent = isset($val['parent']) ? $val['parent'] : 0;
                //$obj = $this->getService('permission')
                $obj = $om->getRepository(Permission::class)->findOneItem($val['target'], $val['entityId'], $val['type'], $parent);
                //\Zend\Debug\Debug::dump([$obj->getId(), $val]); die(__METHOD__);
                $oneItem->$method($obj);
            } else {
                $oneItem->$method($val);
            }
        }
        //$repository = $this->getRepository($this->_repositoryName);
        $repository->save($oneItem);

        return $oneItem;
    }

    public function saveData($data, $items)
    {
        $om = $this->getObjectManager();
        $repository = $this->getRepository();
        if ($data['resource'] == 'all') {
            $this->deleteByRoleId($data['roleId'], 0);
        } else {
            $addItems = false;
            if (isset($data['access'])) {
                foreach ($data['access'] as $key => $val) {
                    $addItems = true;
                    if (!isset($items[$key])) {
                        $items[$key] = $this->getObjectModel();
                    }
                    //$obj = $this->getService('permission')->getItemById($key);
                    $obj = $om->find(Permission::class, $key);
                    if (!is_object($items[$key])) {
                        $items[$key] = $items[$key][0];
                    }
                    $items[$key]->setPermission($obj);
                    $items[$key]->setPermissionId($key);
                    $items[$key]->setRoleId($data['roleId']);
                    $items[$key]->setAccess($val);
                    $om->persist($items[$key]);
                    unset($items[$key]);
                }
            }
            if ($addItems) {
                $om->flush();
            }
            if ($items) {
                foreach ($items as $item) {
                    $om->remove($item);
                }
                $om->flush();
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
     * @param \Popov\ZfcPermission\Model\Permission $oneItem
     */
    public function deleteByPermissionId(\Popov\ZfcPermission\Model\Permission $oneItem)
    {
        $repository = $this->getRepository();
        foreach ($oneItem->getPermissionAccess() as $item) {
            $repository->addRemove($item);
        }
        $repository->saveData();
    }

    /**
     * @param int $roleId
     * @param null|int $entityId
     */
    public function deleteByRoleId($roleId, $entityId = null)
    {
        $repository = $this->getRepository();
        $items = $this->getItemsByField($roleId, 'roleId', $entityId);
        foreach ($items as $item) {
            $repository->addRemove($item);
        }
        $repository->saveData();
    }
}