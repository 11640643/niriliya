<?php

return [

    //登录过期时间
    'expire_time' => 72 * 3600,

    'public_api_urls' => [
        '/api/api/config',
        '/api/api/logout',
        '/api/api/export',
    ],
    //modules
    'modules' => [
        'api'
    ],

    'courier_config' => [
        'customer' => '123123',
        'key' => '123123',
        'url' => 'http://poll.kuaidi100.com/poll/query.do',
    ],

    'sms_config' => [],
    'serve_name' => 2,
];

