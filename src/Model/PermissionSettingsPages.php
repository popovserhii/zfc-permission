<?php

namespace Popov\ZfcPermission\Model;

use Doctrine\ORM\Mapping as ORM;
use Popov\ZfcCore\Model\DomainAwareTrait;

/**
 * PermissionSettingsPages
 */
class PermissionSettingsPages
{
    use DomainAwareTrait;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $permissionSettingsId;

    /**
     * @var integer
     */
    private $pagesId;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $permissionPageBind;

    /**
     * @var \Popov\ZfcPermission\Model\PermissionSettings
     */
    private $permissionSettings;

    /**
     * @var \Popov\Fields\Model\Pages
     */
    private $pages;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->permissionPageBind = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set permissionSettingsId
     *
     * @param integer $permissionSettingsId
     * @return PermissionSettingsPages
     */
    public function setPermissionSettingsId($permissionSettingsId)
    {
        $this->permissionSettingsId = $permissionSettingsId;

        return $this;
    }

    /**
     * Get permissionSettingsId
     *
     * @return integer 
     */
    public function getPermissionSettingsId()
    {
        return $this->permissionSettingsId;
    }

    /**
     * Set pagesId
     *
     * @param integer $pagesId
     * @return PermissionSettingsPages
     */
    public function setPagesId($pagesId)
    {
        $this->pagesId = $pagesId;

        return $this;
    }

    /**
     * Get pagesId
     *
     * @return integer 
     */
    public function getPagesId()
    {
        return $this->pagesId;
    }

    /**
     * Add permissionPageBind

     *
*@param \Popov\ZfcPermission\Model\PermissionPageBind $permissionPageBind
     * @return PermissionSettingsPages
     */
    public function addPermissionPageBind(\Popov\ZfcPermission\Model\PermissionPageBind $permissionPageBind)
    {
        $this->permissionPageBind[] = $permissionPageBind;

        return $this;
    }

    /**
     * Remove permissionPageBind

     *
*@param \Popov\ZfcPermission\Model\PermissionPageBind $permissionPageBind
     */
    public function removePermissionPageBind(\Popov\ZfcPermission\Model\PermissionPageBind $permissionPageBind)
    {
        $this->permissionPageBind->removeElement($permissionPageBind);
    }

    /**
     * Get permissionPageBind
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPermissionPageBind()
    {
        return $this->permissionPageBind;
    }

    /**
     * Set permissionSettings

     *
*@param \Popov\ZfcPermission\Model\PermissionSettings $permissionSettings
     * @return PermissionSettingsPages
     */
    public function setPermissionSettings(\Popov\ZfcPermission\Model\PermissionSettings $permissionSettings = null)
    {
        $this->permissionSettings = $permissionSettings;

        return $this;
    }

    /**
     * Get permissionSettings

     *
*@return \Popov\ZfcPermission\Model\PermissionSettings
     */
    public function getPermissionSettings()
    {
        return $this->permissionSettings;
    }

    /**
     * Set pages
     *
     * @param \Popov\Fields\Model\Pages $pages
     * @return PermissionSettingsPages
     */
    public function setPages(\Popov\Fields\Model\Pages $pages = null)
    {
        $this->pages = $pages;

        return $this;
    }

    /**
     * Get pages
     *
     * @return \Popov\Fields\Model\Pages
     */
    public function getPages()
    {
        return $this->pages;
    }
}
