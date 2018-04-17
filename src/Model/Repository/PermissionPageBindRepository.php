<?php
namespace Popov\ZfcPermission\Model\Repository;

use Doctrine\ORM\Query\ResultSetMapping,
	Doctrine\ORM\Query\ResultSetMappingBuilder;
use Popov\ZfcCore\Service\EntityRepository;


class PermissionPageBindRepository extends EntityRepository {

	protected $_table = 'permission_page_bind';
	protected $_alias = 'ppb';
	protected $_typePermission = 'settings';


	/**
	 * @param array $groupBy
	 * @return array
	 */
	public function findAllItems($groupBy = [])
	{
		$rsm = new ResultSetMapping();

		$rsm->addEntityResult($this->getEntityName(), $this->_alias);
		$rsm->addFieldResult($this->_alias, 'id', 'id');
		$rsm->addFieldResult($this->_alias, 'permissionSettingsPagesId', 'permissionSettingsPagesId');
		$rsm->addFieldResult($this->_alias, 'childrenId', 'childrenId');
		$rsm->addFieldResult($this->_alias, 'entityId', 'entityId');
		$rsm->addScalarResult('permissionId', 'permissionId');

		$group = ($groupBy) ? 'GROUP BY `'.implode('`, `', $groupBy).'`' : '';

		$sql = "SELECT {$this->_alias}.`id`, {$this->_alias}.`permissionSettingsPagesId`, {$this->_alias}.`childrenId`,
			{$this->_alias}.`entityId`, p.`id` AS permissionId
			FROM {$this->_table} {$this->_alias}
			INNER JOIN `permission` p ON {$this->_alias}.`id` = p.`entityId` AND p.`type` = '".$this->_typePermission."'
			{$group}";
		$query = $this->_em->createNativeQuery($sql, $rsm);

		//\Zend\Debug\Debug::dump($sql); die(__METHOD__);


		return $query->getResult();
	}

	/**
	 * @param int $settingId
	 * @return array
	 */
	public function findItemsBySettingsId($settingId)
	{
		$rsm = new ResultSetMappingBuilder($this->_em);
		$rsm->addRootEntityFromClassMetadata($this->getEntityName(), $this->_alias);

		$query = $this->_em->createNativeQuery(
			"SELECT {$this->_alias}.*
			FROM `{$this->_table}` {$this->_alias}
			LEFT JOIN `permission_settings_pages` psp ON ({$this->_alias}.`permissionSettingsPagesId` = psp.`id`
			AND {$this->_alias}.`childrenId` = 0) OR {$this->_alias}.`childrenId` = psp.`id`
			WHERE {$this->_alias}.`permissionSettingsPagesId` = ?",
			$rsm);

		$query = $this->setParametersByArray($query, [$settingId]);

		return $query->getResult();
	}

	/**
	 * @param array $settingIds
	 * @param string|array $roleId
	 * @param array $childrenIds
	 * @param null|int $entityId
	 * @return array
	 */
	public function findItemsAccessBySettingsId(array $settingIds, $roleId, array $childrenIds = [], $entityId = null) {
		$rsm = new ResultSetMappingBuilder($this->_em);
		$rsm->addRootEntityFromClassMetadata($this->getEntityName(), $this->_alias);

		$where = '';
		$idsIn = $this->getIdsIn($settingIds);
		if ($idsIn) {
			$where .= "AND {$this->_alias}.`permissionSettingsPagesId` IN ({$idsIn})";
		}
		if (!is_array($roleId)) {
			$roleId = (array) $roleId;
		}

		$idsInRoleId = $this->getIdsIn($roleId);
		$settingIds = array_merge($roleId, $settingIds);
		if ($childrenIds) {
			$idsInChildren = $this->getIdsIn($childrenIds);
			$settingIds = array_merge($settingIds, $childrenIds);
			$where .= "AND {$this->_alias}.`childrenId` IN ({$idsInChildren})";
		}

		if (!empty($entityId)) {
			$settingIds[] = $entityId;
			$where .= "AND {$this->_alias}.`entityId` = ?";
		}

		$sql = <<<SQL
SELECT {$this->_alias}.*
FROM `{$this->_table}` {$this->_alias}
INNER JOIN `permission` p ON {$this->_alias}.`id` = p.`entityId`
INNER JOIN `permission_access` pa ON pa.`permissionId` = p.`id`
WHERE p.type = '{$this->_typePermission}' AND pa.roleId IN ({$idsInRoleId}) {$where}
SQL;

		//\Zend\Debug\Debug::dump([$settingIds, $sql]); //die(__METHOD__);


		$query = $this->_em->createNativeQuery($sql, $rsm);
		$query = $this->setParametersByArray($query, $settingIds);

		return $query->getResult();
	}

	/**
	 * @return array
	 */
	public function findNotAddPermission()
	{
		$rsm = new ResultSetMapping();

		$rsm->addEntityResult($this->getEntityName(), $this->_alias);
		$rsm->addFieldResult($this->_alias, 'id', 'id');
		$rsm->addScalarResult('page', 'page');

		$sql = "SELECT {$this->_alias}.`id`, p.`page`
			FROM `{$this->_table}` {$this->_alias}
			INNER JOIN `permission_settings_pages` psp ON {$this->_alias}.`permissionSettingsPagesId` = psp.`id`
			INNER JOIN `pages` p ON psp.`pagesId` = p.`id`
			LEFT JOIN `permission` pn ON {$this->_alias}.`id` = pn.`entityId` AND pn.`type` = '".$this->_typePermission."'
			WHERE pn.`id` IS NULL";
		$query = $this->_em->createNativeQuery($sql, $rsm);

		return $query->getResult();
	}

}