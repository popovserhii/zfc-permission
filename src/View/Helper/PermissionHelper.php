<?php
namespace Popov\ZfcPermission\View\Helper;

use Zend\View\Helper\AbstractHelper;
//use Popov\Simpler\Helper\SimplerHelper;
use Popov\Simpler\SimplerHelper;

class PermissionHelper extends AbstractHelper {

	/**
	 * @var \Popov\ZfcPermission\Service\PermissionService
	 */
	protected $_permissionService;

	/**
	 * @var \Popov\Fields\Service\FieldsPagesService
	 */
	protected $_fieldsPagesService;

	/**
	 * @var \Popov\ZfcPermission\Service\PermissionSettingsPagesService
	 */
	protected $_permissionSettingsPagesService;

	/**
	 * @var \Popov\ZfcPermission\Service\PermissionPageBindService
	 */
	protected $_permissionPageBindService;

	/**
	 * @var \Popov\Status\Service\StatusService
	 */
	#protected $_statusService;

	/**
	 * @var \Popov\ZfcRole\Service\RoleService
	 */
	protected $_rolesService;

	protected $_access;

    /** @var \Popov\Simpler\SimplerHelper */
    protected $simplerHelper;

	public function __construct(
        $permissionService,
        $fieldsPagesService,
        $permissionSettingsPagesService,
        $permissionPageBindService,
        $rolesService,
        $simplerHelper,
        $access,
        $statusService = null
    ) {
		$this->_permissionService = $permissionService;
		$this->_fieldsPagesService = $fieldsPagesService;
		$this->_permissionSettingsPagesService = $permissionSettingsPagesService;
		$this->_permissionPageBindService = $permissionPageBindService;
        $this->_rolesService = $rolesService;
        $this->simplerHelper = $simplerHelper;
        $this->_access = $access;
        $this->_statusService = $statusService;
    }

    /**
     * @return SimplerHelper
     */
    public function getSimplerHelper()
    {
        return $this->simplerHelper;
    }

