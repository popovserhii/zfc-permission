<?php
namespace Popov\ZfcPermission\Service;

use Popov\Simpler\SimplerHelper;
use Popov\ZfcCore\Service\DomainServiceAbstract;
use Popov\ZfcPermission\Model\PermissionSettingsPages;
use \Popov\ZfcPermission\Model\Repository\PermissionSettingsPagesRepository;

/**
 * @method PermissionSettingsPagesRepository getRepository()
 */
class PermissionSettingsPagesService extends DomainServiceAbstract
{
    protected $entity = PermissionSettingsPages::class;

    /** @var SimplerHelper */
    protected $simplerPlugin;

    /**
     * @var PermissionPageBindService
     */
    protected $permissionPageBindService;

    public function __construct(SimplerHelper $simplerPlugin, PermissionPageBindService $permissionPageBindService)
    {
        $this->simplerPlugin = $simplerPlugin;
        $this->permissionPageBindService = $permissionPageBindService;
    }

    public function getSimpler()
    {
        return $this->simplerPlugin;
    }

    public function getPermissionPageBindService()
    {
        return $this->permissionPageBindService;
    }

    /**
     * @param string $fieldToArray
     * @param bool $addKeyInt
     * @return array
     */
    public function getSettingsBindByPermissionId($fieldToArray, $addKeyInt = false)
    {
        $result = [];
        $simplerPlugin = $this->getSimpler();
        $items = $this->getSettingsByPage();

        //$itemsArray = $this->toArrayKeyField('id', $items);
        $itemsArray = $simplerPlugin->setContext($items)->asAssociate('id');
        $method = 'get' . ucfirst($fieldToArray);

        $permissionBindService = $this->getPermissionPageBindService();
        $itemsBind = $permissionBindService->getAllItems([
            'permissionSettingsPagesId',
            'childrenId',
        ], 'permissionSettingsPagesId');

        //\Zend\Debug\Debug::dump(array_keys($itemsBind)); //die(__METHOD__);
        foreach($items as $item) {
            $fields = []; // масив в якому зберігаються поля для налаштування http://screencast.com/t/nzr2fyt2izM
            if (!isset($itemsBind[$item[0]->getId()])) {
                continue;
            } else {
                /*if (in_array($item['page'], ['cart/checkout-view'])) {
                    \Zend\Debug\Debug::dump([$item[0]->getId(), $item['page']]); //die(__METHOD__);
                }*/
                foreach($itemsBind[$item[0]->getId()] as $args) {
                    $tmpId = $args[0]->getChildrenId()
                        ? $args[0]->getChildrenId()
                        : $args[0]->getPermissionSettingsPagesId();
                    $fields[] = [
                        'id' => $tmpId,
                        'name' => $itemsArray[$tmpId]['name'],
                    ];
                    /*if (in_array($item['page'], ['cart/checkout-view'])) {
                        \Zend\Debug\Debug::dump($fields); //die(__METHOD__);
                    }*/
                }
            }
            $tmpArgs = [
                'item' => $item,
                'fields' => $fields,
            ];
            if (!is_object($item)) {
                $key = (isset($item[$fieldToArray])) ? $item[$fieldToArray] : $item[0]->$method();
            } else {
                $key = $item->$method();
            }
            //\Zend\Debug\Debug::dump($key);
            if ($addKeyInt) {
                $result[$key][] = $tmpArgs;
            } else {
                $result[$key] = $tmpArgs;
            }
        }

        //\Zend\Debug\Debug::dump($result); die(__METHOD__);
        return $result;
    }

    /**
     * @param string $page , example 'controller/action'
     * @param string|array $settingsMnemo
     * @param string $fieldToArray
     * @return array
     */
    public function getSettingsByPage($page = '', $settingsMnemo = '', $fieldToArray = '')
    {
        $repository = $this->getRepository();
        $items = $repository->findSettingsByPage($page, $settingsMnemo);
        if ($fieldToArray) {
            $simplerPlugin = $this->getSimpler();
            $items = $simplerPlugin->setContext($items)->asAssociate($fieldToArray, true);
            //$items = $this->toArrayKeyField($fieldToArray, $items, true);
        }

        return $items;
    }

    /**
     * @return array
     */
    public function getSettingsEntity()
    {
        $repository = $this->getRepository();

        return $repository->findSettingsEntity();
    }
}