<?php
namespace Popov\ZfcPermission\View\Helper;

use Zend\View\Helper\AbstractHelper,
	Zend\Mvc\Router\Http\RouteMatch,
	Popov\Popov\String\StringUtils as AgereString,
	Popov\Popov\ArrayCustom\ArrayCustom,
	Popov\Users\Acl\Acl;

class PermissionFields extends AbstractHelper {

	protected $user;
	protected $roleId;
	protected $route;
	protected $page;
	protected $acl;
	protected $access;
	protected $accessTotal;

	/** @var \Popov\ZfcPermission\Service\PermissionSettingsPagesService */
	protected $_permissionSettingsPagesService;

	/** @var \Popov\ZfcPermission\Service\PermissionPageBindService */
	protected $_permissionPageBindService;

	/** @var \Popov\Fields\Service\FieldsPagesService */
	protected $_fieldsPagesService;

	/*protected $pageFields = [
		'detailsStore' => [
			'permission'	=> ['manager', 'menedzher_filiala', 'korp_diler'],
			'fields'		=> ['presalePreparation', 'defect', 'optionalEquipment', 'anticorTreatment', 'carDetailNotation'],
		],
	];*/


	/**
	 * @param array $user
	 * @param RouteMatch $route
	 * @param $locator
	 */
	public function __construct(array $user, RouteMatch $route, $locator)
	{
        $this->userHelper = $locator->get('ViewHelperManager')->get('user');
        $this->simpler = $locator->get('ControllerPluginManager')->get('simpler');
        $this->roleId = AgereString::getStringAssocDigit($this->simpler->setContext($this->userHelper->current()->getRoles())->asArray('id'), 'role');
        //$this->roleId = AgereString::getStringAssocDigit($this->user['roleId'], 'role');

        $this->user = $user;
		$this->route = $route;
		$this->page = $this->route->getParam('controller').'/'.$this->route->getParam('action');

		$authEvent = $locator->get('Popov\Users\Event\Authentication');
		$this->acl = $authEvent->getAclClass();
		$this->access = Acl::getAccess();
		$this->accessTotal = Acl::getAccessTotal();

		$this->_permissionSettingsPagesService = $locator->get('PermissionSettingsPagesService');
		$this->_permissionPageBindService = $locator->get('PermissionPageBindService');
		$this->_fieldsPagesService = $locator->get('FieldsPagesService');
	}

	/**
	 * @param int $cityId
	 * @param array $argsSet
	 * @return bool
	 */
	public function detailsStoreAccess($cityId, array $argsSet)
	{
		if (! in_array('all', $this->user['resource']))
		{
			// Set settings
			$settingsFieldsId = null;
			$settingsCityId = null;

			$settingsPage = $this->_permissionSettingsPagesService->getSettingsByPage($this->page);

			foreach ($settingsPage as $item)
			{
				switch ($item['settingsMnemo'])
				{
					case 'fields':
						$settingsFieldsId = $item[0]->getId();
						break;
					case 'cityId':
						$settingsCityId = $item[0]->getId();
						break;
				}
			}

			$itemsSettings = [];

			foreach ($this->roleId as $key => $role)
			{
				$itemsSettings[$key] = $this->_permissionPageBindService->getItemsAccessBySettingsId([$settingsFieldsId], $role, 'childrenId');
			}
			// END Set settings

			// Set conditions
			$statusIdsCity = [];
			$fields = [];

			foreach ($itemsSettings as $key => $itemsSetting)
			{
				foreach ($itemsSetting as $childrenId => $items)
				{
					foreach ($items as $item)
					{
						switch ($childrenId)
						{
							case $settingsCityId:
								$statusIdsCity[$key][] = $item->getEntityId();
								break;
						}
					}
				}

				if (! isset($statusIdsCity[$key]))
				{
					$allowed['total'] = $this->acl->isAllowed($this->user['mnemo'][$key], $this->page, $this->accessTotal);
					$allowed['write'] = $this->acl->isAllowed($this->user['mnemo'][$key], $this->page, $this->access['write']);
					$allowed['read'] = $this->acl->isAllowed($this->user['mnemo'][$key], $this->page, $this->access['read']);

					if (in_array(true, $allowed))
					{
						$statusIdsCity[$key] = [];
					}
				}
			}

			$statusIdsCity = ArrayCustom::array_intersect2($statusIdsCity);

			if ($statusIdsCity)
			{
				$fields = $this->_fieldsPagesService->getFieldsByIds($statusIdsCity, 'mnemo');
			}
			// END Set conditions

			$user = $this->userHelper->current();
			$cityIds = $this->simpler->setContext($user->getCities())->asArray('id');
			//\Zend\Debug\Debug::dump($this->user['cityId']);
			
			// Check is permission on current page
			//if ($fields && ! in_array($cityId, $this->user['cityId']))
			if ($fields && !in_array($cityId, $cityIds))
			{
				foreach ($argsSet as $field => $args)
				{
					if (! in_array($field, $fields))
					{
						$argsSet[$field]['permission'] = false;
					}
				}
			}
			// END Check is permission on current page
		}

		return $argsSet;

		/*if (in_array($this->user['mnemo'], $this->pageFields['detailsStore']['permission']) && ! in_array($cityId, $this->user['cityId']))
		{
			foreach ($argsSet as $field => $args)
			{
				if (! in_array($field, $this->pageFields['detailsStore']['fields']))
				{
					$argsSet[$field]['permission'] = false;
				}
			}
		}

		return $argsSet;*/
	}

}