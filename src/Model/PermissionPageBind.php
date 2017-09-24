<?php

namespace Popov\ZfcPermission\Model;

use Doctrine\ORM\Mapping as ORM;
use Popov\ZfcCore\Model\DomainAwareTrait;

/**
 * PermissionPageBind
 */
class PermissionPageBind
{
    use DomainAwareTrait;
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $permissionSettingsPagesId;

    /**
     * @var integer
     */
    private $childrenId;

	/**
	 * @var integer
	 */
	private $entityId;

    /**
     * @var \Popov\ZfcPermission\Model\PermissionSettingsPages
     */
    private $permissionSettingsPages;

    private $permission;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set permissionSettingsPagesId
     *
     * @param integer $permissionSettingsPagesId
     * @return PermissionPageBind
     */
    public function setPermissionSettingsPagesId($permissionSettingsPagesId)
    {
        $this->permissionSettingsPagesId = $permissionSettingsPagesId;

        return $this;
    }

    /**
     * Get permissionSettingsPagesId
     *
     * @return integer 
     */
    public function getPermissionSettingsPagesId()
    {
        return $this->permissionSettingsPagesId;
    }

    /**
     * Set childrenId
     *
     * @param integer $childrenId
     * @return PermissionPageBind
     */
    public function setChildrenId($childrenId)
    {
        $this->childrenId = $childrenId;

        return $this;
    }

    /**
     * Get childrenId
     *
     * @return integer 
     */
    public function getChildrenId()
    {
        return $this->childrenId;
    }

	/**
	 * Set entityId
	 *
	 * @param integer $entityId
	 * @return PermissionPageBind
	 */
	public function setEntityId($entityId)
	{
		$this->entityId = $entityId;

		return $this;
	}

	/**
	 * Get entityId
	 *
	 * @return integer
	 */
	public function getEntityId()
	{
		return $this->entityId;
	}

    /**
     * Set permissionSettingsPages

     *
*@param \Popov\ZfcPermission\Model\PermissionSettingsPages $permissionSettingsPages
     * @return PermissionPageBind
     */
    public function setPermissionSettingsPages(\Popov\ZfcPermission\Model\PermissionSettingsPages $permissionSettingsPages = null)
    {
        $this->permissionSettingsPages = $permissionSettingsPages;

        return $this;
    }

    /**
     * Get permissionSettingsPages

     *
*@return \Popov\ZfcPermission\Model\PermissionSettingsPages
     */
    public function getPermissionSettingsPages()
    {
        return $this->permissionSettingsPages;
    }

    /**
     * @return mixed
     */
    public function getPermission() {
        return $this->permission;
    }

    /**
     * @param mixed $permission
     * @return PermissionPageBind
     */
    public function setPermission($permission) {
        $this->permission = $permission;

        return $this;
    }

}
