<?php
namespace Popov\ZfcPermission\Service;

use Popov\Simpler\SimplerHelper;
use Popov\ZfcPermission\Model\Repository\PermissionPageBindRepository;
use Popov\ZfcPermission\Model\PermissionPageBind;
use Popov\ZfcCore\Service\DomainServiceAbstract;
use Popov\Simpler\Plugin\SimplerPlugin;

/**
 * @method PermissionPageBindRepository getRepository()
 */
class PermissionPageBindService extends DomainServiceAbstract
{
    protected $entity = PermissionPageBind::class;

    /** @var SimplerPlugin */
    protected $simplerPlugin;

    public function __construct(SimplerHelper $simplerPlugin)
    {
        $this->simplerPlugin = $simplerPlugin;
    }

    public function getSimpler()
    {
        return $this->simplerPlugin;
    }

    /**
     * @param array $groupBy
     * @param string $fieldToArray
     * @return array
     */
    public function getAllItems($groupBy = [], $fieldToArray = '')
    {
        $simplerPlugin = $this->getSimpler();
        $repository = $this->getRepository();
        $items = $repository->findAllItems($groupBy);
        if ($fieldToArray) {
            //$selectedReviews = $this->toArrayKeyField($fieldToArray, $selectedReviews, true);
            $items = $simplerPlugin->setContext($items)->asAssociate($fieldToArray, true);
        }

        return $items;
    }

    /**
     * @param int $settingId
     * @return array
     */
    public function getItemsBySettingsId($settingId)
    {
        $repository = $this->getRepository();

        return $repository->findItemsBySettingsId($settingId);
    }

    /**
     * @param array $settingIds
     * @param string|array $roleId
     * @param string $fieldToArray
     * @param array $childrenIds
     * @param null|int $entityId
     * @return array
     */
    public function getItemsAccessBySettingsId(
        array $settingIds,
        $roleId,
        $fieldToArray = '',
        array $childrenIds = [],
        $entityId = null
    ) {
        $simplerPlugin = $this->getSimpler();
        $repository = $this->getRepository();
        $items = $repository->findItemsAccessBySettingsId($settingIds, $roleId, $childrenIds, $entityId);
        if ($fieldToArray) {
            //$selectedReviews = $this->toArrayKeyField($fieldToArray, $selectedReviews, true);
            $items = $simplerPlugin->setContext($items)->asAssociate($fieldToArray, true);
        }

        return $items;
    }

    /**
     * @return array
     */
    public function getNotAddPermission()
    {
        $repository = $this->getRepository();

        return $repository->findNotAddPermission();
    }

    /**
     * @param array $data
     */
    public function saveData($data)
    {
        foreach ($data as $key => $args) {
            $items[$key] = $this->getObjectModel();
            foreach ($args as $field => $val) {
                $method = 'set' . ucfirst($field);
                $items[$key]->$method($val);
            }
            $this->getObjectManager()->persist($items[$key]);
        }
        if (isset($items)) {
            $this->getObjectManager()->flush();
        }
    }
}