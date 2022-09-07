<?php

return [

    //登录过期时间
    'expire_time' => 72 * 3600*24,

    'public_api_urls' => [
        '/api/api/config',
        '/api/api/logout',
        '/api/api/export',
        '/api/api/login',
    ],
    //modules
    'modules' => [
        'api'
    ],
    'courier_config' => [
        'customer' => 'BD2B549598CAF39B0670F7F978EB115A',
        'key' => 'xmYAOthN9188',
        'url' => 'http://poll.kuaidi100.com/poll/query.do',
    ],	

    'sms_config' => [],
    'serve_name' => 2,
    'jwt_secret' => '123456789qwertyuiop',
    'jwt_expire_time' => 72 * 3600,
];

