<?php

class Im_userModel extends Model
{
    const PREFIX_STORE = 'store';

    const PREFIX_USER = 'user';

    const PREFIX_PLAT = 'plat';

    const PREFIX_WECHAT = 'wechat';

    const PREFIX_VILLAGE = 'village';

    const PREFIX_VILLAGE_USER = 'villageBind';

    const PREFIX_DELIVER = 'deliver';
    
    const PREFIX_HR = 'hr';

    /**
     * 检查账号前缀
     * @param $user
     */
    public static function checkPrefix($user)
    {
        $prefix = self::getUserPrefix($user);
        return in_array($prefix, array(self::PREFIX_STORE, self::PREFIX_USER, self::PREFIX_PLAT, self::PREFIX_WECHAT, self::PREFIX_VILLAGE, self::PREFIX_VILLAGE_USER, self::PREFIX_HR, self::PREFIX_DELIVER));
    }

    /**
     * 检测多个账号前缀
     * @param $users
     */
    public static function checkPrefixMulti(array $users)
    {
        $rs = true;
        foreach ($users as $u) {
            if (!self::checkPrefix($u)) {
                $rs = false;
                break;
            }
        }
        return $rs;
    }

    public static function getUserPrefix($username)
    {
        $split = explode('_', $username);
        return isset($split[0]) ? $split[0] : '';
    }

    /**
     * 用户名格式 user/store/merchant_{记录id}_其他   前两段为用户标识uuid
     * @param $username
     *
     */
    public static function getUUIDByUsername($username)
    {
        $split = explode('_', $username);
        return isset($split[0]) ? $split[0] . '_' . $split[1] : '';
    }

    public static function getUidAndPrefix($username)
    {
        $split = explode('_', $username);
        return array($split[0], $split[1]);
    }

    public function getUserBaseInfo($username)
    {
        $split = explode('_', $username);
        $prefix = $split[0];
        $uuid = intval($split[1]);
        if ($prefix == self::PREFIX_USER) {
            //用户
            $user = M('User')
                ->where(array('uid' => $uuid))
                ->field("nickname,avatar")
                ->find();
        } else if ($prefix == self::PREFIX_STORE) {
            //店铺客服
			$tmp_store = M(_view('Merchant_store'))->where(array('store_id'=>$uuid))->find();
			$user = [];
			$user['nickname'] = $tmp_store['name'];
			$user['avatar'] = $tmp_store['logo'];
        } else if ($prefix == self::PREFIX_PLAT) {
            //平台
            $user['nickname'] = M('Config')->where(array('name' => 'plat_kefu_alias'))->getField('value');
            $user['avatar'] = M('Config')->where(array('name' => 'wechat_share_img'))->getField('value');
        }else if($prefix == self::PREFIX_WECHAT){
            //商户微信公众号用户
            $user = M('Merchant_wechat_user')->find($uuid);
        }else if($prefix == self::PREFIX_VILLAGE){
            //社区
            $user = M('House_village')->where(['village_id'=>$uuid])->field('village_name AS nickname,village_logo AS avatar')->find();
        } else if ($prefix == self::PREFIX_VILLAGE_USER) {
            //业主
            $user = D('House_village_user_bind')->alias('b')
                ->join(C('DB_PREFIX') . 'user AS u ON u.uid = b.uid')
                ->where(['b.pigcms_id' => $uuid])
                ->field('u.nickname,u.avatar')->find();
        } else if ($prefix == self::PREFIX_DELIVER) {
            //配送员
            $user = D('Deliver_user')->where(['uid' => $uuid])
                ->field('name AS nickname')
                ->find();
            $user && $user['avatar'] = '/static/im/icon/deliver_icon.png';
        } else if ($prefix == self::PREFIX_HR) {
            //招聘者
            $user = D('New_recruit_hr')->alias('hr')->join(C('DB_PREFIX') . 'user AS u ON hr.uid=u.uid')
                ->field('concat(hr.first_name,hr.last_name) AS nickname,u.avatar')
                ->where(['hr.uid' => $uuid])
                ->find();
        }
        return isset($user) ? $user : array();
    }

