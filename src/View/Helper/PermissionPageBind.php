<?php
namespace Popov\ZfcPermission\View\Helper;

use Popov\Popov\View\Helper\AbstractHelper;

class PermissionPageBind extends AbstractHelper
{
	/**
	 * @var \Popov\ZfcPermission\Service\PermissionPageBindService $_permissionPageBindService
	 */
	protected $_permissionPageBindService;

	/**
	 * @var \Popov\ZfcPermission\Service\PermissionSettingsPagesService $_permissionSettingsPagesService
	 */
	protected $_permissionSettingsPagesService;


	public function __construct() {
		$this->_permissionPageBindService = $this->getServiceLocator()->get('PermissionPageBindService');
		$this->_permissionSettingsPagesService = $this->getServiceLocator()->get('PermissionSettingsPagesService');
	}

	/**
	 * @param string $page
	 * @param string $mnemo
	 * @return bool
	 */
	public function checkPermission($page, $mnemo)
	{
		if (! in_array('all', $this->getCurrentUser()['resource']))
		{
			// Set settings
			$settingsPage = $this->_permissionSettingsPagesService->getSettingsByPage($page, $mnemo);

			foreach ($settingsPage as $item)
			{
				$itemsSettings = $this->_permissionPageBindService->getItemsAccessBySettingsId([$item[0]->getId()], $this->getRoleId());

				return (bool) $itemsSettings;
			}
		}

		return true;
	}

}