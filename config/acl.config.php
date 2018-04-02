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
 * @package Popov_ZfcUser
 * @author Serhii Popov <popow.sergiy@gmail.com>
 * @license https://opensource.org/licenses/MIT The MIT License (MIT)
 */
use Popov\ZfcUser\Acl\Acl;

return [
    'guest' => [
        //['target' => 'inquiry/form', 'access' => Acl::ACCESS_TOTAL],
        //['target' => 'inquiry/thanks', 'access' => Acl::ACCESS_TOTAL],

        ['target' => 'user/login', 'access' => Acl::ACCESS_TOTAL],
        ['target' => 'user/logout', 'access' => Acl::ACCESS_TOTAL],
        ['target' => 'user/forgot-password', 'access' => Acl::ACCESS_TOTAL],
    ],
];