	/**
	 * @param array|object $tabs
	 * @return array
	 */
	public function permissionTree($tabs = [])
	{
        $simpler = $this->getSimplerHelper();

		//if (is_object($tabs)) {
			$tabs = $simpler($tabs)->asArray('controller');
            //$tabs = $this->_fieldsPagesService->toArrayKeyVal('controller', $tabs);
        //}

        $i = 0;
        $tree = [];
        $defaultTab = 'settings';
		$defaultKeyTab = (int) array_search($defaultTab, $tabs);

		// pages
		$collections = $this->_permissionService->getItemsCollection('action', 0);

		// settings for page
		$settingsPagesArray = $this->_permissionSettingsPagesService->getSettingsBindByPermissionId('page', true);
		$settingsPageBindArray = $this->_permissionPageBindService->getAllItems([], 'permissionSettingsPagesId');

		// fields for page
		$fieldsPagesArray = $this->_fieldsPagesService->getFieldsByPage(''/*, 'page'*/);

        //$selectedItems = $this->toArrayKeyField($fieldToArray, $selectedItems, true);
        //$itemsPageBind = $servicePageBind->toArrayKeyField('childrenId', $itemsPageBind, true);
        $fieldsPagesArray = $simpler->setContext($fieldsPagesArray)->asAssociate('page', true);


		foreach ($collections as $collection) { // collection (actions) from permission table
			// Pages. Get only module mnemo for bind permission to module and action/page to which access is applied
			$explode = explode('/', $collection->getTarget(), 2);

			$keyTab = in_array($explode[0], $tabs) ? array_search($explode[0], $tabs) : $defaultKeyTab;

			$tree[$keyTab][$explode[0]][$i] = [
				'id'	=> $collection->getId(),
				'text'	=> $explode[1],
			];
			// END pages

			// settings for page
			if (isset($settingsPagesArray[$collection->getTarget()])) { // Ось тут немає прив'язки з модулем
				$j = 0;
				//\Zend\Debug\Debug::dump($collection->getTarget()); //die(__METHOD__); // store/index
				foreach ($settingsPagesArray[$collection->getTarget()] as $settingsPage) {
					#$permissionId = (isset($settingsPageBindArray[$settingsPage['item'][0]->getId()]))
					#	? $settingsPageBindArray[$settingsPage['item'][0]->getId()]
					#	: 0;
					$permissionId = $settingsPageBindArray[$settingsPage['item'][0]->getId()] ?? 0;

					$tree[$keyTab][$explode[0]][$i]['settings'][$j] = [
						'id'     => (!$permissionId || !is_array($permissionId)) ? 0 : $permissionId[0]['permissionId'],
						'text'   => $settingsPage['item']['name'],
						'fields' => $settingsPage['fields'],
					];

					/*if (in_array($collection->getTarget(), ['cart/checkout-view'])) {
						//\Zend\Debug\Debug::dump(array_keys($settingsPage['item']));
						unset($settingsPage['item'][0]);
						//foreach($tabs as $key => $value) {
						//	\Zend\Debug\Debug::dump([$key, $value]);
						//}
						\Zend\Debug\Debug::dump($settingsPage);
					}*/

					if (!empty($settingsPage['item']['entityMnemo'])) {
						$permissionIds = is_array($permissionId)
							? $this->_fieldsPagesService->toArrayKeyField('entityId', $permissionId, true)
							: [];

						switch ($settingsPage['item']['entityMnemo']) {
							case 'status':
                                /** @var \Popov\Status\Model\Status[] $itemsStatuses */
                                $itemsStatuses = $this->_statusService->getItemsCollection($explode[0], 0);
								foreach ($itemsStatuses as $item) {
									if (isset($permissionIds[$item[0]->getId()])) {
										$tree[$keyTab][$explode[0]][$i]['settings'][$j]['args'][] = [
											'id'   => $this->getChildrenIds($permissionIds[$item[0]->getId()]),
											'text' => $item[0]->getName(),
										];
									}
								}
								break;
							case 'roles':
								$itemsRoles = $this->_rolesService->getItemsCollection();
								foreach ($itemsRoles as $item) {
									if (isset($permissionIds[$item->getId()])) {
										$tree[$keyTab][$explode[0]][$i]['settings'][$j]['args'][] = [
											'id'   => $this->getChildrenIds($permissionIds[$item->getId()]),
											'text' => $item->getRole(),
										];
									}
								}
								break;
							case 'fields_pages':
								$itemsFields = $this->_fieldsPagesService->getFieldsByPage($collection->getTarget());
								foreach ($itemsFields as $item) {
									if (isset($permissionIds[$item[0]->getId()])) {
										$tree[$keyTab][$explode[0]][$i]['settings'][$j]['args'][] = [
											'id'   => $this->getChildrenIds($permissionIds[$item[0]->getId()]),
											'text' => $item['name'],
										];
									}
								}
								break;
						}
					}
					++$j;
				}
			}
			// END settings for page

			// fields for page
			if (isset($fieldsPagesArray[$collection->getTarget()])) {
				foreach ($fieldsPagesArray[$collection->getTarget()] as $fieldPage) {
					$tree[$keyTab][$explode[0]][$i]['fields'][] = [
						'id'	=> $fieldPage['permissionId'],
						'text'	=> $fieldPage['name'],
					];
				}
			}
			// END fields for page

			++ $i;
		}

		return $tree;
	}

	/**
	 * @param int $value
	 * @return array
	 */
	public function getAccessValues($value)
	{
		$values = [];

		foreach ($this->_access as $key => $val)
		{
			if ($value >= $val)
			{
				$values[$key] = true;
				$value -= $val;
			}
			else
			{
				$values[$key] = false;
			}
		}

		return $values;
	}

	/**
	 * @param $items
	 * @param string $type
	 * @param int $id
	 * @return array
	 */
	public function getChecked($items, $type, $id)
	{
		$checked = ['read' => '', 'write' => ''];

		if (isset($items[$type][$id]))
		{
			$access = (is_object($items[$type][$id])) ? $items[$type][$id]->getAccess() : $items[$type][$id][0]->getAccess();
			$itemAccess = $this->getAccessValues($access);

			foreach ($checked as $key => $value)
			{
				if ($itemAccess[$key])
				{
					$checked[$key] = ' checked';
				}
			}
		}

		return $checked;
	}

	protected function getChildrenIds($permissionIds)
	{
		$ids = [];

		foreach ($permissionIds as $item)
		{
			$tmpId = $item[0]->getChildrenId() ? $item[0]->getChildrenId() : $item[0]->getPermissionSettingsPagesId();
			$ids[$tmpId] = $item['permissionId'];
		}

		return $ids;
	}

}