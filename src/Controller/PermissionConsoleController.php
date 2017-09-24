<?php
namespace Popov\ZfcPermission\Controller;

use Popov\ZfcPermission\Service\PermissionService;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Console\Prompt;
use Zend\Console\Exception\RuntimeException as ConsoleException;
use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Http\Request as HttpRequest;
use Zend\Mvc\Router\Http\RouteMatch;
use Zend\Mvc\Router\Http\TreeRouteStack;
use Popov\Entity\Controller\Plugin\EntityPlugin;
use Popov\Entity\Controller\Plugin\ModulePlugin;
use Popov\ZfcPermission\Model\Permission;

/**
 * @method EntityPlugin entity($context = null)
 * @method ModulePlugin module($context = null)
 */
class PermissionConsoleController extends AbstractActionController
{
    /** @var Console */
    protected $console;

    /** @var TreeRouteStack */
    protected $treeRouteStack;

    /** @var PermissionService */
    protected $permissionService;

    public function __construct(Console $console, TreeRouteStack $treeRouteStack, PermissionService $permissionService)
    {
        $this->console = $console;
        $this->treeRouteStack = $treeRouteStack;
        $this->permissionService = $permissionService;
    }

    public function getPermissionService()
    {
        return $this->permissionService;
    }

    public function promptAction()
    {
        $request = $this->getRequest();

        if (!$target = $request->getParam('target')) {
            $target = Prompt\Line::prompt(
                'Enter target: ',
                false,
                PHP_INT_MAX
            );
        }

        if (!$type = $request->getParam('type')) {
            $options = [
                'action',
                'controller',
                'settings',
                'fields',
            ];

            $typeIndex = (int) Prompt\Select::prompt(
                'Select permission type: ',
                $options,
                true,
                true
            );

            $type = $options[$typeIndex];
        }

        if ('action' !== $type) {
            throw new ConsoleException(sprintf('Support "%s" type not implemented yet.', $type));
        }

        $request = new HttpRequest();
        $request->setMethod(HttpRequest::METHOD_GET);
        //$request->setUri('http://storage.dev/importer/import-lite/type/agere-serial/source/getSNForCloud');
        $request->setUri($target);

        /** @var RouteMatch $routeMatch */
        if (!$routeMatch = $this->treeRouteStack->match($request)) {
            $this->console->write('Sorry, but cannot recognize target path!');

            return;
        }

        $permissionService = $this->getPermissionService();
        /** @var Permission $permission */
        $permission = $permissionService->getRepository(Permission::class)->findBy([
            'type' => $type,
            'target' => $target = trim($request->getUri()->getPath(), '/'),
        ]);

        if ($permission) {
            $this->console->write('Record already exists in database!');

            return;
        }

        $permission = $permissionService->getObjectModel();
        $module = $this->module()->getBy($routeMatch->getParam('controller'), 'mnemo');

        $permission->setTarget($target)
            ->setType($type)
            ->setModule($module->getName())
            ->setEntityId(0)
            ->setParent(0)
            ->setRequired(0)
        ;

        $om = $permissionService->getObjectManager();
        $om->persist($permission);
        $om->flush();

        $this->console->write("Record successfully added to database!\n");
        $this->console->write('Open Role Settings for set relative permissions.');
    }
}