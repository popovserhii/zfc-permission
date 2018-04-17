<?php
namespace Popov\ZfcPermission\Model\Repository;

use Doctrine\ORM\Query\ResultSetMapping,
	Doctrine\ORM\Query\ResultSetMappingBuilder,
	Popov\ZfcCore\Service\EntityRepository;

class PermissionAccessRepository extends EntityRepository {

	protected $_table = 'permission_access';
	protected $_alias = 'pa';


	/**
	 * @param string $target
	 * @param int $entityId
	 * @param string $type
	 * @return array
	 */
	public function findItemRoles($target, $entityId, $type)
	{
		$rsm = new ResultSetMapping();

		$rsm->addEntityResult($this->getEntityName(), $this->_alias);
		$rsm->addFieldResult($this->_alias, 'id', 'id');
		$rsm->addFieldResult($this->_alias, 'roleId', 'roleId');
		$rsm->addFieldResult($this->_alias, 'access', 'access');

		$query = $this->_em->createNativeQuery(
			"SELECT {$this->_alias}.`id`, {$this->_alias}.`roleId`, {$this->_alias}.`access`
			FROM `permission` p
			INNER JOIN {$this->_table} {$this->_alias} ON p.`id` = {$this->_alias}.`permissionId`
			WHERE p.`target` = ? AND p.`entityId` = ? AND p.`type` = ?",
			$rsm
		);

		$query = $this->setParametersByArray($query, [$target, $entityId, $type]);

		return $query->getResult();
	}

	/**
	 * @param string $target
	 * @param string $type
	 * @param string $roleId
	 * @return array
	 */
	public function findItemsUser($target, $type, $roleId)
	{
		$rsm = new ResultSetMapping();

		$rsm->addEntityResult($this->getEntityName(), $this->_alias);
		$rsm->addFieldResult($this->_alias, 'id', 'id');
		$rsm->addFieldResult($this->_alias, 'roleId', 'roleId');
		$rsm->addFieldResult($this->_alias, 'access', 'access');
		$rsm->addScalarResult('parent', 'parent');

		$query = $this->_em->createNativeQuery(
			"SELECT {$this->_alias}.`id`, {$this->_alias}.`roleId`, {$this->_alias}.`access`, p.`parent`
			FROM `permission` p
			INNER JOIN {$this->_table} {$this->_alias} ON p.`id` = {$this->_alias}.`permissionId`
			WHERE p.`target` = ? AND p.`type` = ? AND pa.roleId = ?",
			$rsm
		);

		$query = $this->setParametersByArray($query, [$target, $type, $roleId]);

		return $query->getResult();
	}

	/**
	 * @param int $id
	 * @param string $field
	 * @return object
	 */
	public function findOneByIdAndField($id, $field)
	{
		$rsm = new ResultSetMapping();

		$rsm->addEntityResult($this->getEntityName(), $this->_alias);
		$rsm->addFieldResult($this->_alias, 'id', 'id');
		$rsm->addFieldResult($this->_alias, 'permissionId', 'permissionId');
		$rsm->addFieldResult($this->_alias, 'roleId', 'roleId');
		$rsm->addFieldResult($this->_alias, 'access', 'access');

		$query = $this->_em->createNativeQuery(
			"SELECT *
			FROM {$this->_table} {$this->_alias}
			WHERE {$field} = ?
			LIMIT 1",
			$rsm
		);

		$query = $this->setParametersByArray($query, [$id]);

		$result = $query->getResult();

		if (count($result) > 0)
		{
			$result = $result[0];
		}

		return $result;
	}

	/**
	 * @param string $target
	 * @param int $roleId
	 * @return object
	 */
	public function findAccessByTarget($target, $roleId)
	{
		$rsm = new ResultSetMapping();

		$rsm->addEntityResult($this->getEntityName(), $this->_alias);
		$rsm->addFieldResult($this->_alias, 'id', 'id');
		$rsm->addFieldResult($this->_alias, 'access', 'access');

		$query = $this->_em->createNativeQuery(
			"SELECT {$this->_alias}.`id`, {$this->_alias}.`access`
			FROM {$this->_table} {$this->_alias}
			INNER JOIN `permission` p ON p.`id` = {$this->_alias}.`permissionId`
			AND p.`target` = ?
			WHERE {$this->_alias}.`roleId` = ?
			LIMIT 1",
			$rsm
		);

		$query = $this->setParametersByArray($query, [$target, $roleId]);

		$result = $query->getResult();

		if (count($result) > 0)
		{
			$result = $result[0];
		}

		return $result;
	}

