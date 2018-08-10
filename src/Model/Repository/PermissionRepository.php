<?php
namespace Popov\ZfcPermission\Model\Repository;

use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Popov\ZfcPermission\Model\Permission;
use Popov\ZfcCore\Model\Repository\EntityRepository;

class PermissionRepository extends EntityRepository {

	protected $_table = 'permission';
	protected $_alias = 'p';

	/**
	 * @param string $type
	 * @param null|int $entityId
	 * @param bool $parent
	 * @return mixed
	 */
	public function findAllItems($type, $entityId = null, $parent = false)
	{
		$rsm = new ResultSetMappingBuilder($this->_em);
		$rsm->addRootEntityFromClassMetadata($this->getEntityName(), $this->_alias);

		$where = $parent ? "{$this->_alias}.`parent` > 0" : "{$this->_alias}.`parent` = 0";
		$where .= (! is_null($entityId)) ? " AND {$this->_alias}.`entityId` = ?" : '';

		$query = $this->_em->createNativeQuery(
			"SELECT * FROM {$this->_table} {$this->_alias}
			WHERE {$this->_alias}.`type` = ? AND {$where}",
			$rsm);

		$query = $this->setParametersByArray($query, [$type, $entityId]);

		return $query->getResult();
	}

	/**
	 * @param string $target
	 * @param string $type
	 * @param int $parent
	 * @param string $typeField
	 * @return mixed
	 */
	public function findItemsByParent($target, $type, $parent, $typeField = 'required')
	{
		$rsm = new ResultSetMappingBuilder($this->_em);
		$rsm->addRootEntityFromClassMetadata($this->getEntityName(), $this->_alias);

		$query = $this->_em->createNativeQuery(
			"SELECT * FROM {$this->_table} {$this->_alias}
			WHERE {$this->_alias}.`target` = ? AND {$this->_alias}.`type` = ? AND {$this->_alias}.`parent` = ?
			AND {$this->_alias}.`typeField` = ?",
			$rsm);

		$query = $this->setParametersByArray($query, [$target, $type, $parent, $typeField]);

		return $query->getResult();
	}

	/**
	 * @param string $target
	 * @param int $entity
	 * @param string $type
	 * @param int $parent
	 * @return mixed
	 */
	public function findOneItem($target, $entity, $type, $parent = 0)
	{
		$rsm = new ResultSetMappingBuilder($this->_em);
		$rsm->addRootEntityFromClassMetadata($this->getEntityName(), $this->_alias);

		$query = $this->_em->createNativeQuery(
			"SELECT * FROM {$this->_table} {$this->_alias}
			WHERE {$this->_alias}.`target` = ? AND {$this->_alias}.`entityId` = ? AND {$this->_alias}.`type` = ?
			AND {$this->_alias}.`parent` = ?
			LIMIT 1",
			$rsm
		);

		$query = $this->setParametersByArray($query, [$target, $entity, $type, $parent]);

		$result = $query->getResult();

		if (count($result) == 0)
		{
			$result = $this->createOneItem();
		}
		else
		{
			$result = $result[0];
		}

		return $result;
	}

	/**
	 * @param int $id
	 * @return null|object
	 */
	public function findOneByIdAndCreateNew($id)
	{
		$result = $this->findOneById($id);

		if (count($result) == 0)
		{
			$result = $this->createOneItem();
		}

		return $result;
	}

	public function getPermissions() {
		$rsm = new ResultSetMappingBuilder($this->_em);

		//\Zend\Debug\Debug::dump($selectClause); die(__METHOD__);
		//$rsm->addRootEntityFromClassMetadata('MyProject\User', 'u');
		//$rsm->addJoinedEntityFromClassMetadata('MyProject\Address', 'a', 'u', 'address', array('id' => 'address_id'));

		$rsm->addRootEntityFromClassMetadata($this->getEntityName(), $this->_alias);
		$rsm->addJoinedEntityFromClassMetadata(
			'Popov\ZfcPermission\Model\PermissionAccess', 'pa', $this->_alias, 'permissionAccess', ['id' => 'pa_id']
		);
		//$rsm->addFieldResult('pa', 'pa_id', 'id');
		//$rsm->addFieldResult('pa', 'permissionId', 'permissionId');
		//$rsm->addFieldResult('pa', 'roleId', 'roleId');
		//$rsm->addFieldResult('pa', 'access', 'access');
		$rsm->addJoinedEntityFromClassMetadata(
			'Popov\ZfcPermission\Model\PermissionPageBind', 'ppb', $this->_alias, 'permissionPageBind', ['id' => 'ppb_id', 'entityId' => 'ppd_entityId']
		);

		$selectClause = $rsm->generateSelectClause([
			$this->_alias => 'p',
			'pa' => 'pa',
			'ppb' => 'ppb',
		]);

		$sql = <<<SQL
SELECT {$selectClause}
FROM (
	SELECT ppb.id, ppb.`permissionSettingsPagesId`, ppb.`childrenId`, ppb.`entityId`
	FROM `permission_page_bind` ppb
	INNER JOIN permission_settings_pages psp ON ( ppb.`childrenId` = 0 AND psp.id = ppb.`permissionSettingsPagesId` )
	INNER JOIN `permission_settings` ps ON ps.`id` = psp.`permissionSettingsId`
	UNION
	SELECT ppb.id, ppb.`childrenId` AS permissionSettingsPagesId, ppb.`entityId`, ps.entityId as moduleId
	FROM `permission_page_bind` ppb
	INNER JOIN permission_settings_pages psp ON ( ppb.`childrenId` != 0 AND psp.id = ppb.`childrenId` )
	INNER JOIN `permission_settings` ps ON ps.`id` = psp.`permissionSettingsId`
) ppb
INNER JOIN `permission` p ON ppb.`id` = p.`entityId`
INNER JOIN `permission_access` pa ON pa.`permissionId` = p.`id`

WHERE p.`type`='settings' AND roleId='020'
SQL;

		$query = $this->_em->createNativeQuery($sql, $rsm);

		//$query = $this->setParametersByArray($query, [$target, $entity, $type, $parent]);

		$result = $query->getResult();

		//\Zend\Debug\Debug::dump($result[0]->getPermissionAccess()[0]->getAccess()); die(__METHOD__);
		//\Zend\Debug\Debug::dump(count($result)); die(__METHOD__);

		return $result;

	}

}