    public function getUserDetailInfo($username, $from, $merId = 0)
    {
        $info = $this->where(array('username' => $username))->field('username,nickname,avatar')->find();
        list($prefix, $uuid) = Im_userModel::getUidAndPrefix($username);
        if ($prefix == self::PREFIX_USER) {
            //客户角色获取更多信息
            $uerinfo = M('User')->where(array('uid' => $uuid))
                ->field('uid,real_name,phone,FROM_UNIXTIME(add_time,"' . L_('X1年X2月X3日', ['X1'=>'%Y', 'X2'=>'%m', 'X3'=>'%d']) . '") AS register_date,now_money,score_count')
                ->find();
            $statics = [];
            if($merId >= 0){
                //获取用户累计消费金额、累计下单数统计
                $statics = $this->getOrderCountByUid($uuid,$merId);
            }
            $info = array_merge($info, $uerinfo, $statics);
        }else if($prefix == self::PREFIX_STORE){
            $tmp_store = M(_view('Merchant_store'))->where(array('store_id' => $uuid))->find();
            if ($tmp_store['phone']) {
                $info['phone'] = explode(' ',$tmp_store['phone'])[0];
            }
            if (empty($info) || empty($info['nickname']) || empty($info['avatar']) || C('config.open_multilingual')) {
                $info = [];
                $info['nickname'] = $tmp_store['name'];
                $info['avatar'] = $tmp_store['logo'];
            }
            $info['store_index'] = get_shop_url($uuid,$from);
        }else if($prefix == self::PREFIX_PLAT){
            //平台客服
            $info['nickname'] = M('Config')->where(array('name' => 'plat_kefu_alias'))->getField('value');
            $info['avatar'] = M('Config')->where(array('name' => 'wechat_share_img'))->getField('value');
        }else if($prefix == self::PREFIX_WECHAT){
            //商家微信公众号用户
            $info = M('Merchant_wechat_user')->field('nickname,avatar')->find($uuid);
        }else if($prefix == self::PREFIX_VILLAGE){
            //社区客服
            $info = M('House_village')->where(['village_id'=>$uuid])->field('village_name AS nickname,village_logo AS avatar')->find();
        }else if($prefix == self::PREFIX_VILLAGE_USER){
            //社区业主
            $info = D('House_village_user_bind')->alias('b')
                ->join(C('DB_PREFIX') . 'user AS u ON u.uid = b.uid')
                ->where(['b.pigcms_id' => $uuid])
                ->field('u.nickname,u.avatar')->find();
        } else if ($prefix == self::PREFIX_DELIVER) {
            //配送员
            $info = D('Deliver_user')->where(['uid' => $uuid])
                ->field('name AS nickname,phone')
                ->find();
            $info && $info['avatar'] = C('config.site_url') . '/static/im/icon/deliver_icon.png';
        }else if ($prefix == self::PREFIX_HR) {
            //招聘者
            $info = D('New_recruit_hr')->alias('hr')->join(C('DB_PREFIX') . 'user AS u ON hr.uid=u.uid')
                ->join(C('DB_PREFIX') . 'merchant AS m ON m.mer_id=hr.mer_id')
                ->join(C('DB_PREFIX') . 'new_recruit_company as com ON hr.mer_id =com.mer_id')
                ->field('concat(hr.first_name,hr.last_name) AS nickname,u.avatar,m.logo AS company_logo,com.name AS company_name,com.short_name AS company_short_name')
                ->where(['hr.uid' => $uuid])
                ->find();
            $info['company_logo'] = $info['company_logo'] ? replace_file_domain($info['company_logo']) : '';
        }else{
            return [];
        }
        $info['avatar'] && $info['avatar'] = replace_file_domain($info['avatar']);
        $info['role'] = $prefix;
        return $info;
    }

