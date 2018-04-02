<?php
/**
 * The MIT License (MIT)
 * Copyright (c) 2018 Serhii Popov
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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\View\Model\ViewModel;

class PermissionMiddleware implements MiddlewareInterface
{

    /**
     * @var PermissionHelper
     */
    protected $permissionHelper;

    /**
     * @var TemplateRendererInterface
     */
    protected $renderer;

    /**
     * @var array
     */
    protected $config;

    public function __construct(
        PermissionHelper $permissionHelper,
        TemplateRendererInterface $renderer,
        array $config = null
    )
    {
        $this->permissionHelper = $permissionHelper;
        $this->renderer = $renderer;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->permissionHelper->init();

        if (!$this->permissionHelper->checkPermission()) {
            //$view = (new ViewModel())->setTemplate('admin-permission::denied');
            //return $handler->handle($request->withAttribute(ViewModel::class, $view));

            $content = $this->renderer->render('admin-permission::denied');

            return new HtmlResponse($content);
        }

        return $handler->handle($request);

    }
}