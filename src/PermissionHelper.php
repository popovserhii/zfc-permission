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
use Zend\Expressive\Helper\UrlHelper;
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
//use Popov\Agere\String\StringUtils as AgereString;
use Popov\ZfcUser\Controller\Plugin\AuthService as AuthPlugin;
use Popov\ZfcUser\Acl\Acl;
use Zend\Stdlib\Request;

/**
 * Class Authentication
 *
 * @package Popov\ZfcUser\Event
 */
class PermissionHelper
{
    use ConfigAwareTrait;

    protected $permissionDenied = false;

    /** @var AuthenticationService */
    protected $authService;

    /** @var Request */
    protected $request;

    /** @var AuthPlugin */
    protected $_userAuth = null;

    /** @var Acl */
    protected $acl = null;

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

    public function __construct(CurrentHelper $currentHelper, SimplerHelper $simplerHelper, UrlHelper $urlHelper)
    {
        $this->currentHelper = $currentHelper;
        $this->simpleHelper = $currentHelper;
        $this->urlHelper = $urlHelper;
    }

    public function setAuthService($authService)
    {
        $this->authService = $authService;
    }

    public function getAuthService()
    {
        return $this->authService;
    }

    public function setRequest($request)
    {
        $this->request = $request;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function setUsePermission($usePermission)
    {
        $this->usePermission = $usePermission;

        return $this;
    }

    /**
     * Sets Authentication Plugin
     *
     * @param AuthPlugin $userAuthenticationPlugin
     * @return $this
     */
    public function setUserAuthenticationPlugin(AuthPlugin $userAuthenticationPlugin)
    {
        $this->_userAuth = $userAuthenticationPlugin;

        return $this;
    }

    /**
     * Gets Authentication Plugin
     *
     * @return Authentication
     */
    public function getUserAuthenticationPlugin()
    {
        return $this->_userAuth;
    }

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
            $this->acl = new Acl([]);
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

    /*public function setup()
    {
        $this->init();
        $this->mvcPreDispatch();

        /*$sharedEvents->attach(\Zend\Mvc\Controller\AbstractActionController::class, 'dispatch', [
            $auth,
            'mvcPreDispatch',
        ], 1000);
    }*/

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
                //\Zend\Debug\Debug::dump([$resource['target'], /*$this->roles, __METHOD__*/]);
                if (!$this->acl->hasResource($resource['target'])) {
					$this->acl->addResource(new \Zend\Permissions\Acl\Resource\GenericResource($resource['target']));
				}
				if ($resource['access'] == $this->denyDefault) {
					$this->acl->deny($role, $resource['target'], $resource['access']);
				} else {
					$this->acl->allow($role, $resource['target'], $resource['access']);
				}
			}
		}
	}

    public function checkPermission(/*$event*/)
    {
        //\Zend\Debug\Debug::dump([$this->roles, spl_object_hash($this), __METHOD__]);
        //$params = $event->getRouteMatch()->getParams();
        //$params = $this->currentHelper->currentRouteParams();
        // Access to page
        $this->checkGeneralPermission(/*$event*/);
        $this->checkItemPermission(/*$event, $params*/); // here set $permissionDenied

        return $this->permissionDenied;
    }

	/**
	 * preDispatch Event Handler
	 *
	 * @param MvcEvent $event
     * @return void
	 * @throws \Exception
	 */
    public function checkGeneralPermission(/*MvcEvent $event*/) {
        static $defaultResource;

        #$viewModel = $event->getViewModel();

        $roleMnemos = [Acl::DEFAULT_ROLE];
        $access = Acl::getAccess();
        $accessTotal = Acl::getAccessTotal();

        $userPlugin = $this->getAuthService();
        /** @var UserPlugin $userPlugin */
        if (($userPlugin->hasIdentity()) && ($user = $userPlugin->getIdentity()) && $user->getId()) {
            $roleMnemos = [];
            foreach ($user->getRoles() as $role) {
                $roleMnemos[] = $role->getMnemo();
            }

            //if (!$userPlugin->isAdmin()) {
            if (!in_array('admin', $roleMnemos)) {
                // Update expire login
                $sessionAuth = new SessionContainer('Zend_Auth');
                $sessionAuth->setExpirationSeconds(3600); // 60 minutes
            }

            /*if (!$defaultResource) {
                // Set default resource
                $defaultResource = ['files/get'];
                foreach ($defaultResource as $target) {
                    $this->acl->addResource(new GenericResource($target));
                    $this->acl->allow($roleMnemos, $target, Acl::getAccessTotal());
                }
            }*/
        }

        //$hasResource = $this->acl->hasResource('all');
        $isAllowed = $this->acl->hasResource('all')
            ? $this->acl->isAllowed($roleMnemos, 'all', $accessTotal)
            : false;
        $allowed = [$isAllowed];

        // Allowed session
        if (isset($_SESSION['location'])) {
            $target = $_SESSION['location']['resource'] . '/' . $_SESSION['location']['action'];
            // Allowed
            if ($this->acl->hasResource($target)) {
                $allowed[] = $this->acl->isAllowed($roleMnemos, $target, $accessTotal);
                $allowed[] = $this->acl->isAllowed($roleMnemos, $target, $access['write']);
                $allowed[] = $this->acl->isAllowed($roleMnemos, $target, $access['read']);
            }

            if (in_array(true, $allowed)) {
                $routeName = 'default';
                $dataUrl = [
                    'resource' => $_SESSION['location']['resource'],
                    'action' => $_SESSION['location']['action'],
                ];
                if (isset($_SESSION['location']['id'])) {
                    $routeName = 'default/id';
                    $dataUrl['id'] = $_SESSION['location']['id'];
                }
                //$url = $this->urlHelper->generate($dataUrl, ['name' => $routeName]);
                //$url = $this->urlHelper->generate($routeName, $dataUrl);
                $url = ['name' => $routeName, 'params' => $dataUrl];


                /*$response = $event->getResponse();
                $response->getHeaders()->addHeaderLine('Location', $url);
                $response->setStatusCode(302);
                $response->sendHeaders();
                unset($_SESSION['location']);
                exit;*/

                $this->redirect = $url;

                return true;
            }
        }

        // Resource
        //$routeMatch = $event->getRouteMatch();
        //$routeMatch = $this->currentHelper->currentRoute();
        $resource = $this->currentHelper->currentResource();
        $action = $this->currentHelper->currentAction();
        $target = $resource . '/' . $action;



        /*$targetFull = $event->getRouter()->assemble($routeMatch->getParams(), [
            'name' => $routeMatch->getMatchedRouteName()
        ]);*/
        $targetFull = $this->urlHelper->generate(
            $this->currentHelper->currentRouteName(),
            $this->currentHelper->currentRouteParams()
        );

        // Allowed
        if ($this->acl->hasAccessByRoles($roleMnemos, $target)
            || $this->acl->hasAccessByRoles($roleMnemos, $targetFull)
        ) {
            return;
        }

        if ($userPlugin->hasIdentity()) {
            //$event->stopPropagation(true); // very important string
            //$viewModel->permissionDenied = false;
            $this->permissionDenied = true;

            return;
        } else {
            $_SESSION['location'] = $this->currentHelper->currentRouteParams();
            /*$url = $event->getRouter()->assemble([
                'controller' => 'user',
                'action' => 'login',
            ], ['name' => 'default']);*/
            /*$url = $this->urlHelper->generate('admin/default', [
                'resource' => 'user',
                'action' => 'login',
            ]);*/
            $url = [
                'route' => 'admin/default',
                'params' => [
                    'resource' => 'user',
                    'action' => 'login',
                ],
            ];
        }

        if ($url) {
            /*$response = $event->getResponse();
            $response->getHeaders()->addHeaderLine('Location', $url);
            $response->setStatusCode(302);
            $response->sendHeaders();
            exit('Probably you enable showing DEPRECATED error messages
                and header already has been send through ServiceLocatorAwareInterface trigger_error');*/

            $this->redirect = $url;
        }
    }

    /**
     * Check if user has access to page with ID
     *
     * @return bool
     */
    public function checkItemPermission(/*MvcEvent $e, $params*/)
    {
        static $accessDefault = 6;

        //if (!$this->usePermission) {
        //    return;
        //}

        $params = $this->currentHelper->currentRouteParams();

        //$sm = $e->getApplication()->getServiceManager();
        $dbAdapter = $this->getDbAdapter();
        $where = '';

        //$simpler = $sm->get('ControllerPluginManager')->get('simpler');
        $user = $this->getAuthService()->getIdentity();
        //$viewModel = $e->getViewModel();


        // Acl class
        $acl = $this->getAcl();
        $role = $user ? $user->getRoles()->first() : false;
        if ($role && $role->getMnemo() && !$acl->isAllowed($role->getMnemo(), 'all', $accessDefault)) {
            // Where
            if (isset($params['id']) && $params['id'] > 0) {
                $where = "(p.`target` = '{$params['resource']}/{$params['action']}/{$params['id']}'
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

                    if (!$resultAccess->count()) {
                        $this->permissionDenied = true;
                        //return false;
                    }
                }
            }
        }
    }

	/**
	 * @todo: Implement more perfect structure
	 * @param $dbAdapter
	 * @param $usePermission
	 * @return mixed
	 */
	public function initRoles($usePermission = false) {
        $this->roles = ArrayUtils::merge($this->getConfig()['acl'], $this->roles);

        $resultRolesArray = $this->getResultRolesArray();

        // is permission module enabled
        #if (!$usePermission) {
        #    return $this->roles;
        #}

		$sql = <<<SQL
SELECT p.`target`, pa.`roleId`, pa.`access`
FROM `permission_access` pa
LEFT JOIN `permission` p ON pa.`permissionId` = p.`id`
WHERE p.`entityId` = 0 AND p.`parent` = 0
SQL;
        $dbAdapter = $this->getDbAdapter();
		$results = $dbAdapter->query($sql, $dbAdapter::QUERY_MODE_EXECUTE);

		//\Zend\Debug\Debug::dump($resultRolesArray); die(__METHOD__);

		foreach ($results as $result) {
			// Parse roleId
			$assocDigit = self::parseStringAssocDigit($result['roleId']);
			//\Zend\Debug\Debug::dump($assocDigit);

			if ($assocDigit['field'] == 'role' && isset($resultRolesArray[$assocDigit['id']])) {
				$this->roles[$resultRolesArray[$assocDigit['id']]['mnemo']][] = [
					'target' => $result['target'],
					'access' => $result['access'],
				];
			}
		}
		//die(__METHOD__);

		//return $this->roles;
	}

	private function getResultRolesArray() {
		static $resultRolesArray;
		static $accessDefault = Acl::ACCESS_TOTAL;

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
						'access' => $accessDefault,
					];
				} else {
                    $this->roles[$result['mnemo']][] = [
                        'target' => $result['resource'],
                        'access' => $accessDefault,
                    ];
					$resultRolesArray[$result['id']] = $result;
				}
			}
		}

		return $resultRolesArray;
	}


}