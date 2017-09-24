<?php

namespace Popov\ZfcPermission\Model;

use Doctrine\ORM\Mapping as ORM;
use Popov\ZfcCore\Model\DomainAwareTrait;
/**
 * Permission
 */
class Permission
{
    use DomainAwareTrait;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $target;

    /**
     * @var integer
     */
    private $entityId;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $module;

    /**
     * Item ID
     *
     * In context of user permissions this take the Brand identifier.
     * That means in email sending will check user access to this brand.
     * Other context not yet explored.
     *
     * @var integer
     */
    private $parent;

    /**
     * @var string
     */
    private $typeField = '';

    /**
     * @var string
     */
    private $required;

    /**
     * @var
     */
    private $permissionPageBind;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $permissionAccess;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->permissionAccess = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * Set target
     *
     * @param string $target
     * @return Permission
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Get target
     *
     * @return string 
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Set entityId
     *
     * @param integer $entityId
     * @return Permission
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
     * Set type
     *
     * @param string $type
     * @return Permission
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set module
     *
     * @param string $module
     * @return Permission
     */
    public function setModule($module)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * Get module
     *
     * @return string 
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Set parent
     *
     * @param integer $parent
     * @return Permission
     */
    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return integer 
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set typeField
     *
     * @param string $typeField
     * @return Permission
     */
    public function setTypeField($typeField)
    {
        $this->typeField = $typeField;

        return $this;
    }

    /**
     * Get typeField
     *
     * @return string 
     */
    public function getTypeField()
    {
        return $this->typeField;
    }

    /**
     * Set required
     *
     * @param string $required
     * @return Permission
     */
    public function setRequired($required)
    {
        $this->required = $required;

        return $this;
    }

    /**
     * Get required
     *
     * @return string 
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * @return mixed
     */
    public function getPermissionPageBind() {
        return $this->permissionPageBind;
    }

    /**
     * @param mixed $permissionPageBind
     * @return Permission
     */
    public function setPermissionPageBind($permissionPageBind) {
        $this->permissionPageBind = $permissionPageBind;

        return $this;
    }

    /**
     * Add permissionAccess

     *
*@param \Popov\ZfcPermission\Model\PermissionAccess $permissionAccess
     * @return Permission
     */
    public function addPermissionAccess(\Popov\ZfcPermission\Model\PermissionAccess $permissionAccess)
    {
        $this->permissionAccess[] = $permissionAccess;

        return $this;
    }

    /**
     * Remove permissionAccess

     *
*@param \Popov\ZfcPermission\Model\PermissionAccess $permissionAccess
     */
    public function removePermissionAccess(\Popov\ZfcPermission\Model\PermissionAccess $permissionAccess)
    {
        $this->permissionAccess->removeElement($permissionAccess);
    }

    /**
     * Get permissionAccess
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPermissionAccess()
    {
        return $this->permissionAccess;
    }
}
