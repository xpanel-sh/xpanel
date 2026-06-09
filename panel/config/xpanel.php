<?php

return [
    'routes' => [
        'admin_login_path' => trim(env('XPANEL_ADMIN_LOGIN_PATH', 'admin/login'), '/'),
        'client_login_path' => trim(env('XPANEL_CLIENT_LOGIN_PATH', 'login'), '/'),
        'admin_base_path' => trim(env('XPANEL_ADMIN_BASE_PATH', 'admin'), '/'),
    ],
];
