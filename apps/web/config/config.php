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
        '/api/flow/shuabao',
        '/api/flow/qutoutiao',
        '/api/flow/qutoutiao000',
        '/api/flow/kuaishou',
        '/api/flow/dongfang',
        '/api/flow/phenix',
        '/api/downlog/stat',
        '/api/api/onepaytransfernotice',
        '/api/api/onepaypaymentnotice',
        ],
    //modules
    'modules' => [
        'api'
    ],
    'gdt_config'=>[
    'client_id'=>'1111032270',
        'secret_key'=>'b65dd97dcf7799d331a5c3dbe08eb3bb',
        'user_action_set_id'=>'1111059312',
        'access_token'=>'264848bdc6f78517db542314bc5d4895',
        'account_id'=>'17803728',
    ],
    'gdt1_config'=>[
        'client_id'=>'1111032270',
        'secret_key'=>'b65dd97dcf7799d331a5c3dbe08eb3bb',
        'user_action_set_id'=>'1111015446',
        'access_token'=>'4729d8173f63c0a6ac058e56e9b62a2f',
        'account_id'=>'17776188',

    ],
    
    'courier_config' => [
        'customer' => 'BD2B549598CAF39B0670F7F978EB115A',
        'key' => 'xmYAOthN9188',
        'url' => 'http://poll.kuaidi100.com/poll/query.do',
    ],
    'promotion_host1' => [
        'www.muyunkj.com',    
        'muyunkj.com',    
    ],
    'promotion_host2' => [
    'www.zwyhr.com',
    'zwyhr.com',
    ],
    'promotion_host3' => [
    'www.tizantec.com',
    'tizantec.com',
    ],
    'serve_name' => 2,
];
