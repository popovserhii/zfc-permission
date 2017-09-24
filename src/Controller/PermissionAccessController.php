<?php
namespace Popov\ZfcPermission\Controller;

use Zend\Mvc\Controller\AbstractActionController,
	Zend\View\Model\ViewModel,
	Popov\Agere\String\StringUtils as AgereString;

class PermissionAccessController extends AbstractActionController {

	public $serviceName = 'PermissionAccessService';


	/**
	 * @param \Zend\ServiceManager\ServiceManager $sm
	 * @param null|\Zend\Http\Request $request
	 * @param int $roleId
	 * @return array
	 */
	public function edit($sm, $request, $roleId) {
		/** @var \Popov\ZfcPermission\Service\PermissionAccessService $service */
		$service = $sm->get($this->serviceName);

		// Used roleId with possible user, role, group
		$permissionAccessRoleId = AgereString::getStringAssocDigit($roleId, 'role');

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

		if ($request) {
			$post = $request->getPost()->toArray();

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