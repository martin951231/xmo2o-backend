<?php
/**
 * 润雅定制 对接商盈通
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/5/28 16:21
 */

namespace app\common\model\service;

use app\common\model\db\UserShangyingtong;

class UserShangyingtongService
{
    public $userShangyingtongService = null;

    protected $runya_to_shangyingtong ;//是否开启对接商盈通
    protected $shangyingtong_url ;//对接商盈通接口地址
    protected $shangyingtong_appid ;//商盈通应用ID
    protected $syt_jgbh ;
    protected $cardSubCls ;//卡子类型号
    protected $cert_path ;//证书
    protected $cert_pwd ;//证书密码
    protected $shanghuhao ;//商家号

    public function __construct()
    {
        $this->runya_to_shangyingtong 	= cfg("runya_to_shangyingtong");
        $this->shangyingtong_url 		= cfg("shangyingtong_url");
        $this->shangyingtong_appid 		= cfg("shangyingtong_appid");
        $this->syt_jgbh					= cfg("shangyingtong_jgbh");//机构编号
        $this->cardSubCls				= cfg("shangyingtong_cardSubCls");
        $this->cert_path				= '.'.cfg("shangyingtong_cert");
        $this->cert_pwd					= cfg("shangyingtong_cert_pwd");
        $this->shanghuhao				= cfg("shangyingtong_shanghuhao");

        $this->userShangyingtongObj = new UserShangyingtong();
    }

    /**
     * 充值
     * User: chenxiang
     * Date: 2020/5/28 16:54
     * @param $uid
     * @param $money
     * @return bool
     */
    public function recharge($uid, $money) {
        if(empty($uid) || empty($money) || $this->runya_to_shangyingtong == '0' || !$this->shangyingtong_appid) return false;
        $where = array(
            'uid' => $uid,
            'card_id' => array('neq', ''),
            'errcode' => array('eq', '')
        );
        $now_user = $this->userShangyingtongObj->getUserData('card_id', $where, 'add_time asc');
        if(empty($now_user) || empty($now_user['card_id'])) return false;
        //业务参数
        $json = json_encode(array(
            'head' => array(
                'Ver' => '1.0',
                'App' => 'CARD',
                'SndCode' => $this->syt_jgbh,
                'RecCode' => '00000000',
                'MsgId' => $this->syt_jgbh.date('YmdHis').$this->milliseconds().rand(1000000,9999999),
                'TimeStamp' => date('YmdHis').$this->milliseconds()
            ),
            'body' => array(
                'cardId' => $now_user['card_id'],
                'depPtVal' => $money,
                'realPtVal' => $money,
                'depType'   => '02',
                'entryMode' => '012',
                'condCode' => '65',
                'currency' => '156'
            )
        ));

        $param = array(
            'app_id' => $this->shangyingtong_appid,
            'method' => 'gnete.sytbc.trade.TransTradeRpcService.recharge',//请求接口 接口服务.方法名
            'timestamp' => date('Y-m-d H:i:s'),
            'v' => '1.0.1',
            'sign_alg' => '0',
            'biz_content' => $json
        );

        $data = $this->getSignStr($param);
//待定...
//        import('ORG.Net.Http');
        $http = new Http();
        $return = Http::curlPost($this->shangyingtong_url, $data);

    }

    /**
     * 设置毫秒
     * User: chenxiang
     * Date: 2020/5/28 16:36
     * @param string $format
     * @param null $utimestamp
     * @return false|float
     */
    public function milliseconds($format = 'u', $utimestamp = null)
    {
        if (is_null($utimestamp)){
            $utimestamp = microtime(true);
        }
        $timestamp = floor($utimestamp);
        $milliseconds = round(($utimestamp - $timestamp) * 1000);//改这里的数值控制毫秒位数
        return $milliseconds;
    }

    /**
     * 获取sign串
     * User: chenxiang
     * Date: 2020/5/28 16:39
     * @param $param
     * @return string
     */
    public function getSignStr($param){
        if(empty($param)) return '';
        $str = [];
        foreach ($param as $key => $value) {
            $str[] = $key."=".$value;
        }
        $sign = $this->SHA1withRSA(implode('&', $str));
        $return = implode('&', $str)."&sign=".$sign;
        return $return;
    }

    /**
     * 消费
     * User: chenxiang
     * Date: 2020/5/28 20:52
     * @param $uid
     * @param $money
     * @return bool
     */
    public function consume($uid, $money){
        if(empty($uid) || empty($money) || $this->runya_to_shangyingtong == '0' || !$this->shangyingtong_appid) return false;
        $where = array(
            'uid' => $uid,
            'card_id' => array('neq', ''),
            'errcode' => array('eq', '')
        );

        $now_user = $this->userShangyingtongObj->getUserData('card_id', $where, 'add_time asc');
        if(empty($now_user) || empty($now_user['card_id'])) return false;
        //业务参数
        $json = json_encode(array(
            'head' => array(
                'Ver' => '1.0',
                'App' => 'CARD',
                'SndCode' => $this->syt_jgbh,
                'RecCode' => '00000000',
                'MsgId' => $this->syt_jgbh.date('YmdHis').$this->milliseconds().rand(1000000,9999999),
                'TimeStamp' => date('YmdHis').$this->milliseconds()
            ),
            'body' => array(
                'cardId' => $now_user['card_id'],
                'transAmt' => $money,
                'transType' => '10',
                'entryMode'   => '012',
                'condCode' => '65',
                'acpter' => $this->shanghuhao,
                'currency' => '156'
            )
        ));

        $param = array(
            'app_id' => $this->shangyingtong_appid,
            'method' => 'gnete.sytbc.trade.TransTradeRpcService.consume',//请求接口 接口服务.方法名
            'timestamp' => date('Y-m-d H:i:s'),
            'v' => '1.0.1',
            'sign_alg' => '0',
            'biz_content' => $json
        );

        $data = $this->getSignStr($param);
//        fdump_sql($data, "shangyingtong_consume_head");
        //待定...
        import('ORG.Net.Http');
        $http = new Http();
        $return = Http::curlPost($this->shangyingtong_url, $data);
//        fdump_sql($return, "shangyingtong_consume_return");
    }
}