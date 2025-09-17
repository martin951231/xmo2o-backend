<?php

/**
 * 文档地址：http://dev.erp.miaomaiyun.com/apidoc_v2/#apidoc-home-3
 *
 */
class Miaomaiyun
{
    private $appId = '';

    private $appSecret = '';

    public $params = [];

    private $gateway = 'http://erp.tceasemall.com';

    //private $gateway = 'http://dev.erp.miaomaiyun.com';

    //测试接口
    const TEST_INDEX = '/api_v2/test/index';

    //分类设置
    const CATEGORY_SET = '/api_v2/goods_set/category_set';

    //分类删除
    const CATEGORY_DEL = '/api_v2/goods_set/category_del';

    //获取分类
    const CATEGORY_GET = '/api_v2/goods_set/get_category';

    //商品设置
    const GOODS_SETS = '/api_v2/goods_set/goods_sets';

    //商品批量删除
    const GOODS_DEL = '/api_v2/goods_set/goods_del';

    //存货类型设置
    const SPEC_SET = '/api_v2/goods_set/spec_set';

    //存货类型属性设置 （规格）
    const SPEC_ATTR_SET = '/api_v2/goods_set/spec_attr_set';

    //存货类型属性值设置（规格值）
    const SPEC_ATTR_VAL_SET = '/api_v2/goods_set/spec_attr_val_set';

    //新增会员信息/会员注册
    const MEMBER_MK = '/api_v2/member/mk';

    //修改会员信息
    const MEMBER_UP = '/api_v2/member/up';

    //查询会员信息
    const GET_MEMBER_INFO = '/api_v2/member/get_member_info';

    //修改会员余额
    const SET_MEMBER_MONEY = '/api_v2/member/set_member_money';

    //获取会员等级
    const GET_MEMBER_GRADE = '/api_v2/member/get_member_grade';

    //修改会员积分余额
    const SET_MEMBER_POINTS = '/api_v2/member/set_member_points';

    //同步库存
    const BGD_ADD = '/api_v2/bgd/add';

    //审核库存变更单
    const BGD_SH = '/api_v2/bgd/sh';

    //获取全部商品信息
    const GET_GOODS_ALL ='/api_v2/goods/get_goods_all';
    //商品分类
    const GET_GOODS_CATEGORY ='/api_v3/category/xlist';
    //现金抵用券-会员券列表
    const GET_COUPON_LIST ='/app_coupon_offset/aapi_coupon_offset_member/member_coupon';
    //取消订单
    const CANCEL_ORDER = '/api_v2/mall_order/cancel_order';
    public function __construct()
    {
    }

    public function setConfig($appId, $appSecret, $gateway = '')
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->params['appid'] = $appId;
        $this->params['pos_num'] = 'Om7ZM9nw';//终端编号
        $this->gateway = $gateway ?: C('config.miaomaiyun_erp_gateway');
        return $this;
    }

    /**
     * 清空其他参数防止影响签名
     * @author: 张涛
     * @date: 2021/04/02
     */
    public function clearParam()
    {
        $params = $this->params;
        foreach ($params as $k => $v) {
            if ($k !== 'appid') unset($params[$k]);
        }
        $this->params = $params;
        return $this;
    }

    /**
     * 设置请求参数
     * @author: 张涛
     * @date: 2021/03/31
     */
    public function setParam($key, $value)
    {
        $this->params[$key] = $value;
        return $this;
    }

    public function setParams($params)
    {
        foreach ($params as $key => $value){
            $this->params[$key] = $value;
        }
        return $this;
    }
    
    /**
     * 调用接口
     * @param $api  接口地址
     * @author: 张涛
     * @date: 2021/03/31
     */
    public function run($api)
    {
        $this->params['sign'] = $this->getSign();
        import('ORG.Net.Http');
        $rs = ErpHttp::post($this->gateway . $api, $this->params);
        return $rs;
    }

    /**
     * 获取签名
     * @author: 张涛
     * @date: 2021/03/31
     */
    public function getSign()
    {
        $params = $this->params;
        $parameters = [];
        foreach ($params as $k => $v) {
            $parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        $string = $this->formatBizQueryParaMap($parameters);

        //签名步骤二：在string后加入KEY
        $string = $string . $this->appSecret;
        //签名步骤三：MD5加密
        $string = md5($string);
        //echo "【string3】 ".$String."</br>";
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    function formatBizQueryParaMap($paraMap)
    {
        ksort($paraMap);
        return urldecode(http_build_query($paraMap));
    }

}

class ErpHttp
{
    public static function post($url, $params, $header = [])
    {
        $rs = Http::curlPostOwnWithHeader($url, $params, $header);
        fdump_sql([$url, $params, $rs], 'MiaomaiyunErp_curl');
        return is_array($rs) ? $rs : json_decode($rs, true);
    }
}