    public function getOrderCountByUid($uid, $merid)
    {
        $totalMoeny = 0;
        $totalCount = 0;
        $lastOrderTime = 0;
        $where = ['uid' => $uid, 'paid' => 1];
        if ($merid > 0) {
            $where['mer_id'] = $merid;
        }
        //团购订单
        $groupOrder = M('Group_order')->where($where)->field('sum(`total_money`) AS totalMoney,count(`order_id`) AS totalCount,max(`add_time`) AS createTime')->find();
        $totalMoeny += round($groupOrder['totalMoney'], 2);
        $totalCount += intval($groupOrder['totalCount']);
        $lastOrderTime < $groupOrder['createTime'] && $lastOrderTime = $groupOrder['createTime'];
        //快店、商城
        $shopOrder = M('Shop_order')->where($where)->field('sum(`price`) AS totalMoney,count(`order_id`) AS totalCount,max(`create_time`) AS createTime')->find();
        $totalMoeny += round($shopOrder['totalMoney'], 2);
        $totalCount += intval($shopOrder['totalCount']);
        $lastOrderTime < $shopOrder['createTime'] && $lastOrderTime = $shopOrder['createTime'];

        //新版商城
        $mallWhere = ['uid'=>$uid,'pay_time'=>['gt',0]];
        $merid && $mallWhere['mer_id'] = $merid;
        $mallOrder = M('Mall_order')->where($mallWhere)->field('sum(`money_total`) AS totalMoney,count(`order_id`) AS totalCount,max(`create_time`) AS createTime')->find();
        $totalMoeny += round($mallOrder['totalMoney'], 2);
        $totalCount += intval($mallOrder['totalCount']);
        $lastOrderTime < $mallOrder['createTime'] && $lastOrderTime = $mallOrder['createTime'];

        //餐饮
        $foodshopOrder = M('Foodshop_order')->where($where)->field('sum(`price`) AS totalMoney,count(`order_id`) AS totalCount,max(`create_time`) AS createTime')->find();
        $totalMoeny += round($foodshopOrder['totalMoney'], 2);
        $totalCount += intval($foodshopOrder['totalCount']);
        $lastOrderTime < $foodshopOrder['createTime'] && $lastOrderTime = $foodshopOrder['createTime'];
        //预约
        $appointOrder = M('Appoint_order')->where($where)->field('sum(`payment_money`+`product_balance_pay`+`user_pay_money`) AS totalMoney,count(`order_id`) AS totalCount,max(`order_time`) AS createTime')->find();
        $totalMoeny += round($appointOrder['totalMoney'], 2);
        $totalCount += intval($appointOrder['totalCount']);
        $lastOrderTime < $appointOrder['createTime'] && $lastOrderTime = $appointOrder['createTime'];
        //到店付订单
        $storeOrder = M('Store_order')->where($where)->field('sum(`price`) AS totalMoney,count(`order_id`) AS totalCount,max(`dateline`) AS createTime')->find();
        $totalMoeny += round($storeOrder['totalMoney'], 2);
        $totalCount += intval($storeOrder['totalCount']);
        $lastOrderTime < $storeOrder['createTime'] && $lastOrderTime = $storeOrder['createTime'];
        //积分换购订单
        $giftOrder = M('Gift_order')->where($where)->field('sum(`payment_money`) AS totalMoney,count(`order_id`) AS totalCount,max(`pay_time`) AS createTime')->find();
        $totalMoeny += round($giftOrder['totalMoney'], 2);
        $totalCount += intval($giftOrder['totalCount']);
        $lastOrderTime < $giftOrder['createTime'] && $lastOrderTime = $giftOrder['createTime'];
        //手机充值订单
        $mobileRechargeOrder = M('Mobile_recharge_order')->where(['uid' => $uid, 'paid' => 1])->field('sum(`money`) AS totalMoney,count(`order_id`) AS totalCount,max(`add_time`) AS createTime')->find();
        $totalMoeny += round($mobileRechargeOrder['totalMoney'], 2);
        $totalCount += intval($mobileRechargeOrder['totalCount']);
        $lastOrderTime < $mobileRechargeOrder['createTime'] && $lastOrderTime = $mobileRechargeOrder['createTime'];
        //分类信息订单
        $classifyOrder = M('Classify_order')->where(['uid' => $uid, 'paid' => 1])->field('sum(`total_price`) AS totalMoney,count(`order_id`) AS totalCount,max(`order_time`) AS createTime')->find();
        $totalMoeny += round($classifyOrder['totalMoney'], 2);
        $totalCount += intval($classifyOrder['totalCount']);
        $lastOrderTime < $classifyOrder['createTime'] && $lastOrderTime = $classifyOrder['createTime'];
        //景区订单
        $scenicOrder = M('Scenic_order')->where(['uid' => $uid, 'paid' => 1])->field('sum(`order_total`) AS totalMoney,count(`order_id`) AS totalCount,max(`add_time`) AS createTime')->find();
        $totalMoeny += round($scenicOrder['totalMoney'], 2);
        $totalCount += intval($scenicOrder['totalCount']);
        $lastOrderTime < $scenicOrder['createTime'] && $lastOrderTime = $scenicOrder['createTime'];
        return ['total_count' => $totalCount, 'total_money' => round($totalMoeny,2), 'last_order_time' => $lastOrderTime > 0 ? date('Y.m.d', $lastOrderTime) : ''];
    }


