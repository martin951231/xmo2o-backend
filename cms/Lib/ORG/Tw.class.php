<?php

class Tw
{
    protected $key;

    const BRANCH_INFO = 'http://192.168.1.254:9000/tw/fuque/getbranchinfo';

    const GET_VIP_INFO = 'http://192.168.1.254:9000/tw/fuque/getvipinfo';

    const ORDER_AMOUNT = 'http://192.168.1.254:9000/tw/fuque/order_amount';

    const SAVE_ORDER = 'http://192.168.1.254:9000/tw/fuque/valid_order_amount';

    const FINISH_ORDER = 'http://192.168.1.254:9000/tw/fuque/approve_order';

    const REFUND_ORDER = 'http://192.168.1.254:9000/tw/fuque/return_order';

    const QUERY_STOCK = 'http://192.168.1.254:9000/tw/fuque/selectstock';

    const QUERY_ARRAY_STOCK = 'http://192.168.1.254:9000/tw/fuque/selectarrystock';  //批量查询商品库存

    const UPDATE_IMGS = 'http://192.168.1.254:9000/tw/fuque/upitemimage';  //福雀更新商品图片通知图维

    const VIP_SCORE_OPERATION =  'http://192.168.1.254:9000/tw/fuque/vipvalueoperation'; //会员卡积分变动

    const VIP_BALANCE_OPERATION =  'http://192.168.1.254:9000/tw/fuque/vipbalanceoperation'; //会员卡储值余额变动

    const CREATE_VIPINFO =  'http://192.168.1.254:9000/tw/fuque/suvipinfo'; //新增或者修改会员档案

    public function __construct()
    {
        $this->key = 'cgeyxmqt5075gqptwfpcxmhpgdkygtxr';
    }

    public function getBranchInfo($param)
    {
        $param['sign'] = $this->getSign($param);
        import('ORG.Net.Http');
        $rs = Http::curlPost(self::BRANCH_INFO, json_encode($param, JSON_UNESCAPED_UNICODE), 15, 'json');
        fdump_api([self::BRANCH_INFO, json_encode($param, JSON_UNESCAPED_UNICODE), $rs], 'tw/getBranchInfo', 1);

        //这里门店名匹配
        $store = D('Merchant_store')->where(['tw_shop_id' => $param['shop_id']])->find();
        if ($store['tw_branch_no'] !== '') {
            return $store['tw_branch_no'];
        }
        $branchNo = '';
        if ($rs['errorCode'] == 0) {
            foreach ($rs['result']['branchinfo'] as $v) {
                if ($v['branch_name'] == $store['name']) {
                    $branchNo = $v['branch_no'];
                    D('Merchant_store')->where(['store_id' => $store['store_id']])->save(['tw_branch_no' => $branchNo]);
                    break;
                }
            }
        }
        return $branchNo;
    }

    public function getVipInfo($param)
    {
        $param['sign'] = $this->getSign($param);
        import('ORG.Net.Http');
        $rs = Http::curlPost(self::GET_VIP_INFO, json_encode($param, JSON_UNESCAPED_UNICODE), 15, 'json');
        fdump_api(['请求url' => self::GET_VIP_INFO, '请求参数' => json_encode($param, JSON_UNESCAPED_UNICODE), '响应内容' => $rs], 'tw/getVipInfo', 1);
        if ($rs['errorCode'] == 0) {
            return $rs['result'];
        } else {
            return [];
        }
    }

    
    public function createVipInfo($param)
    {
        $param['sign'] = $this->getSign($param);
        import('ORG.Net.Http');
        $rs = Http::curlPost(self::CREATE_VIPINFO, json_encode($param, JSON_UNESCAPED_UNICODE), 15, 'json');
        fdump_api(['请求url' => self::GET_VIP_INFO, '请求参数' => json_encode($param, JSON_UNESCAPED_UNICODE), '响应内容' => $rs], 'tw/createVipInfo', 1);
        if ($rs['errorCode'] == 0) {
            return $rs['result'];
        } else {
            return [];
        }
    }

    public function saveOrder($param)
    {
        $param['branch_no'] = $this->getBranchInfo(['shop_id' => $param['shop_id']]);
        $param['sign'] = $this->getSign($param, ['products']);
        import('ORG.Net.Http');
        $rs = Http::curlPost(self::SAVE_ORDER, json_encode($param, JSON_UNESCAPED_UNICODE), 15, 'json');
        fdump_api(['请求url' => self::SAVE_ORDER, '请求参数' => json_encode($param, JSON_UNESCAPED_UNICODE), '响应内容' => $rs], 'tw/saveOrder', 1);
        if ($rs['errorCode'] == 0) {
            return $rs['result'];
        } else {
            return [];
        }
    }

