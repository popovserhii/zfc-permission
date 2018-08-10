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
use Popov\ZfcPermission\Acl\Acl;

return [
    'guest' => [
        ['target' => 'user/admin-login', 'access' => Acl::ACCESS_TOTAL],
        ['target' => 'user/admin-logout', 'access' => Acl::ACCESS_TOTAL],
        ['target' => 'user/admin-forgot-password', 'access' => Acl::ACCESS_TOTAL],
    ],
];