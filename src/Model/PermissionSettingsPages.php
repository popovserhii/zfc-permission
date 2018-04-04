<?php

namespace Popov\ZfcPermission\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Popov\ZfcCore\Model\DomainAwareTrait;
use Popov\ZfcFields\Model\Pages;

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
     * @var Collection
     */
    private $permissionPageBind;

    /**
     * @var PermissionSettings
     */
    private $permissionSettings;

    /**
     * @var Pages
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
     * @param PermissionPageBind $permissionPageBind
     * @return PermissionSettingsPages
     */
    public function addPermissionPageBind(PermissionPageBind $permissionPageBind)
    {
        $this->permissionPageBind[] = $permissionPageBind;

        return $this;
    }

    /**
     * Remove permissionPageBind
     *
     * @param PermissionPageBind $permissionPageBind
     */
    public function removePermissionPageBind(PermissionPageBind $permissionPageBind)
    {
        $this->permissionPageBind->removeElement($permissionPageBind);
    }

    /**
     * Get permissionPageBind
     *
     * @return Collection
     */
    public function getPermissionPageBind()
    {
        return $this->permissionPageBind;
    }

    /**
     * Set permissionSettings
     *
     * @param PermissionSettings $permissionSettings
     * @return PermissionSettingsPages
     */
    public function setPermissionSettings(PermissionSettings $permissionSettings = null)
    {
        $this->permissionSettings = $permissionSettings;

        return $this;
    }

    /**
     * Get permissionSettings
     *
     * @return PermissionSettings
     */
    public function getPermissionSettings()
    {
        return $this->permissionSettings;
    }

    /**
     * Set pages
     *
     * @param Pages $pages
     * @return PermissionSettingsPages
     */
    public function setPages(Pages $pages = null)
    {
        $this->pages = $pages;

        return $this;
    }

    /**
     * Get pages
     *
     * @return Pages
     */
    public function getPages()
    {
        return $this->pages;
    }
}
