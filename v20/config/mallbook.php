<?php

return [
    // api接口地址
    # pay_url mallbook 生产环境地址
    'apiUrl'   => 'https://cloudpay.mallbook.cn/api',
    # pay_url mallbook 测试环境地址
//    'apiUrl'   => 'https://uat.mallbook.cn/api',
    // 平台商户编号
    'merchantNo'   => '',
    // 渠道类型
    'channelType' => 'HF',
    // 接口版本号
    'version' => '1.0.0',

    // 商户平台私钥文件地址
//    'private_key_file' => cfg('mallbook_my_private_rsa_key'),
//    // 商户平台公钥文件地址
//    'public_key_file' => cfg('mallbook_my_public_rsa_key'),
//    // mallbook公钥文件地址
//    'mallbook_key_file' => cfg('mallbook_my_rsa_public'),

    # mall_book_public_key mallbook测试环境公钥 不需要替换
    'mall_book_public_key' => '',
    # mall_book_public_key mallbook生产环境公钥  不需要替换
    #'mall_book_public_key' => '',
];
