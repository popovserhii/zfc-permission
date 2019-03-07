<?php
/**
 * The MIT License (MIT)
 * Copyright (c) 2017 Serhii Popov
 * This source file is subject to The MIT License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @category Popov
 * @package Popov_ZfcPermission
 * @author Serhii Popov <popow.serhii@gmail.com>
 * @license https://opensource.org/licenses/MIT The MIT License (MIT)
 */
namespace Popov\ZfcPermission;

use Popov\Simpler\SimplerHelper;
use Popov\ZfcCurrent\CurrentHelper;
use Zend\View\Helper\Url;
use Zend\Mvc\MvcEvent as MvcEvent;
//Zend\Permissions\Acl\Acl as Acl,
use Zend\Session\Container as SessionContainer;
use Zend\ServiceManager\Exception;
use Zend\Stdlib\ArrayUtils;
use Zend\Authentication\AuthenticationService;
use Zend\Permissions\Acl\Resource\GenericResource;
use Zend\Permissions\Acl\Role\GenericRole;

use Popov\ZfcUser\Model\User;
use Popov\ZfcUser\Controller\Plugin\AuthService;
use Popov\ZfcCore\Service\ConfigAwareTrait;
use Popov\ZfcUser\Controller\Plugin\UserPlugin;
//use Popov\Popov\String\StringUtils as AgereString;
use Popov\ZfcUser\Controller\Plugin\AuthService as AuthPlugin;
use Popov\ZfcPermission\Acl\Acl;
use Zend\Stdlib\Request;

/**
 * Class Authentication
 *
 * @package Popov\ZfcUser\Event
 */
class PermissionHelper
{
    use ConfigAwareTrait;

    protected $permissionDenied = true;

    /**
     * Redirect params
     *
     * @var array
     */
    protected $redirect = [];

    /** @var AuthenticationService */
    protected $authService;

    /** @var Request */
    //protected $request;

    /** @var AuthPlugin */
    protected $_userAuth = null;

    /** @var Acl */
    protected $acl = null;

    /**
     * @var User
     */
    protected $user;

    protected $roles = [];

    protected $adapter;

    /**
     * @var CurrentHelper
     */
    protected $currentHelper;

    /**
     * @var SimplerHelper
     */
    protected $simpleHelper;

    /**
     * @var UrlHelper
     */
    protected $urlHelper;

    /**
     * Use Permission Module for access
     *
     * @var bool
     */
    //protected $usePermission = false;

    protected $accessDefault = 6;

    protected $denyDefault = 0;

    //public function __construct(CurrentHelper $currentHelper, SimplerHelper $simplerHelper, UrlHelper $urlHelper)
    public function __construct(CurrentHelper $currentHelper, SimplerHelper $simplerHelper, Url $urlHelper)
    {
        $this->currentHelper = $currentHelper;
        $this->simpleHelper = $currentHelper;
        $this->urlHelper = $urlHelper;
        //$this->config = $config;
    }

    /**
     * @return CurrentHelper
     */
    public function getCurrentHelper()
    {
        return $this->currentHelper;
    }

    public function setAuthService($authService)
    {
        $this->authService = $authService;
    }

    public function getAuthService()
    {
        return $this->authService;
    }


    /*public function setUsePermission($usePermission)
    {
        $this->usePermission = $usePermission;

        return $this;
    }*/

    /**
     * Sets Authentication Plugin
     *
     * @param AuthPlugin $userAuthenticationPlugin
     * @return $this
     */
    /*public function setUserAuthenticationPlugin(AuthPlugin $userAuthenticationPlugin)
    {
        $this->_userAuth = $userAuthenticationPlugin;

        return $this;
    }*/

    /**
     * Gets Authentication Plugin
     *
     * @return Authentication
     */
    /*public function getUserAuthenticationPlugin()
    {
        return $this->_userAuth;
    }*/

    /**
     * Sets ACL Class
     *
     * @param Acl $acl
     * @return $this
     */
    public function setAcl(Acl $acl)
    {
        $this->acl = $acl;

        return $this;
    }

    /**
     * Gets ACL Class
     *
     * @return Acl
     */
    public function getAcl()
    {
        if ($this->acl === null) {
            $this->acl = new Acl();
        }

        return $this->acl;
    }

