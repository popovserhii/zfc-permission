<?php
namespace Popov\ZfcPermission\Model\Repository;

use Doctrine\ORM\Query\ResultSetMapping,
	Doctrine\ORM\Query\ResultSetMappingBuilder,
	Popov\Agere\ORM\EntityRepository;

class PermissionSettingsRepository extends EntityRepository {

	protected $_table = 'permission_settings';
	protected $_alias = 'ps';

	/**
	 * Human readable representation of permissions data
	 *
	 * Example usage:
	 * $repository->getPlainPermissions(['roleId'=>['020'], 'moduleId'=>7, 'type'=>'settings']);
	 *
	 * Example return:
	 * [0] => array(6) {
	 *    ["setting"] => string(6) "status"
	 *    ["module"] => int(7)
	 *    ["entity"] => int(2)
	 *    ["type"] => string(8) "settings"
	 *    ["mask"] => string(3) "030"
	 *    ["permission"] => int(4)
	 * }
	 *
	 * @param array $conditions
	 * @return array
	 */
	public function getPlainPermissions(array $conditions = []) {
		$rsm = new ResultSetMappingBuilder($this->_em);

		// Реалізація повної відповідності об'єктній структурі.
		// Можна використовувати об'єкти але тоді знижується швидкодія і ускладнюється сприйняття логіки
		#$rsm->addRootEntityFromClassMetadata($this->getEntityName(), $this->_alias,
		#	['id' => 'ps_id', 'name' => 'ps_name', 'mnemo' => 'ps_mnemo', 'entityId' => 'ps_entityId']
		#);
		#$rsm->addJoinedEntityFromClassMetadata('Popov\ZfcPermission\Model\PermissionSettingsPages', 'psp', $this->_alias, 'permissionSettingsPages',
		#	['id' => 'psp_id', 'permissionSettingsId' => 'psp_permissionSettingsId', 'pagesId' => 'psp_pagesId']
		#);
		#$rsm->addJoinedEntityFromClassMetadata('Popov\ZfcPermission\Model\PermissionPageBind', 'ppb', 'psp', 'permissionPageBind',
		#	['id' => 'ppb_id', 'permissionSettingsPagesId' => 'ppb_permissionSettingsPagesId', 'childrenId' => 'ppb_childrenId', 'entityId' => 'ppb_entityId']
		#);
		#$rsm->addJoinedEntityFromClassMetadata('Popov\ZfcPermission\Model\Permission', 'p', 'ppb', 'permission',
		#	['id' => 'p_id', 'target' => 'p_target', 'entityId' => 'p_entityId', 'type' => 'p_type', 'module' => 'p_module', 'parent' => 'p_parent', 'typeField' => 'p_typeField', 'required' => 'p_required']
		#);
		#$rsm->addJoinedEntityFromClassMetadata('Popov\ZfcPermission\Model\PermissionAccess', 'pa', 'p', 'permissionAccess',
		#	['id' => 'pa_id', 'permissionId' => 'pa_permissionId', 'roleId' => 'pa_roleId', 'access' => 'pa_access']
		#);

		// also is possibility get scalar data as array that then can be group for oneself
		// @link http://doctrine-orm.readthedocs.org/projects/doctrine-orm/en/latest/reference/native-sql.html#scalar-results
		$rsm->addScalarResult('p_type', 'type');
		$rsm->addScalarResult('p_target', 'target');
		$rsm->addScalarResult('pa_roleId', 'mask');
		$rsm->addScalarResult('ps_entityId', 'module', 'integer');
		$rsm->addScalarResult('ps_mnemo', 'setting');
		$rsm->addScalarResult('ppb_entityId', 'entity', 'integer');
		$rsm->addScalarResult('pa_access', 'permission', 'integer');

		$sql = <<<SQL
SELECT ppb.*,
	  p.id p_id, p.target p_target, p.entityId p_entityId, p.type p_type, p.module p_module, p.parent p_parent, p.typeField p_typeField, p.required p_required,
	  pa.id pa_id, pa.permissionId pa_permissionId,	pa.roleId pa_roleId, pa.access pa_access
FROM (
	SELECT ps.id ps_id, ps.name ps_name, ps.mnemo ps_mnemo, ps.entityId ps_entityId, ps.entityId moduleId,
		psp.id psp_id, psp.permissionSettingsId psp_permissionSettingsId, psp.pagesId psp_pagesId,
		ppb.id ppb_id, ppb.permissionSettingsPagesId ppb_permissionSettingsPagesId, ppb.childrenId ppb_childrenId, ppb.entityId ppb_entityId
	FROM `permission_page_bind` ppb
	INNER JOIN permission_settings_pages psp ON ( ppb.`childrenId` = 0 AND psp.id = ppb.`permissionSettingsPagesId` )
	INNER JOIN `permission_settings` ps ON ps.`id` = psp.`permissionSettingsId`
	UNION
	SELECT ps.id ps_id, ps.name ps_name, ps.mnemo ps_mnemo, ps.entityId ps_entityId, ps.entityId moduleId,
		psp.id psp_id, psp.permissionSettingsId psp_permissionSettingsId, psp.pagesId psp_pagesId,
		ppb.id ppb_id, ppb.permissionSettingsPagesId ppb_permissionSettingsPagesId, ppb.childrenId ppb_childrenId, ppb.entityId ppb_entityId
	FROM `permission_page_bind` ppb
	INNER JOIN permission_settings_pages psp ON ( ppb.`childrenId` != 0 AND psp.id = ppb.`childrenId` )
	INNER JOIN `permission_settings` ps ON ps.`id` = psp.`permissionSettingsId`
) ppb
INNER JOIN `permission` p ON ppb.`ppb_id` = p.`entityId`
INNER JOIN `permission_access` pa ON pa.`permissionId` = p.`id`
SQL;
		// Important!!! `roleId` must have "020" format

		#if (!isset($conditions['type'])) {
		#	$conditions['type'] = 'settings';
		#	//$conditions['type'] = 'field';
		#}

		$where = [];
		foreach ($conditions as $field => $value) {
			$pattern = '`%s`=:%s';
			if (is_array($value)) {
				$pattern = '`%s` IN(:%s)';
			}
			$where[] = sprintf($pattern, $field, $field);
		}
		if ($where) {
			//AND `moduleId`='7' AND roleId IN('020') AND type='settings'
			$sql .= ' WHERE ' . implode(' AND ', $where);
		}

		$query = $this->_em->createNativeQuery($sql, $rsm);
		$query->setParameters($conditions);
		$result = $query->getResult();

		//\Zend\Debug\Debug::dump([$sql, $conditions, $result]); die(__METHOD__);


		return $result;

	}

}