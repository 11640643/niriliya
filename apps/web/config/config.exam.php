<?php

return [
    //登录过期时间
    'expire_time' => 14 * 24 * 3600,

    'public_api_urls' => [
        '/api/api/config',
        '/api/api/qrcode',
        '/api/api/index',
        '/api/api/xc',
        '/api/flow/juliang',
        '/api/gdt/gdt',
        '/api/gdt1/gdt',
        '/api/flow/phenix',
    ],
    //modules
    'modules' => [
        'api'
    ],
    'gdt_config'=>[
        'client_id'=>'1110488482',
        'secret_key'=>'111',
        'user_action_set_id'=>'222',
        'access_token'=>'',
        'account_id'=>'',

    ],
    'gdt1_config'=>[
        'client_id'=>'1110488482',
        'secret_key'=>'111',
        'user_action_set_id'=>'222',
        'access_token'=>'',
        'account_id'=>'',

    ],
    'courier_config' => [
        'customer' => '123',
        'key' => '123',
        'url' => 'http://poll.kuaidi100.com/poll/query.do',
    ],
    'promotion_host1' => [

    ],
    'promotion_host2' => [

    ],
    'serve_name' => 2,
];
