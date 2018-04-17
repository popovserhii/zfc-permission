<?php
namespace Popov\ZfcPermission\Controller;

use Popov\ZfcPermission\Service\PermissionAccessService;
use Zend\Mvc\Controller\AbstractActionController,
	Zend\View\Model\ViewModel,
	Popov\Popov\String\StringUtils as AgereString;
use Popov\ZfcPermission\PermissionHelper;

class PermissionAccessController extends AbstractActionController {

	public $serviceName = 'PermissionAccessService';

    /**
     * @var PermissionAccessService
     */
	protected $permissionAccessService;

	public function __construct(PermissionAccessService $permissionAccessService)
    {
        $this->permissionAccessService = $permissionAccessService;
    }

    /**
	 * @param array $request
	 * @param int $roleId
	 * @return array
	 */
	public function edit($post, $roleId) {
		/** @var \Popov\ZfcPermission\Service\PermissionAccessService $service */
		$service = $this->permissionAccessService;

		// Used roleId with possible user, role, group

        $permissionAccessRoleId = PermissionHelper::getStringAssocDigit($roleId, 'role');
		//$permissionAccessRoleId = AgereString::getStringAssocDigit($roleId, 'role');

		// Table permission_access
		$itemsAction = $service->getItemsByField($permissionAccessRoleId, 'roleId', 0, true);

		$condition = [
			'entityId'	=> [0, '>'],
			'type'		=> ['field'],
			'parent'	=> [0],
			'typeField'	=> ['permission'],
			'roleId'	=> [$permissionAccessRoleId],
		];
		$itemsField = $service->getItems($condition, 'permissionId');

		$condition = [
			'entityId'	=> [0, '>'],
			'type'		=> ['settings'],
			'parent'	=> [0],
			'roleId'	=> [$permissionAccessRoleId],
		];
		/** @var \Popov\ZfcPermission\Model\PermissionAccess[] $itemsSettings */
		$itemsSettings = $service->getItems($condition, 'permissionId');

		if ($post) {

			// Table permission_access accessAction
			$saveData = $this->_prepareSave('accessAction', $post, $permissionAccessRoleId);
			$service->saveData($saveData, $itemsAction);

			// Table permission_access accessField
			$saveData = $this->_prepareSave('accessField', $post, $permissionAccessRoleId);
			$service->saveData($saveData, $itemsField);

			// Table permission_access accessSettings
			$saveData = $this->_prepareSave('accessSettings', $post, $permissionAccessRoleId);
			$service->saveData($saveData, $itemsSettings);
		}

		return [
			'action'	=> $itemsAction,
			'field'		=> $itemsField,
			'settings'	=> $itemsSettings,
		];
	}


	//------------------------------------PRIVATE----------------------------------------
	/**
	 * @param string $keyPost
	 * @param array $post
	 * @param string $permissionAccessRoleId
	 * @return array
	 */
	private function _prepareSave($keyPost, $post, $permissionAccessRoleId) {
		$saveData = [];
		if (isset($post[$keyPost])) {
			foreach ($post[$keyPost] as $permissionId => $access) {
				$saveData['access'][$permissionId] = array_sum($access);
			}
		}
		$saveData['roleId'] = $permissionAccessRoleId;
		$saveData['resource'] = $post['resource'];

		return $saveData;
	}

}