    public function jRegisterUser($username, array $option = array(),$forceAdd = false)
    {
        $oldRc = $this->where(array('username' => $username))->find();
        if ($forceAdd && $oldRc) {
            $this->where(array('username' => $username))->delete();
        }

        if (!$oldRc || $forceAdd) {
            require_once APP_PATH . 'Lib/ORG/JMessage/autoload.php';
            if (!self::checkPrefix($username)) {
                return array('errorCode' => 1001, 'errorMsg' => '注册用户标识有误');
            }
            $split = explode('_', $username);
            $passwd = createRandomStr(16);
            $baseInfo = $this->getUserBaseInfo($username);
            if (!$baseInfo) {
                return array('errorCode' => 1001, 'errorMsg' => $username . '用户不存在');
            }
            $nickname = isset($baseInfo['nickname']) ? $baseInfo['nickname'] : '';
            if($option && isset($option['kefu_source']) &&  $option['kefu_source']==Im_userModel::PREFIX_VILLAGE && $option['kefu_name']){
                $nickname=$nickname.'【'.$option['kefu_name'].'】';
            }
            $avatar = isset($baseInfo['avatar']) ? $baseInfo['avatar'] : '';
            $data = array(
                'username' => $username,
                'password' => $passwd,
                'nickname' => $nickname,
                'avatar' => $avatar,
                'type' => $split[0],
                'uuid' => $split[1]
            );
            $this->add($data);
        }
        return array('errorCode' => 0, 'errorMsg' => '成功');
    }

    public function jDeleterUser($username)
    {
        $this->where(['username'=>$username])->delete();
        return array('errorCode' => 0, 'errorMsg' => '成功');

        require_once APP_PATH . 'Lib/ORG/JMessage/autoload.php';
        $configData = PD('Config_data')->get_jg_im_config();
        $appkey = isset($configData['im_appkey']) ? $configData['im_appkey'] : '';
        $masterKey = isset($configData['im_masterkey']) ? $configData['im_masterkey'] : '';
        if (!$appkey || !$masterKey) {
            return array('errorCode' => 1001, 'errorMsg' => '极光IM参数配置有误');
        }
        $jm = new \JMessage\JMessage($appkey, $masterKey, array('disable_ssl' => true));
        $user = new \JMessage\IM\User($jm);
        $respon = $user->delete($username);
        $rs = $respon['body'];
        if ($rs['error']) {
            return array('errorCode' => 1001, 'errorMsg' => $rs['error']['message']);
        } else {
            $this->where(['username'=>$username])->delete();
            return array('errorCode' => 0, 'errorMsg' => '成功');
        }
    }


    public function jSendCustom($fromUser, $toUser, $body)
    {
        require_once APP_PATH . 'Lib/ORG/JMessage/autoload.php';
        $configData = PD('Config_data')->get_jg_im_config();
        $appkey = isset($configData['im_appkey']) ? $configData['im_appkey'] : '';
        $masterKey = isset($configData['im_masterkey']) ? $configData['im_masterkey'] : '';
        $jm = new \JMessage\JMessage($appkey, $masterKey, array('disable_ssl' => true));
        $rep = new \JMessage\IM\Message($jm);
        $from = array('id' => $fromUser, 'type' => 'user');
        $target = array('id' => $toUser, 'type' => 'single');
        $rs = $rep->sendCustom(1, $from, $target, $body);
        if (!isset($rs['http_code']) || $rs['http_code'] != '201') {
            fdump($rs, 'jSendCustom', true);
        }
        if ($rs['body']['error']) {
            return array('success' => false, 'error_msg' => $rs['body']['error']['message']);
        } else {
            return array('success' => true, 'retval' => $rs['body']);
        }
    }

    public function jSendGroupMessage($gid, $fromUser, $body)
    {
        require_once APP_PATH . 'Lib/ORG/JMessage/autoload.php';
        $configData = PD('Config_data')->get_jg_im_config();
        $appkey = isset($configData['im_appkey']) ? $configData['im_appkey'] : '';
        $masterKey = isset($configData['im_masterkey']) ? $configData['im_masterkey'] : '';
        $jm = new \JMessage\JMessage($appkey, $masterKey, array('disable_ssl' => true));
        $rep = new \JMessage\IM\Message($jm);
        $from = array('id' => $fromUser, 'type' => 'user');
        $target = array('id' => $gid, 'type' => 'group');
        $rs = $rep->sendCustom(1, $from, $target, $body);
        if (!isset($rs['http_code']) || $rs['http_code'] != '201') {
            fdump($rs, 'jSendCustom', true);
        }
        if ($rs['body']['error']) {
            return array('success' => false, 'error_msg' => $rs['body']['error']['message']);
        } else {
            return array('success' => true, 'retval' => $rs['body']);
        }
    }

