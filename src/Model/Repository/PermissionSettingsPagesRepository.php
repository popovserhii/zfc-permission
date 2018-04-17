<?php
namespace Popov\ZfcPermission\Model\Repository;

use Doctrine\ORM\Query\ResultSetMapping,
	Doctrine\ORM\Query\ResultSetMappingBuilder;
use Popov\ZfcCore\Service\EntityRepository;


class PermissionSettingsPagesRepository extends EntityRepository {

	protected $_table = 'permission_settings_pages';
	protected $_alias = 'psp';


	/**
	 * @param string $page, example 'controller/action'
	 * @param string|array $settingsMnemo
	 * @return array
	 */
	public function findSettingsByPage($page = '', $settingsMnemo = '') {
		$rsm = new ResultSetMapping();
		$rsm->addEntityResult($this->getEntityName(), $this->_alias);
		$rsm->addFieldResult($this->_alias, 'id', 'id');
		$rsm->addScalarResult('name', 'name');
		$rsm->addScalarResult('settingsMnemo', 'settingsMnemo');
		$rsm->addScalarResult('entityMnemo', 'entityMnemo');
		$rsm->addScalarResult('page', 'page');

		$where = '';
		$data = [];
		if ($page) {
			$where = 'WHERE p.`page` = ?';
			$data[] = $page;
		}

		if ($settingsMnemo) {
			if (is_string($settingsMnemo)) {
				$settingsMnemo = (array) $settingsMnemo;
			}
			$strIn = $this->getIdsIn($settingsMnemo);
			$where .= ($where != '') ? ' AND ' : 'WHERE ';
			$where .= "ps.`mnemo` IN ({$strIn})";
			$data = array_merge($data, $settingsMnemo);
		}

		$sql = "SELECT {$this->_alias}.`id`, ps.`name`, ps.`mnemo` AS settingsMnemo, e.`mnemo` AS entityMnemo, p.`page`
			FROM `{$this->_table}` {$this->_alias}
			INNER JOIN `permission_settings` ps ON {$this->_alias}.`permissionSettingsId` = ps.`id`
			LEFT JOIN `entity` e ON ps.`entityId` = e.`id`
			INNER JOIN `pages` p ON {$this->_alias}.`pagesId` = p.`id`
			{$where}";

		//\Zend\Debug\Debug::dump($sql); //die(__METHOD__);

		$query = $this->_em->createNativeQuery($sql, $rsm);
		if ($page != '') {
			$query = $this->setParametersByArray($query, $data);
		}

		return $query->getResult();
	}

	/**
	 * @return array
	 */
	public function findSettingsEntity()
	{
		$rsm = new ResultSetMapping();

		$rsm->addEntityResult($this->getEntityName(), $this->_alias);
		$rsm->addFieldResult($this->_alias, 'id', 'id');
		$rsm->addScalarResult('name', 'name');
		$rsm->addScalarResult('settingsMnemo', 'settingsMnemo');
		$rsm->addScalarResult('entityMnemo', 'entityMnemo');
		$rsm->addScalarResult('page', 'page');

		$query = $this->_em->createNativeQuery(
			"SELECT {$this->_alias}.`id`, ps.`name`, ps.`mnemo` AS settingsMnemo, e.`mnemo` AS entityMnemo, p.`page`
			FROM `{$this->_table}` {$this->_alias}
			INNER JOIN `permission_settings` ps ON {$this->_alias}.`permissionSettingsId` = ps.`id`
			INNER JOIN `entity` e ON ps.`entityId` = e.`id`
			INNER JOIN `pages` p ON {$this->_alias}.`pagesId` = p.`id`
			WHERE ps.`entityId` IS NOT NULL",
			$rsm
		);

		return $query->getResult();
	}

}