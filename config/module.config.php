<?php
namespace Popov\ZfcPermission;

return [

    'acl' => require __DIR__ . '/acl.config.php',

    'console' => [
        'router' => [
            'routes' => [
                'permission-routes' => [
                    'type' => 'simple',
                    'options' => [
                        'route' => 'permission <action> [--prompt] [--type=] [--target=]',
                        'defaults' => [
                            'controller' => 'permission-console',
                            'action' => 'prompt'
                        ]
                    ]
                ]
            ]
        ]
    ],

    'controllers' => [
        'aliases' => [
            'permission-console' => Controller\PermissionConsoleController::class,
        ],
        'invokables' => [
            'permission' => Controller\PermissionController::class,
            'permission-access' => Controller\PermissionAccessController::class,
        ],
        'factories' => [
            Controller\PermissionConsoleController::class => Controller\Factory\PermissionConsoleControllerFactory::class
        ]
    ],

    'actions' => [
        'permission' => __NAMESPACE__ . '\Action'
    ],

    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern' => '%s.mo',
                'text_domain' => __NAMESPACE__,
            ],
        ],
    ],

    // mvc
    'view_manager' => [
        'prefix_template_path_stack' => [
            'permission::' => __DIR__ . '/../view/permission',
        ],
    ],

    // middleware
    'templates' => [
        'paths' => [
            'admin-permission'  => [__DIR__ . '/../view/admin/permission'],
        ],
    ],

    'dependencies' => [
        'aliases' => [
            'Acl' => Acl\Acl::class,
            'PermissionService' => Service\PermissionService::class,
            'PermissionAccessService' => Service\PermissionAccessService::class,
            'PermissionPageBindService' => Service\PermissionPageBindService::class,
            'PermissionSettingsPagesService' => Service\PermissionSettingsPagesService::class,
        ],
        'invokables' => [
            Acl\Acl::class => Acl\Acl::class,
        ],
        'factories' => [
            PermissionHelper::class => Factory\PermissionHelperFactory::class,
            Service\PermissionService::class => Service\Factory\PermissionServiceFactory::class,
            Service\PermissionAccessService::class => Service\Factory\PermissionAccessServiceFactory::class,
            Service\PermissionPageBindService::class => Service\Factory\PermissionPageBindServiceFactory::class,
            Service\PermissionSettingsPagesService::class => Service\Factory\PermissionSettingsPagesServiceFactory::class,
        ],
    ],

    'view_helpers' => [
        'invokables' => [
            'permissionPageBind' => View\Helper\PermissionPageBind::class,
        ],
        'factories' => [
            'permission' => View\Helper\Factory\PermissionHelperFactory::class,
            'permissionFields' => View\Helper\Factory\PermissionFieldsFactory::class,
        ],
    ],

    // Doctrine config
    'doctrine' => [
        'driver' => [
            'orm_default' => [
                'drivers' => [
                    __NAMESPACE__ . '\Model' => __NAMESPACE__ . '_driver',
                ],
            ],
            __NAMESPACE__ . '_driver' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\YamlDriver',
                'cache' => 'array',
                'extension' => '.dcm.yml',
                'paths' => [__DIR__ . '/yaml'],
            ],
        ],
    ],
];