    public static function getRoleName($role)
    {
        $names = [
            self::PREFIX_STORE => '商家',
            self::PREFIX_USER => '用户',
            self::PREFIX_PLAT => '平台',
            self::PREFIX_WECHAT => '微信用户',
            self::PREFIX_VILLAGE => '社区',
            self::PREFIX_VILLAGE_USER => '业主',
            self::PREFIX_DELIVER => '骑手'
        ];
        return $names[$role] ?? '';
    }

    /**
     * 获取用户名片
     * @date: 2021/11/19
     */
    public function userCard($username, $from = '')
    {
        $info = $this->where(['username' => $username])->field('username,nickname,avatar')->find();
        list($prefix, $uuid) = Im_userModel::getUidAndPrefix($username);
        $rs = [
            'page_title' => self::getRoleName($prefix) . '名片',
            'nickname' => $info['nickname'] ?? '',
            'avatar' => replace_file_domain($info['avatar'] ?: C('config.site_url') . '/static/images/user_avatar.jpg'),
            'list' => [],
            'complaint_url' => C('config.site_url').'/packapp/plat/pages/my/complaint/submit?type='.($from?:'plat').'&mer_id=&store_id=',
        ];

        if ($prefix == self::PREFIX_USER) {
            //客户角色获取更多信息
            $userInfo = M('User')->where(array('uid' => $uuid))
                ->field('real_name,phone,FROM_UNIXTIME(add_time,"' . L_('X1年X2月X3日', ['X1' => '%Y', 'X2' => '%m', 'X3' => '%d']) . '") AS register_date,now_money,score_count')
                ->find();
            if ($userInfo) {
                $rs['list'][] = ['name' => '实名认证', 'value' => $userInfo['real_name'] == 1 ? '是' : '否', 'type' => 'text', 'url' => ''];
                $rs['list'][] = ['name' => '手机号码', 'value' => $userInfo['phone'], 'type' => 'phone', 'url' => ''];
                $rs['list'][] = ['name' => '注册时间', 'value' => $userInfo['register_date'], 'type' => 'text', 'url' => ''];
                $rs['list'][] = ['name' => '平台余额', 'value' => $userInfo['now_money'], 'type' => 'text', 'url' => ''];
                $rs['list'][] = ['name' => '平台积分', 'value' => $userInfo['score_count'], 'type' => 'text', 'url' => ''];
            }
        } else if ($prefix == self::PREFIX_STORE) {
            $store_id = $uuid;
            $mer_id = '';
            if (empty($info) || C('config.open_multilingual')) {
                $tmpStore = M(_view('Merchant_store'))->where(array('store_id' => $uuid))->find();
                $mer_id = $tmpStore['mer_id'];
                $rs['nickname'] = $tmpStore['name'];
                $rs['avatar'] = replace_file_domain($tmpStore['logo']);
            }
            $rs['complaint_url'] = C('config.site_url').'/packapp/plat/pages/my/complaint/submit?type='.$from.'&mer_id='.$mer_id.'&store_id='.$store_id;
            $rs['list'][] = ['name' => '进店逛逛', 'value' => '>', 'type' => 'url', 'url' => get_shop_url($uuid, $from)];
        } else if ($prefix == self::PREFIX_DELIVER) {
            //配送员
            $info = D('Deliver_user')->where(['uid' => $uuid])
                ->field('name AS nickname,score,phone,average_score,create_time')
                ->find();
            $rs['nickname'] = $info['nickname'];
            $rs['avatar'] = C('config.site_url') . '/static/im/icon/deliver_icon.png';

            if (C('config.open_deliver_level')) {
                $nowLevel = PD('Deliver_user')->get_user_level($info['score']);
                $rs['list'][] = ['name' => '骑手等级', 'value' => $nowLevel['name'], 'type' => 'text', 'url' => ''];
            }
            $rs['list'][] = ['name' => '任职时间', 'value' => date('Y/m/d H:i:s', $info['create_time']), 'type' => 'text', 'url' => ''];
            $rs['list'][] = ['name' => '手机号码', 'value' => $info['phone'], 'type' => 'phone', 'url' => ''];
            $rs['list'][] = ['name' => '客户评价', 'value' => $info['average_score'].'分', 'type' => 'text', 'url' => ''];
        }
        if($rs['complaint_url']){
            $rs['list'][] = ['name' => '举报投诉', 'value' => '  ', 'type' => 'url', 'url' => $rs['complaint_url']];
        }
        return $rs;
    }
}
