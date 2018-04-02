<?php

namespace Popov\ZfcPermission\Model;

use Doctrine\ORM\Mapping as ORM;
use Popov\ZfcCore\Model\DomainAwareTrait;
use Popov\ZfcEntity\Model\Entity;

/**
 * PermissionSettings
 */
class PermissionSettings {

    use DomainAwareTrait;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $mnemo;

    /**
     * @var integer
     */
    private $entityId;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @deprecated
     */
    private $permissionSettingsPages;

    /**
     * @var Entity
     */
    private $entity;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->permissionSettingsPages = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set name
     *
     * @param string $name
     * @return PermissionSettings
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set mnemo
     *
     * @param string $mnemo
     * @return PermissionSettings
     */
    public function setMnemo($mnemo)
    {
        $this->mnemo = $mnemo;

        return $this;
    }

    /**
     * Get mnemo
     *
     * @return string 
     */
    public function getMnemo()
    {
        return $this->mnemo;
    }

    /**
     * Set entityId
     *
     * @param integer $entityId
     * @return PermissionSettings
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
     * Add permissionSettingsPages

     *
*@param \Popov\ZfcPermission\Model\PermissionSettingsPages $permissionSettingsPages
     * @return PermissionSettings
     */
    public function addPermissionSettingsPage(\Popov\ZfcPermission\Model\PermissionSettingsPages $permissionSettingsPages)
    {
        $this->permissionSettingsPages[] = $permissionSettingsPages;

        return $this;
    }

    /**
     * Remove permissionSettingsPages

     *
*@param \Popov\ZfcPermission\Model\PermissionSettingsPages $permissionSettingsPages
     */
    public function removePermissionSettingsPage(\Popov\ZfcPermission\Model\PermissionSettingsPages $permissionSettingsPages)
    {
        $this->permissionSettingsPages->removeElement($permissionSettingsPages);
    }

    /**
     * Get permissionSettingsPages
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPermissionSettingsPages()
    {
        return $this->permissionSettingsPages;
    }

    /**
     * Set entity
     *
     * @param Entity $entity
     * @return PermissionSettings
     */
    public function setEntity(Entity $entity = null)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get entity
     *
     * @return Entity
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