	/**
	 * @param int $id
	 * @param string $field
	 * @param  null|int $entityId
	 * @return array
	 */
	public function findItemsByField($id, $field = 'id', $entityId = null)
	{
		$rsm = new ResultSetMappingBuilder($this->_em);
		$rsm->addRootEntityFromClassMetadata($this->getEntityName(), $this->_alias);

		$join = '';
		$where = '';
		$data = [$id];

		if (!is_null($entityId))
		{
			$join = "INNER JOIN `permission` p ON {$this->_alias}.permissionId = p.id";
			$where = "AND p.`entityId` = ?";
			$data[] = $entityId;
		}

		$sql = "SELECT {$this->_alias}.*
			FROM {$this->_table} {$this->_alias}
			{$join}
			WHERE {$this->_alias}.$field = ? {$where}";

		$query = $this->_em->createNativeQuery($sql, $rsm);

		//\Zend\Debug\Debug::dump($sql); die(__METHOD__);

		$query = $this->setParametersByArray($query, $data);

		return $query->getResult();
	}

	/**
	 * @param string $target
	 * @param string $type
	 * @param array $roleIds
	 * @param null|int|array $parentId
	 * @return array
	 */
	public function findItemsByRoleId($target, $type, array $roleIds, $parentId = null)
	{
		$rsm = new ResultSetMapping();

		$rsm->addEntityResult($this->getEntityName(), $this->_alias);
		$rsm->addFieldResult($this->_alias, 'id', 'id');
		$rsm->addFieldResult($this->_alias, 'roleId', 'roleId');
		$rsm->addScalarResult('parent', 'parent');

		$idIn = $this->getIdsIn($roleIds);
		$data = [$target, $type];

		$joinWhere = '';
		if (is_null($parentId))
		{
			$joinWhere = 'AND p.`parent` > 0';
		}
		else
		{
			if (is_array($parentId))
			{
				$parentIdIn = $this->getIdsIn($parentId);
				$data = array_merge($data, $parentId);
			}
			else
			{
				$parentIdIn = '?';
				$data[] = $parentId;
			}

			$joinWhere = "AND p.`parent` IN ({$parentIdIn})";
		}

		$sql = "SELECT {$this->_alias}.`id`, {$this->_alias}.`roleId`, p.`parent`
			FROM `permission` p
			LEFT JOIN {$this->_table} {$this->_alias} ON p.`id` = {$this->_alias}.`permissionId`
			AND p.`target` = ? AND p.`type` = ? {$joinWhere}
			WHERE {$this->_alias}.`roleId` IN ({$idIn})";

		$query = $this->_em->createNativeQuery($sql, $rsm);



		$query = $this->setParametersByArray($query, array_merge($data, $roleIds));

		return $query->getResult();
	}

	/**
	 * @param string $target
	 * @param int $entityId
	 * @param string $type
	 * @param int $parent
	 * @param null|int $limitStart
	 * @param null|int $pageOn
	 * @return array
	 */
	public function findByParent($target, $entityId, $type, $parent, $limitStart = null, $pageOn = null)
	{
		$rsm = new ResultSetMapping;

		$rsm->addEntityResult($this->getEntityName(), $this->_alias);
		$rsm->addFieldResult($this->_alias, 'id', 'id');

		$limit = '';

		if (! is_null($limitStart))
		{
			$limit .= 'LIMIT '.$limitStart;

			if ($pageOn > 0)
			{
				$limit .= ', '.$pageOn;
			}
		}

		$query = $this->_em->createNativeQuery(
			"SELECT {$this->_alias}.`id`
			FROM `permission` p
			INNER JOIN {$this->_table} {$this->_alias} ON p.`id` = {$this->_alias}.`permissionId`
			WHERE p.`target` = ? AND p.`entityId` = ? AND p.`type` = ? AND p.`parent` = ?
			{$limit}",
			$rsm);

		$query = $this->setParametersByArray($query, [$target, $entityId, $type, $parent]);

		return $query->getResult();
	}

	/**
	 * @param array $args, example ['field' => [$val, '>'], 'field2' => [$val, '>=']]
	 * @return array
	 */
	public function findItems(array $args = []) {
		$rsm = new ResultSetMappingBuilder($this->_em);
		$rsm->addRootEntityFromClassMetadata($this->getEntityName(), $this->_alias);
		$where = '';
		$data = [];
		if ($args) {
			foreach ($args as $field => $values) {
				$where .= ($where == '') ? 'WHERE ' : ' AND ';
				$operand = (isset($values[1])) ? $values[1] : '=';
				$where .= "`{$field}` {$operand} ?";
				$data[] = $values[0];
			}
		}
		$query = $this->_em->createNativeQuery(
			"SELECT {$this->_alias}.*
			FROM {$this->_table} {$this->_alias}
			INNER JOIN `permission` p ON pa.`permissionId` = p.`id`
			{$where}", $rsm);
		if ($data) {
			$query = $this->setParametersByArray($query, $data);
		}

		return $query->getResult();
	}

}