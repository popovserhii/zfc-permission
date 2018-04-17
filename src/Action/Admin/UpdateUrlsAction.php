<?php

namespace Popov\ZfcPermission\Action\Admin;

use Popov\ZfcCore\Filter\Translit;
use Popov\ZfcPermission\Controller\PermissionAccessController;
use Popov\ZfcPermission\Service\PermissionService;
use Popov\ZfcRole\Service\RoleService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Fig\Http\Message\RequestMethodInterface;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Expressive\Router\RouteResult;
use Zend\Expressive\Helper\UrlHelper;
use Zend\View\Model\ViewModel;
use Popov\ZfcForm\FormElementManager;
use Popov\ZfcUser\Form\UserForm;
use Popov\ZfcUser\Service\UserService;
use Popov\ZfcUser\Model\User;

use Popov\ZfcRole\Form\RoleForm;


class UpdateUrlsAction implements MiddlewareInterface, RequestMethodInterface
{
    /** @var UserService */
    protected $permissionService;

    /**
     * @var PermissionAccessController
     */
    protected $permissionAccessController;

    /** @var FormElementManager */
    protected $config;

    /** @var UrlHelper */
    protected $urlHelper;

    public function __construct(
        PermissionService $permissionService,
        UrlHelper $urlHelper,
        array $config
    ) {
        $this->permissionService = $permissionService;
        $this->config = $config;
        $this->urlHelper = $urlHelper;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //$route = $request->getAttribute(RouteResult::class);
        #$routesToControllers = $service->routeToControllerNormalization($this->config['controllers']); // for MVC

        $translate = $this->permissionService->runMiddleware($this->config['actions']);
        // Write to file
        $file = __DIR__ . '/../../../language/translate.phtml';
        $fileContent = file($file);
        $fileContent = array_unique(array_merge($fileContent, $translate));
        file_put_contents($file, implode("\n", $fileContent));
        // Update Module Permission
        #$this->permissionService->updatePermission(__CLASS__);
        $this->permissionService->updateSettings();

        $view = new ViewModel([
        ]);

        return $handler->handle($request->withAttribute(ViewModel::class, $view));
    }
}