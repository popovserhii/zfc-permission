<?php

namespace Popov\ZfcPermission\Model;

use Doctrine\ORM\Mapping as ORM;
use Popov\ZfcCore\Model\DomainAwareTrait;

/**
 * PermissionAccess
 */
class PermissionAccess
{
    use DomainAwareTrait;

    const PERMISSION_READ = 2;
    const PERMISSION_WRITE = 4;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $permissionId;

    /**
     * @var string
     */
    private $roleId;

    /**
     * @var integer
     */
    private $access;

    /**
     * @var \Popov\ZfcPermission\Model\Permission
     */
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
     * Set permissionId
     *
     * @param integer $permissionId
     * @return PermissionAccess
     */
    public function setPermissionId($permissionId)
    {
        $this->permissionId = $permissionId;

        return $this;
    }

    /**
     * Get permissionId
     *
     * @return integer 
     */
    public function getPermissionId()
    {
        return $this->permissionId;
    }

    /**
     * Set roleId
     *
     * @param string $roleId
     * @return PermissionAccess
     */
    public function setRoleId($roleId)
    {
        $this->roleId = $roleId;

        return $this;
    }

    /**
     * Get roleId
     *
     * @return string 
     */
    public function getRoleId()
    {
        return $this->roleId;
    }

    /**
     * Set access
     *
     * @param integer $access
     * @return PermissionAccess
     */
    public function setAccess($access)
    {
        $this->access = $access;

        return $this;
    }

    /**
     * Get access
     *
     * @return integer 
     */
    public function getAccess()
    {
        return $this->access;
    }

    /**
     * Set permission

     *
*@param \Popov\ZfcPermission\Model\Permission $permission
     * @return PermissionAccess
     */
    public function setPermission(\Popov\ZfcPermission\Model\Permission $permission = null)
    {
        $this->permission = $permission;

        return $this;
    }

    /**
     * Get permission

     *
*@return \Popov\ZfcPermission\Model\Permission
     */
    public function getPermission()
    {
        return $this->permission;
    }
}