    public function refundOrder($param)
    {
        $param['sign'] = $this->getSign($param, ['products']);
        import('ORG.Net.Http');
        $rs = Http::curlPost(self::REFUND_ORDER, json_encode($param, JSON_UNESCAPED_UNICODE), 15, 'json');
        fdump_api(['请求url' => self::REFUND_ORDER, '请求参数' => json_encode($param, JSON_UNESCAPED_UNICODE), '响应内容' => $rs], 'tw/refundOrder', 1);
        if ($rs['errorCode'] == 0) {
            return $rs['result'];
        } else {
            return [];
        }
    }

    public function finishOrder($param)
    {
        $param['sign'] = $this->getSign($param);
        import('ORG.Net.Http');
        $rs = Http::curlPost(self::FINISH_ORDER, json_encode($param, JSON_UNESCAPED_UNICODE), 15, 'json');
        fdump_api(['请求url' => self::FINISH_ORDER, '请求参数' => json_encode($param, JSON_UNESCAPED_UNICODE), '响应内容' => $rs], 'tw/finishOrder', 1);
        if ($rs['errorCode'] == 0) {
            return $rs['result'];
        } else {
            return [];
        }
    }

    public function queryStock($param)
    {
        $param['branch_no'] = $this->getBranchInfo(['shop_id' => $param['shop_id']]);
        $param['sign'] = $this->getSign($param);
        import('ORG.Net.Http');
        $rs = Http::curlPost(self::QUERY_STOCK, json_encode($param, JSON_UNESCAPED_UNICODE), 15, 'json');
        fdump_api(['请求url' => self::QUERY_STOCK, '请求参数' => json_encode($param, JSON_UNESCAPED_UNICODE), '响应内容' => $rs], 'tw/queryStock', 1);
        if ($rs['errorCode'] == 0) {
            return $rs['result'];
        } else {
            return [];
        }
    }

    public function queryArrayStock($param)
    {
        $param['branch_no'] = $this->getBranchInfo(['shop_id' => $param['shop_id']]);
        $param['sign'] = $this->getSign($param,['itemarry']);
        import('ORG.Net.Http');
        $rs = Http::curlPost(self::QUERY_ARRAY_STOCK, json_encode($param, JSON_UNESCAPED_UNICODE), 15, 'json');
        fdump_api(['请求url' => self::QUERY_ARRAY_STOCK, '请求参数' => json_encode($param, JSON_UNESCAPED_UNICODE), '响应内容' => $rs], 'tw/queryArrayStock', 1);
        if ($rs['errorCode'] == 0) {
            return $rs['result'];
        } else {
            return [];
        }
    }

    public function syncProductImage($param)
    {
        $param['sign'] = $this->getSign($param,['item_detail_image']);
        import('ORG.Net.Http');
        $rs = Http::curlPost(self::UPDATE_IMGS, json_encode($param, JSON_UNESCAPED_UNICODE), 15, 'json');
        fdump_api(['请求url' => self::UPDATE_IMGS, '请求参数' => json_encode($param, JSON_UNESCAPED_UNICODE), '响应内容' => $rs], 'tw/syncProductImage', 1);
        if ($rs['errorCode'] == 0) {
            return $rs['result'];
        } else {
            return [];
        }
    }

    public function vipScoreOperation($param)
    {
        $param['sign'] = $this->getSign($param);
        import('ORG.Net.Http');
        $rs = Http::curlPost(self::VIP_SCORE_OPERATION, json_encode($param, JSON_UNESCAPED_UNICODE), 15, 'json');
        fdump_api(['请求url' => self::VIP_SCORE_OPERATION, '请求参数' => json_encode($param, JSON_UNESCAPED_UNICODE), '响应内容' => $rs], 'tw/vipScoreOperation', 1);
        if ($rs['errorCode'] == 0) {
            return $rs['result'];
        } else {
            return [];
        }
    }

    public function vipBalanceOperation($param)
    {
        $param['sign'] = $this->getSign($param);
        import('ORG.Net.Http');
        $rs = Http::curlPost(self::VIP_BALANCE_OPERATION, json_encode($param, JSON_UNESCAPED_UNICODE), 15, 'json');
        fdump_api(['请求url' => self::VIP_BALANCE_OPERATION, '请求参数' => json_encode($param, JSON_UNESCAPED_UNICODE), '响应内容' => $rs], 'tw/vipBalanceOperation', 1);
        if ($rs['errorCode'] == 0) {
            return $rs['result'];
        } else {
            return [];
        }
    }


    public function getSign($param, $notInSign = [])
    {
        if ($notInSign) {
            foreach ($notInSign as $v) {
                unset($param[$v]);
            }
        }

        ksort($param);
        $str = '';
        foreach ($param as $key => $val) {
            $str .= '&' . $key . '=' . $val;
        }
        $str .= '&key=' . $this->key;
        if ($notInSign) {
            fdump_api(["签名字符串" => ltrim($str, '&')], 'tw/getSign', 1);
        }
        return strtoupper(md5(ltrim($str, '&')));
    }
}