    /**
     * @return Acl
     * @deprecated
     */
    public function getAclClass()
    {
        return $this->getAcl();
    }

    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setRoles(array $roles)
    {
        $this->roles = $roles;

        return $this;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function setDbAdapter($adapter)
    {
        $this->adapter = $adapter;
    }

    public function getDbAdapter()
    {
        return $this->adapter;
    }

    public function getRedirect()
    {
        return $this->redirect;
    }

    /**
     * @param string|array $args
     * @param string $assocDigit, possible values: user, role, group
     * @return mixed
     */
    public static function getStringAssocDigit($args, $assocDigit) {
        $assocDigits = [
            'user'  => '%s00',
            'role'  => '0%s0',
            'group' => '00%s',
        ];

        if (!isset($assocDigits[$assocDigit])) {
            return $args;
        }
        if (is_array($args)) {
            foreach ($args as $key => $val) {
                $args[$key] = sprintf($assocDigits[$assocDigit], $val);
            }
        } else {
            $args = sprintf($assocDigits[$assocDigit], $args);
        }

        return $args;
    }

    /**
     * Parse string filled [0-9] and 0 to be thrown away, return int,
     * examples: 020 return [id => 2, field => role]; 0200 return [id => 20, field => role],
     * 02000 return [id => 200, field => role], 200 return [id => 2, field => user]
     *
     * @param $str
     * @param array $assocDigits - associate a Digits, default [user, role, group]
     * @return array
     */
    public static function parseStringAssocDigit($str, $assocDigits = ['user', 'role', 'group'])
    {
        $countDigits = count($assocDigits);
        $tmpCountDigits = $countDigits;
        $assocDigit = '';
        $toInt = '';
        for ($i = 0, $k = strlen($str); $i < $k; ++$i) {
            $remainder = $k - $i;
            if ($str[$i] == 0 && $toInt == '') {
                --$tmpCountDigits;
            } else if ($str[$i] > 0 OR ($toInt != '' && $remainder >= $tmpCountDigits)) {
                $toInt .= $str[$i];
                if ($remainder == $tmpCountDigits) {
                    $j = $countDigits - $tmpCountDigits;
                    $assocDigit = $assocDigits[$j];
                    break;
                }
            }
        }

        return [
            'id' => (int) $toInt,
            'field' => $assocDigit,
        ];
    }

    public static function getResourceName($area, $controller, $action)
    {
        return $controller . '/' . (($area == 'default') ? : $area . '-') . $action;
    }

    /**
	 * Initialization ACL resources for current user
	 */
	public function init() {
        $this->initRoles();
		foreach ($this->roles as $role => $resources) {
			$role = new GenericRole($role);
			$this->acl->addRole($role);
			//adding resources
			foreach ($resources as $resource) {
                if (!$this->acl->hasResource($resource['target'])) {
					$this->acl->addResource(new GenericResource($resource['target']));
				}
				if ($resource['access'] == $this->denyDefault) {
					$this->acl->deny($role, $resource['target'], $resource['access']);
				} else {
					$this->acl->allow($role, $resource['target'], $resource['access']);
				}
			}
		}
	}

    public function checkPermission()
    {
        $this->checkGeneralPermission();
        $this->checkItemPermission();

        return $this->permissionDenied;
    }

    public function checkGeneralPermission()
    {
        $roleMnemos = [Acl::DEFAULT_ROLE];
        $access = Acl::getAccess();
        $accessTotal = Acl::getAccessTotal();

        //$userPlugin = $this->getAuthService();
        #$user = ($userPlugin->hasIdentity() && ($user = $userPlugin->getIdentity())) ? $user : false;
        //$user = $userPlugin->getIdentity();
        $user = $this->getUser();

        /** @var UserPlugin $userPlugin */
        if ($user && $user->getId()/* && $user->getIsInner()*/) {
            $roleMnemos = [];
            foreach ($user->getRoles() as $role) {
                $roleMnemos[] = $role->getMnemo();
            }

            // Update expire login
            $sessionAuth = new SessionContainer('Zend_Auth');
            in_array('admin', $roleMnemos)
                ? $sessionAuth->setExpirationSeconds(60 * 60 * 48) // 24 hours
                : $sessionAuth->setExpirationSeconds(60 * 60 * 24); // 60 minutes

            #foreach ($this->getConfig()['acl']['guest'] as $resource) {
            #    $this->acl->addResource(new GenericResource($resource['target']));
            #    $this->acl->allow($roleMnemos, $resource['target'], $resource['access']);
            #}
        }

        //$hasResource = $this->acl->hasResource('all');
        $isAllowed = $this->acl->hasResource('all')
            ? $this->acl->isAllowed($roleMnemos, 'all', $accessTotal)
            : false;
        $allowed = [$isAllowed];

        // Allowed session
        if (isset($_SESSION['location'])) {
            $controller = $_SESSION['location']['params']['controller'];
            $action = $_SESSION['location']['params']['action'];
            $area = $_SESSION['location']['params']['area'];

            //$controller = $controller . '/' . $action;
            $resource = self::getResourceName($area, $controller, $action);
            // Allowed
            if ($this->acl->hasResource($controller)) {
                $allowed[] = $this->acl->isAllowed($roleMnemos, $resource, $accessTotal);
                $allowed[] = $this->acl->isAllowed($roleMnemos, $resource, $access['write']);
                $allowed[] = $this->acl->isAllowed($roleMnemos, $resource, $access['read']);
            }

            if (in_array(true, $allowed)) {
                $this->redirect = $_SESSION['location'];
                unset($_SESSION['location']);

                return;
            }
        }

        // Resource
        $area = $this->currentHelper->currentRouteParams()['area'] ?? null;
        $controller = $this->currentHelper->currentController();
        $action = $this->currentHelper->currentAction();
        $resource = self::getResourceName($area, $controller, $action);
        
        $resourceFull = ($this->urlHelper)(
            $this->currentHelper->currentRouteName(),
            $this->currentHelper->currentRouteParams()
        );

        // Allowed
        if ($this->acl->hasAccessByRoles($roleMnemos, $resource)
            || $this->acl->hasAccessByRoles($roleMnemos, $resourceFull)
        ) {
            $this->permissionDenied = false;
            return;
        }

        if ($user/* && $user->getIsInner()*/) {
            //$event->stopPropagation(true); // very important string
            //$viewModel->permissionDenied = false;
            // permission denied
            return;
        } else {
            $_SESSION['location'] = [
                'route' => $this->currentHelper->currentRouteName(),
                'params' => $this->currentHelper->currentRouteParams(),
            ];
            $this->redirect = [
                'route' => 'admin/default',
                'params' => [
                    'controller' => 'user',
                    'action' => 'login',
                ],
            ];
        }
    }

    /**
     * Check if user has access to page with ID
     */
    public function checkItemPermission()
    {
        $params = $this->currentHelper->currentRouteParams();

        $where = '';
        $dbAdapter = $this->getDbAdapter();
        $user = $this->getUser();

        // Acl class
        $acl = $this->getAcl();
        $role = $user ? $user->getRoles()->first() : false;
        if ($role && $role->getMnemo() && !$acl->isAllowed($role->getMnemo(), 'all', Acl::ACCESS_TOTAL)) {
            // Where
            if (isset($params['id']) && $params['id'] > 0) {
                $where = "(p.`target` = '{$params['controller']}/{$params['action']}/{$params['id']}'
						AND p.`entityId` = {$params['id']} AND p.`type` = 'action')";
            }

            if ($where != '') {
                // Table permission
                $permissionId = 0;
                $resultPermission = $dbAdapter->query(
                    "SELECT p.`id` FROM `permission` p WHERE {$where}",
                    $dbAdapter::QUERY_MODE_EXECUTE
                );

                foreach ($resultPermission as $result) {
                    $permissionId = $result['id'];
                }
                if ($permissionId > 0) {
                    $roleIds = $this->simpleHelper->setContext($user->getRoles())->asArray('id');
                    $roleId = self::getStringAssocDigit($roleIds, 'role');
                    $roleId = implode(', ', $roleId);
                    $userId = self::getStringAssocDigit($user->getId(), 'user');
                    $sql = <<<SQL
SELECT pa.`roleId`, pa.`access`
FROM `permission_access` pa
WHERE pa.`permissionId` = {$permissionId}
AND (pa.`roleId` IN ({$roleId})
OR pa.`roleId` = '{$userId}')
SQL;
                    // Table permission_access
                    $resultAccess = $dbAdapter->query($sql, $dbAdapter::QUERY_MODE_EXECUTE);

                    // Access to page
                    if ($resultAccess->count()) {
                        $this->permissionDenied = false;
                    }
                }
            }
        }
    }

	/**
	 * @todo: Implement more perfect structure
	 * @return mixed
	 */
	public function initRoles()
    {
        $this->roles = ArrayUtils::merge($this->getConfig()['acl'], $this->roles);

        $resultRolesArray = $this->getResultRolesArray();

		$sql = <<<SQL
SELECT p.`target`, pa.`roleId`, pa.`access`
FROM `permission_access` pa
LEFT JOIN `permission` p ON pa.`permissionId` = p.`id`
WHERE p.`entityId` = 0 AND p.`parent` = 0
SQL;
        $dbAdapter = $this->getDbAdapter();
		$results = $dbAdapter->query($sql, $dbAdapter::QUERY_MODE_EXECUTE);

		foreach ($results as $result) {
			// Parse roleId
			$assocDigit = self::parseStringAssocDigit($result['roleId']);
			if ($assocDigit['field'] == 'role' && isset($resultRolesArray[$assocDigit['id']])) {
				$this->roles[$resultRolesArray[$assocDigit['id']]['mnemo']][] = [
					'target' => $result['target'],
					'access' => $result['access'],
				];
			}
		}
	}

	private function getResultRolesArray() {
		static $resultRolesArray;

        if (!$resultRolesArray) {
            $dbAdapter = $this->getDbAdapter();
            // Table roles
			$resultRoles = $dbAdapter->query(
				'SELECT r.`id`, r.`mnemo`, r.`resource`FROM `role` r',
				$dbAdapter::QUERY_MODE_EXECUTE
			);

			foreach ($resultRoles as $result) {
				if ($result['resource'] == 'all') {
					$this->roles[$result['mnemo']][] = [
						'target' => $result['resource'],
						'access' => Acl::ACCESS_TOTAL,
					];
				} else {
                    $this->roles[$result['mnemo']][] = [
                        'target' => $result['resource'],
                        'access' => Acl::ACCESS_TOTAL,
                    ];
					$resultRolesArray[$result['id']] = $result;
				}
			}
		}

		return $resultRolesArray;
	}
}
