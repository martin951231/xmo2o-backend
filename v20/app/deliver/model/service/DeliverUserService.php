<?php

namespace app\deliver\model\service;

use app\common\model\db\Area;
use app\common\model\service\AreaService;
use app\common\model\service\ConfigService;
use app\deliver\model\db\DeliverCount;
use app\deliver\model\db\DeliverSupply;
use app\deliver\model\db\DeliverUser;
use app\deliver\model\db\DeliverUserMoneyLog;
use app\deliver\model\db\DeliverUserWithdraw;
use app\deliver\model\db\UserWithdrawAccountList;
use app\mall\model\service\MallOrderService;
use app\merchant\model\service\MerchantStoreService;
use app\paotui\model\db\ServiceUserPublish;
use app\paotui\model\db\ServiceUserPublishGive;
use app\paotui\model\service\ServiceUserPublishService;
use app\shop\model\service\order\ShopOrderService;
use think\Exception;
use think\facade\Db;
use token\Ticket;

/**
 * 配送员服务类
 * @author: 张涛
 * @date: 2020/9/7
 * @package app\deliver\model\service
 */
class DeliverUserService
{

    /**
     * 获取access token url
     * @var string
     */
    protected $accessTokenUrl = 'https://aip.baidubce.com/oauth/2.0/token';

    /**
     * 人脸注册 url
     * @var string
     */
    protected $baidu_face_add = 'https://aip.baidubce.com/rest/2.0/face/v3/faceset/user/add';
    /**
     * 人脸删除 url
     * @var string
     */
    protected $baidu_face_delete = 'https://aip.baidubce.com/rest/2.0/face/v3/faceset/face/delete';
    /**
     * 人脸搜索 $url
     * @var string
     */
    protected $baidu_face_search = 'https://aip.baidubce.com/rest/2.0/face/v3/search';

    /**
     * 获取一条配送员记录
     * @author: 张涛
     * @date: 2020/9/17
     */
    public function getOneUser($where, $fields = '*')
    {
        return (new DeliverUser())->where($where)->field($fields)->findOrEmpty()->toArray();
    }

    /**
     * 手机号+密码登录
     * @author: 张涛
     * @date: 2020/9/7
     */
    public function login($phone, $password, $isPasswd = true)
    {
        $deliverUserMod = new DeliverUser();
        $where = [
            ['phone', '=', $phone],
            ['status', '=', $deliverUserMod::STATUS_NORMAL]
        ];
        $deliverUser = $deliverUserMod->getOne($where, 'uid,group,name,pwd,openid,is_notice,phone,status,phone_country_type,user_type');
        if (empty($deliverUser)) {
            throw new Exception(L_('账号不存在或禁用'));
        }
        if ($isPasswd) {
            if ($deliverUser['pwd'] != md5($password)) {
                throw new Exception(L_('密码错误'));
            }
        } else {
            $smsWhere = [
                ['phone', '=', $phone],
                ['type', '=', 31],
                ['status', '=', 0],
                ['extra', '=', $password],
                ['expire_time', '>', time()],
            ];
            $smsRecord = \think\facade\Db::name('app_sms_record')->where($smsWhere)->find();
            if (empty($smsRecord)) {
                throw new Exception(L_('验证码错误'));
            }
            \think\facade\Db::name('app_sms_record')->where('pigcms_id', $smsRecord['pigcms_id'])->update(['status' => 1]);
        }
        unset($deliverUser['pwd']);
        $info = $deliverUser ? $deliverUser->toArray() : [];
        if ($info) {
            $info['show_phone'] = $this->getShowPhone($info['phone'], $info['phone_country_type']);
        }
        $info['open_wallet'] = $this->isOpenWallet($info);
        return $info;
    }

    /**
     * 获取登录信息
     * @author: 张涛
     * @date: 2020/9/10
     */
    public function getLoginInfo($uid)
    {
        $deliverUserMod = new DeliverUser();
        $where = [
            ['uid', '=', $uid],
            ['status', '<>', $deliverUserMod::STATUS_DEL]
        ];
        $deliverUser = $deliverUserMod->getOne($where, 'uid,group,name,openid,is_notice,phone,status,phone_country_type,user_type');
        if (empty($deliverUser)) {
            throw new Exception(L_('账号不存在'));
        }
        if ($deliverUser['status'] != $deliverUserMod::STATUS_NORMAL) {
            throw new Exception(L_('账号被禁用'));
        }
        $info = $deliverUser ? $deliverUser->toArray() : [];
        if ($info) {
            $info['show_phone'] = $this->getShowPhone($info['phone'], $info['phone_country_type']);
        }

        $info['open_wallet'] = $this->isOpenWallet($info);
        return $info;
    }

    /**
     * 是否开启钱包
     * @author: 张涛
     * @date: 2020/12/22
     */
    public function isOpenWallet($info)
    {
        //$openWallet = cfg('open_deliver_wallet');
        $openWallet = (new ConfigService())->getOneField('open_deliver_wallet');
        $rs = false;
        if ($info['group'] == 1) {
            if ($openWallet == 1 && $info['user_type'] == 0) {
                $rs = true;
            } else if ($openWallet == 2 && $info['user_type'] == 1) {
                $rs = true;
            } else if ($openWallet == 3) {
                $rs = true;
            }
        }
        return $rs;
    }

    /**
     * 登录成功后处理
     * @param $data
     * @param $uid
     * @author: 张涛
     * @date: 2020/9/7
     */
    public function loginSucessAfter($data, $uid)
    {
        //记录最后登录的设备信息
        $deliverUser = (new DeliverUser())->where('uid', $uid)->find();
        isset($data['app_version']) && $deliverUser->app_version = $data['app_version'];
        isset($data['last_time']) && $deliverUser->last_time = $data['last_time'];
        isset($data['device_id']) && $deliverUser->device_id = $data['device_id'];
        isset($data['device_token']) && $deliverUser->device_token = $data['device_token'];
        isset($data['client']) && $deliverUser->client = $data['client'];
        isset($data['jpush_registrationId']) && $deliverUser->jpush_registrationId = $data['jpush_registrationId'];
        $deliverUser->save();

        //记录登录日志
        $loginLog = [
            'uid' => $uid,
            'client' => $data['client'],
            'device_id' => $data['device_id'],
            'add_time' => $data['last_time'],
            'add_ip' => $data['ip'],
        ];
        \think\facade\Db::name('deliver_user_login_log')->insert($loginLog);

        //登录成功应该把同样设备号的其他的用户全部设置为下线
        if ($uid > 0 && isset($data['device_id']) && !empty($data['device_id'])) {
            (new DeliverUser())->where('uid', '<>', $uid)->where('device_id', '=', $data['device_id'])->update(['last_time' => 0]);
        }
    }

    /**
     * 重置密码
     * @param $uid
     * @param $newPassword
     * @author: 张涛
     * @date: 2020/9/7
     */
    public function resetPasswrod($uid, $newPassword, $code)
    {
        $nowUser = $this->getOneUser(['uid' => $uid]);
        $smsWhere = [
            ['phone', '=', $nowUser['phone']],
            ['type', '=', 32],
            ['status', '=', 0],
            ['extra', '=', $code],
            ['expire_time', '>', time()],
        ];
        $smsRecord = \think\facade\Db::name('app_sms_record')->where($smsWhere)->find();
        if (empty($smsRecord)) {
            throw new Exception(L_('验证码错误'));
        }
        \think\facade\Db::name('app_sms_record')->where('pigcms_id', $smsRecord['pigcms_id'])->update(['status' => 1]);
        //校验密码
        $check_password = verifyPassword($newPassword);
        if($check_password['code']!=0){
            throw new Exception(L_($check_password['msg']));
        }

        $newPasswd = md5($newPassword);
        $rs = (new DeliverUser())->where('uid', $uid)->update(['pwd' => $newPasswd]);
        return $rs;
    }

    /**
     * 更换手机
     * @param $uid
     * @param $phone
     * @author: 张涛
     * @date: 2020/9/7
     */
    public function changePhone($uid, $phone, $code)
    {
        $nowUser = $this->getOneUser(['uid' => $uid]);
        $smsWhere = [
            ['phone', '=', $phone],
            ['type', '=', 33],
            ['status', '=', 0],
            ['extra', '=', $code],
            ['expire_time', '>', time()],
        ];
        $smsRecord = \think\facade\Db::name('app_sms_record')->where($smsWhere)->find();
        if (empty($smsRecord)) {
            throw new Exception(L_('验证码错误'));
        }
        \think\facade\Db::name('app_sms_record')->where('pigcms_id', $smsRecord['pigcms_id'])->update(['status' => 1]);

        $checkWhere = [
            ['phone', '=', $phone],
            ['status', '<>', DeliverUser::STATUS_DEL],
            ['uid', '<>', $uid]
        ];
        $deliverUserMod = new DeliverUser();
        $checkUser = $deliverUserMod->where($checkWhere)->find();
        if ($checkUser) {
            throw new Exception(L_('该手机号已经是配送员账号了，不能重复绑定'));
        }
        $deliverUserMod->where('uid', $uid)->update(['phone' => $phone]);
        return true;
    }

    /**
     * 注销账号
     * @param $uid
     * @author: 张涛
     * @date: 2020/9/7
     */
    public function destroyAccount($uid)
    {
        $data = ['status' => DeliverUser::STATUS_DEL];
        (new DeliverUser())->where('uid', $uid)->save($data);
        return true;
    }


    /**
     * 获取配送员基础信息
     * @param $uid
     * @author: 张涛
     * @date: 2020/9/8
     */
    public function getBaseInfo($uid)
    {
        $deliverUserMod = new DeliverUser();
        $deliverUser = $deliverUserMod->getOne(['uid' => $uid], 'uid,name,openid,is_notice,phone,status,group,store_id,store_ids,now_lng,now_lat,user_type');
        if (empty($deliverUser)) {
            throw new Exception(L_('配送员账号不存在'));
        }
        $deliverUser = $deliverUser->toArray();

        //获取头像
        if ($deliverUser['group'] == 1) {
            $deliverUser['avatar'] = replace_file_domain(cfg('wechat_share_img') ? cfg('wechat_share_img') : cfg('site_logo'));
        } else {
            $storeInfo = (new MerchantStoreService())->getStoreInfo($deliverUser['store_id']);
            $deliverUser['avatar'] = $storeInfo['logo'] ? replace_file_domain($storeInfo['logo']) : '';
        }
        $deliverUser['open_wallet'] = $this->isOpenWallet($deliverUser);
        return $deliverUser;
    }

    /**
     * 获取配送员当前订单数量
     * @author: 张涛
     * @date: 2020/09/16
     */
    public function getCurrentCount($uid)
    {
        return (new DeliverSupply())->where("uid={$uid} AND status >0 AND status < 5 AND NOT (get_type IN (1,2) AND status IN (2,3,4) AND uid={$uid} AND is_fetch_order = 0)")->count();
    }

    /**
     * 获取新订单数量
     * @author: 张涛
     * @date: 2020/09/16
     */
    public function getNewOrderCount($uid)
    {
        $count = 0;
        $nowUser = (new DeliverUser())->where('uid', $uid)->find();
        $tm = time();
        if ($nowUser) {
            $canTakeOrder = cfg('deliver_rest_can_take_order');
            //配送员休息中
            if ($nowUser['is_notice'] != 0 && $canTakeOrder == 0) {
                return $count;
            }

            $myLng = $nowUser->lng;
            $myLat = $nowUser->lat;
            $myRange = $nowUser->range * 1000;

            if ($nowUser->group == 1) {
                //平台配送员 状态必须为待结单和没有关联配送员
                $where = "`type`= 0 AND `status`=1 AND `uid`=0 AND NOT FIND_IN_SET($uid,back_log) AND NOT FIND_IN_SET($uid,refuse_log)";
                if ($nowUser->delivery_range_type == 0) {
                    $where .= " AND ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$myLat}*PI()/180-`from_lat`*PI()/180)/2),2)+COS({$myLat}*PI()/180)*COS(`from_lat`*PI()/180)*POW(SIN(({$myLng}*PI()/180-`from_lnt`*PI()/180)/2),2)))*1000) < $myRange ";
                } else {
                    $where .= " AND MBRContains(PolygonFromText('" . $nowUser->delivery_range_polygon . "'),PolygonFromText(CONCAT('Point(',from_lnt,' ',from_lat,')')))>0";
                }
                $where = '((' . $where . ') OR (get_type IN (1,2) AND status IN (2,3,4)  AND uid=' . $uid . ' AND is_fetch_order = 0))';
            } else {
                //商家配送员
                $storeIds = $nowUser->store_ids ? $nowUser->store_ids : $nowUser->store_id;
                $where = "`type`= 1 AND `status`=1 AND `store_id` IN (" . $storeIds . ")";
            }

            //配送员可配送订单类型判断
            $whiteOrderType = explode(',', $nowUser->order_type);
            $whiteCondition = [];
            if (in_array(1, $whiteOrderType)) {
                //商家单
                $whiteCondition[] = " (`item` IN (2,4,5)) ";
            }
            if (in_array(2, $whiteOrderType)) {
                //帮买单
                $whiteCondition[] = " (`item`=3 AND `server_type` IN (2,3)) ";
            }
            if (in_array(3, $whiteOrderType)) {
                //帮送单
                $whiteCondition[] = " (`item`=3 AND `server_type`=1) ";
            }
            if (in_array(4, $whiteOrderType)) {
                //社区团购
                $whiteCondition[] = " (`item`= 8) ";
            }
            $where .= ' AND (' . implode('OR', $whiteCondition) . ')';

            //新订单读取最近7天的订单，数量限制300单，防止历史订单过多导致系统卡死
            $where .= ' AND create_time > ' . ($tm - 86400 * 7);
            $supplyMod = new DeliverSupply();
            $items = $supplyMod->where($where)->order('supply_id DESC')->select()->toArray();

            //屏蔽掉不允许抢单的订单
            $cityIds = array_unique(array_column($items, 'city_id'));
            $areaConfig = (new \app\common\model\db\Area())->whereIn('area_id', $cityIds)->column('no_grab_min', 'area_id');
//            $areaDeliverConfig = (new \app\common\model\db\Area())->whereIn('area_id', $cityIds)->column('deliver_model', 'area_id');
            foreach ($items as $k => $v) {
                if ($v['type'] == 0 && $v['status'] == 1 && isset($areaConfig[$v['city_id']]) && $v['create_time'] + $areaConfig[$v['city_id']] * 60 < $tm) {
                    unset($items[$k]);
                }
                //                elseif($v['type'] == 0 && isset($areaDeliverConfig[$v['city_id']]) && $areaDeliverConfig[$v['city_id']] > 0 && $v['get_type'] == 0){//禁止抢单的城市的订单不出现
                //                    unset($items[$k]);
                //                }
                //判断跑腿单是否来自商家中心，而且商家是否绑定了骑手
                if (cfg('open_paotui_price_template') && $v['item'] == 3 && $v['mer_id'] > 0) {
                    $poolIds = Db::name('paotui_grab_pool_merchant')->alias('m')
                        ->join('paotui_grab_pool p', 'p.id = m.pool_id')
                        ->where('p.status', 1)
                        ->where('m.mer_id', $v['mer_id'])
                        ->column('p.id');
                    if ($poolIds) {
                        //商家绑定了配送池，则只能配送池里的骑手才能抢单，其他骑手不允许抢单
                        $isBind = Db::name('paotui_grab_pool_deliver')->whereIn('pool_id', $poolIds)->where('deliver_uid', $uid)->find();
                        if (empty($isBind)) {
                            unset($items[$k]);
                        }
                    } else {
                        //商家没绑定配送池，走原逻辑抢单
                    }
                }
            }
            $count = count($items);

            //获取到其他用户转给自己的
            $where = 'transfer_to_uid =' . $nowUser->uid . ' AND transfer_status=0 AND transfer_deliver_status = deliver_status';
            $transferCount = $supplyMod->where($where)->count();
            $count = $count + $transferCount;
            //超出最大接单量，则显示新任务数量重置为0
            $currentOrderCount = $this->getCurrentCount($uid);
            if ($nowUser->max_num != 0 && $nowUser->max_num <= $currentOrderCount) {
                $count = 0;
            } 
        }
        return $count;
    }

    /**
     * 获取待取货订单数量
     * @author: 张涛
     * @date: 2020/09/16
     */
    public function getToPickOrderCount($uid)
    {
        return (new DeliverSupply())->where('deliver_status > 1 AND deliver_status < 4 AND item in (2,3,4,5,8) AND is_fetch_order=1 AND uid = ' . $uid)->count();
    }

    /**
     * 获取待送达订单数量
     * @author: 张涛
     * @date: 2020/09/16
     */
    public function getToFinishOrderCount($uid)
    {
        return (new DeliverSupply())->where('deliver_status = 4 AND is_fetch_order=1 AND uid = ' . $uid)->count();
    }

    /**
     * 订单列表
     * @author: 张涛
     * @date: 2020/9/9
     */
    public function getOrderLists($type, $uid, $orderType = "")
    {
        $deliverUserMod = new DeliverUser();
        $nowUser = $deliverUserMod->getOne(['uid' => $uid, 'status' => $deliverUserMod::STATUS_NORMAL], '*');
        if (empty($nowUser)) {
            throw new Exception(L_('配送员账号不存在'));
        }
        if (empty($nowUser->order_type)) {
            return [];
        }

        $orders = [];
        $where = "";

        if ($orderType == 'merchant_order') {
            $where = "`item` IN (2,4,5) AND ";
        } else if ($orderType == 'service_buy') {
            $where = "`item`=3 AND `server_type` IN (2,3) AND ";
        } else if ($orderType == 'service_send') {
            $where = "`item`=3 AND `server_type`=1 AND ";
        } else if ($orderType == 'village_group_order') {
            $where = "`item`=8 AND ";
        }


        $supplyService = new DeliverSupplyService();
        if ($type == 'new') {
            $canTakeOrder = cfg('deliver_rest_can_take_order');
            //配送员休息中不返回新订单
            if ($nowUser['is_notice'] != 0 && $canTakeOrder == 0) {
                $orders = [];
            } else {
                $orders = $supplyService->getNewOrderLists($nowUser, $orderType);
            }
        } else if ($type == 'pick') {
            $orders = $supplyService->getOrderLists($where . 'deliver_status > 1 AND deliver_status < 4 AND is_fetch_order=1 AND uid =' . $uid, $uid);
        } else if ($type == 'finish') {
            $orders = $supplyService->getOrderLists($where . 'deliver_status = 4 AND is_fetch_order=1 AND uid =' . $uid, $uid);
        }

        //过滤转单超时的
        $tm = time();
        if (!isset($orders['max_num'])) {
            foreach ($orders as $k => $v) {
                //超时了
                if ($v['transfer_time'] > 0 && $tm > $v['transfer_time'] + config('const.refuse_transfer_order_expire')) {
                    (new DeliverSupplyService())->transferExpiredUpdae($v['supply_id']);
                    if ($v['transfer_to_uid'] == $uid) {
                        unset($orders[$k]);
                    } else {
                        $orders[$k]['is_transfer_order'] = false;
                        $orders[$k]['transfer_left_second'] = 0;
                        $orders[$k]['btns'] = $supplyService->getBtnsByDeliverStatus($v['deliver_status'], '', 0, $v['is_fetch_order'],0,$v['need_prefect_order_info']);
                    }
                }
            }
            $orders = array_values($orders);
        }
        return $orders;
    }

    /**
     * 根据配送ID获取订单列表
     * @param $supplyId
     * @return array
     * @author: 张涛
     * @date: 2020/9/9
     */
    public function getOrderBySupplyId($supplyId)
    {
        $orders = (new DeliverSupplyService())->getOrderLists(['supply_id' => $supplyId]);
        return $orders;
    }

    /**
     * 更改配送状态
     * @author: 张涛
     * @date: 2020/9/9
     */
    public function updateDeliverStatus($uid, $supplyId, $action,$transferToUid=0)
    {
        switch ($action) {
            case 'grab_order':
                $this->grabOrder($uid, $supplyId);
                break;
            case 'report_arrive_store':
                $this->reportArriveStore($uid, $supplyId);
                break;
            case 'pick_order':
                $this->pickOrder($uid, $supplyId);
                break;
            case 'finish_order':
                $this->finishOrder($uid, $supplyId);
                break;
            case 'throw_order':
                $this->throwOrder($uid, $supplyId);
                break;
            case 'transfter_order':
                $this->transfterOrder($uid, $supplyId, $transferToUid);
                break;
            case 'accept_transfer_order':
                $this->acceptTransferOrder($uid, $supplyId);
                break;
            case 'refuse_order':
                $this->refuseOrder($uid, $supplyId);
                break;
            case 'fetch_order':
                $this->fetchOrder($uid, $supplyId);
                break;
            default:
                throw new Exception(L_('非法action'));
        }
        return true;
    }

    /**
     * 配送员处理订单各个操作之前检测
     * @param $uid
     * @param $supplyId
     * @author: 张涛
     * @date: 2020/9/10
     */
    public function beforeActionCheck($uid, $supplyId)
    {
        $supply = (new DeliverSupply())->where('supply_id', $supplyId)->findOrEmpty()->toArray();
        $user = (new DeliverUser())->where('uid', $uid)->findOrEmpty()->toArray();

        if (empty($supply)) {
            throw new Exception(L_('配送记录不存在'));
        }
        if (empty($user)) {
            throw new Exception(L_('配送员不存在'));
        }
        if ($supply['status'] == 0) {
            throw new Exception(L_('配送单已取消'));
        }

        if ($user['group'] == 1) {
            //平台配送员
            if ($supply['type'] != 0) {
                throw new Exception(L_('平台配送员禁止操作商家配送订单'));
            }
        } else if ($user['group'] == 2) {
            //商家配送员
            if ($supply['type'] != 1) {
                throw new Exception(L_('商家配送员禁止操作平台配送订单'));
            }
            $storeIds = !empty($user['store_ids']) ? explode(',', $user['store_ids']) : [$user['store_id']];
            if (!in_array($supply['store_id'], $storeIds)) {
                throw new Exception(L_('非该店铺配送员禁止操作'));
            }
        }
        return ['supply' => $supply, 'user' => $user];
    }

    /**
     * 抢单
     * @param $uid
     * @param $supplyId
     * @author: 张涛
     * @date: 2020/9/10
     */
    public function grabOrder($uid, $supplyId)
    {
        $checkRs = $this->beforeActionCheck($uid, $supplyId);
        $supply = $checkRs['supply'];
        $user = $checkRs['user'];
        if ($user['is_notice'] != 0 && cfg('deliver_rest_can_take_order') == 0) {
            throw new Exception(L_('配送员休息中禁止抢单'));
        }
        //查询是否允许抢单
        $areaInfo = (new Area())->where(['area_id'=>$supply['city_id']])->field('deliver_model')->find();
        if($supply['type'] == 0 && $areaInfo && $areaInfo['deliver_model'] > 0){
            throw new Exception(L_('当前配置不支持抢单！'));
        }
        //查询
        if ($supply['item'] == 2) {
            //外卖
            (new ShopOrderService())->grabOrder($uid, $supplyId);
        }else if($supply['item'] == 3){
            (new ServiceUserPublishService())->grabOrder($uid, $supplyId);
        } else if ($supply['item'] == 4 || $supply['item'] == 5 || $supply['item'] == 8) {
            (new DeliverSupplyService())->grabOrder($uid, $supplyId);
        } else {
            throw new Exception(L_('该类型订单暂不支持'));
        }
    }

    /**
     * 上报到店
     * @author: 张涛
     * @date: 2020/9/10
     */
    public function reportArriveStore($uid, $supplyId)
    {
        $checkRs = $this->beforeActionCheck($uid, $supplyId);
        $supply = $checkRs['supply'];
        if ($supply['item'] == 2) {
            //外卖
            (new ShopOrderService())->reportArriveStore($uid, $supplyId);
        } else if ($supply['item'] == 3) {
            (new ServiceUserPublishService())->reportArriveStore($uid, $supplyId);
        } else if ($supply['item'] == 4 || $supply['item'] == 5 || $supply['item'] == 8) {
            (new DeliverSupplyService())->reportArriveStore($uid, $supplyId);
        } else {
            throw new Exception(L_('该类型订单暂不支持'));
        }
    }

    /**
     * 我已取货
     * @author: 张涛
     * @date: 2020/9/10
     */
    public function pickOrder($uid, $supplyId)
    {
        $checkRs = $this->beforeActionCheck($uid, $supplyId);
        $supply = $checkRs['supply'];
        $user = $checkRs['user'];
        //增加上报到店距离限制判断
        $distance = get_distance($user['now_lat'], $user['now_lng'], $supply['from_lat'], $supply['from_lnt']);
        $area = (new AreaService)->getOne(['area_id' => $supply['city_id']]);
        if ($supply['server_type'] != 3 && $area['pick_miles_range'] > 0 && $area['pick_miles_range'] * 1000 < $distance) {
            throw new Exception(L_('距离商家超出X1公里，禁止到店取货', ['X1' => $area['pick_miles_range']]));
        }
        if ($supply['item'] == 2) {
            //外卖
            (new ShopOrderService())->pickOrder($uid, $supplyId);
        } else if ($supply['item'] == 3) {
            (new ServiceUserPublishService())->pickOrder($uid, $supplyId);
        } else if ($supply['item'] == 4 || $supply['item'] == 5 || $supply['item'] == 8) {
            (new DeliverSupplyService())->pickOrder($uid, $supplyId);
        } else {
            throw new Exception(L_('该类型订单暂不支持'));
        }
    }

    /**
     * 我已送达
     * @author: 张涛
     * @date: 2020/9/10
     */
    public function finishOrder($uid, $supplyId)
    {
        $checkRs = $this->beforeActionCheck($uid, $supplyId);
        $supply = $checkRs['supply'];
        $user = $checkRs['user'];
        //增加我已送达距离限制判断
        $distance = get_distance($user['now_lat'], $user['now_lng'], $supply['aim_lat'], $supply['aim_lnt']);
        $area = (new AreaService)->getOne(['area_id' => $supply['city_id']]);
        if ($supply['server_type'] != 3 && $area['finish_miles_range'] > 0 && $area['finish_miles_range'] * 1000 < $distance) {
            throw new Exception(sprintf('当前位置距离用户收货地%s，超出系统设置%s公里，禁止确认送达', get_range($distance, false, true), $area['finish_miles_range']));
        }
        if ($supply['item'] == 2) {
            //外卖
            (new ShopOrderService())->finishOrder($uid, $supplyId);
        } else if ($supply['item'] == 3) {
            (new ServiceUserPublishService())->finishOrder($uid, $supplyId);
        } else if ($supply['item'] == 4 || $supply['item'] == 5 || $supply['item'] == 8) {
            (new DeliverSupplyService())->finishOrder($uid, $supplyId);
        } else {
            throw new Exception(L_('该类型订单暂不支持'));
        }
    }

    /**
     * 扔回订单
     * @author: 张涛
     * @date: 2020/09/10
     */
    public function throwOrder($uid, $supplyId)
    {
        $checkRs = $this->beforeActionCheck($uid, $supplyId);
        $supply = $checkRs['supply'];
        if ($supply['item'] == 2) {
            //外卖
            (new ShopOrderService())->throwOrder($uid, $supplyId);
        } else if ($supply['item'] == 3) {
            (new ServiceUserPublishService())->throwOrder($uid, $supplyId);
        } else if ($supply['item'] == 4 || $supply['item'] == 5 || $supply['item'] == 8) {
            (new DeliverSupplyService())->throwOrder($uid, $supplyId);
        } else {
            throw new Exception(L_('该类型订单暂不支持'));
        }
    }

    /**
     * 转单
     * @author: 张涛
     * @date: 2020/09/10
     */
    public function transfterOrder($uid, $supplyId, $transferToUid)
    {
        if ($transferToUid < 1) {
            throw new Exception(L_('请选择配送员'));
        }
        if ($uid == $transferToUid) {
            throw new Exception(L_('禁止转单给自己'));
        }
        $checkRs = $this->beforeActionCheck($uid, $supplyId);
        $supply = $checkRs['supply'];
        //增加转单判断
        $area = (new AreaService)->getOne(['area_id' => $supply['city_id']]);
        if ($area['open_transfer'] != 1) {
            throw new Exception(L_('该配送单所在城市禁止转单'));
        }
        if ($supply['item'] == 2) {
            //外卖
            (new ShopOrderService())->transfterOrder($uid, $supplyId, $transferToUid);
        } else if ($supply['item'] == 3) {
            (new ServiceUserPublishService())->transfterOrder($uid, $supplyId, $transferToUid);
        } else if ($supply['item'] == 4 || $supply['item'] == 5|| $supply['item'] == 8) {
            (new DeliverSupplyService())->transfterOrder($uid, $supplyId, $transferToUid);
        }else {
            throw new Exception(L_('该类型订单暂不支持'));
        }
    }

    /**
     * 接受转单
     * @author: 张涛
     * @date: 2020/09/10
     */
    public function acceptTransferOrder($uid, $supplyId)
    {
        $checkRs = $this->beforeActionCheck($uid, $supplyId);
        $supply = $checkRs['supply'];
        if ($supply['item'] == 2) {
            //外卖
            (new ShopOrderService())->acceptTransferOrder($uid, $supplyId);
        } else if ($supply['item'] == 3) {
            (new ServiceUserPublishService())->acceptTransferOrder($uid, $supplyId);
        } else if ($supply['item'] == 4 || $supply['item'] == 5|| $supply['item'] == 8) {
            (new DeliverSupplyService())->acceptTransferOrder($uid, $supplyId);
        } else {
            throw new Exception(L_('该类型订单暂不支持'));
        }
    }

    /**
     * 拒单转单
     * @author: 张涛
     * @date: 2020/09/10
     */
    public function refuseOrder($uid, $supplyId)
    {
        $checkRs = $this->beforeActionCheck($uid, $supplyId);
        $supply = $checkRs['supply'];
        if ($supply['item'] == 2) {
            //外卖
            (new ShopOrderService())->refuseOrder($uid, $supplyId);
        } else if ($supply['item'] == 3) {
            (new ServiceUserPublishService())->refuseOrder($uid, $supplyId);
        } else if ($supply['item'] == 4 || $supply['item'] == 5|| $supply['item'] == 8) {
            (new DeliverSupplyService())->refuseOrder($uid, $supplyId);
        } else {
            throw new Exception(L_('该类型订单暂不支持'));
        }
    }

    /**
     * 接受派单
     * @param $uid
     * @param $supplyId
     * @author: 张涛
     * @date: 2020/10/24
     */
    public function fetchOrder($uid, $supplyId){
        $checkRs = $this->beforeActionCheck($uid, $supplyId);
        $supply = $checkRs['supply'];
        if ($supply['item'] == 2) {
            //外卖
            (new ShopOrderService())->fetchOrder($uid, $supplyId);
        } else if ($supply['item'] == 3) {
            (new ServiceUserPublishService())->fetchOrder($uid, $supplyId);
        } else if ($supply['item'] == 4 || $supply['item'] == 5 || $supply['item'] == 8) {
            (new DeliverSupplyService())->fetchOrder($uid, $supplyId);
        }else {
            throw new Exception(L_('该类型订单暂不支持'));
        }
    }

    /**
     * 获取配送员等级
     * @param $score
     * @return array
     * @author: 张涛
     * @date: 2020/9/10
     */
    public function getUserLevel($score)
    {
        $levelList = \think\facade\Db::name('deliver_level')->order('score', 'asc')->select()->toArray();
        $nowLevel = array(
            'level' => 0,
            'score' => $score,
            'now_score' => 0,
            'name' => L_('暂无等级'),
            'next_score' => 0,
            'order_number' => -1, // 没有等级不限制接单量
        );
        if ($levelList) {
            foreach ($levelList as $key => $value) {
                if ($score >= $value['score']) {
                    if ($nowLevel['now_score'] <= $value['score']) {
                        $nowLevel['now_score'] = $value['score'];
                        $nowLevel['name'] = $value['name'];
                        $nowLevel['level'] = $value['level'];
                        $nowLevel['order_number'] = $value['order_number'];
                    }
                } else {
                    if ($value['score'] < $nowLevel['next_score'] || !$nowLevel['next_score']) {
                        $nowLevel['next_score'] = $value['score'];
                    }
                }
            }
        }
        return $nowLevel;
    }

    /**
     * 更新配送员总配送订单量
     * @param $uid
     * @author: 张涛
     * @date: 2020/9/12
     */
    public function updateDeliverUserTotalNum($uid)
    {
        $supplyMod = new DeliverSupply();
        $userMod = new DeliverUser();

        //更新个人总配送订单量
        $total = $supplyMod->where([['uid', '=', $uid], ['status', '>', 4]])->count();
        $userMod->where(['uid' => $uid])->update(['num' => $total]);
        return true;
    }

    /**
     * 更新配送员每日配送量
     * @param $uid
     * @param string $date 格式：Ymd
     * @author: 张涛
     * @date: 2020/9/12
     */
    public function updateDeliverUserNumByDate($uid, $date = '')
    {
        $supplyMod = new DeliverSupply();
        $countMod = new DeliverCount();

        $beginTime = strtotime($date);
        $where = [
            ['uid', '=', $uid],
            ['status', '>', 4],
            ['end_time', '>', $beginTime],
            ['end_time', '<', $beginTime + 86400],
        ];
        $num = $supplyMod->where($where)->count();

        $item = $countMod->where(['uid' => $uid, 'today' => $date])->find();
        if ($item) {
            $item->num = $num;
            $item->save();
        } else {
            $countMod->insert(['uid' => $uid, 'today' => $date, 'num' => $num]);
        }
        return true;
    }


    /**
     * 配送员增加积分
     * @param $uid 配送员ID
     * @param $score 积分
     * @param $desc  描述
     * @param $from 操作来源
     * @param array $param 其他参
     * @return array
     * @author: 张涛
     * @date: 2020/9/12
     */
    public function addScore($uid, $score, $desc, $from, $param = [])
    {
        if (!cfg('open_deliver_level')) {
            throw new Exception(L_('平台没有开启配送员等级配置'));
        }
        if (cfg('score_round_two') == 1) {
            $score = sprintf('%.2f', floatval($score));
        } else {
            $score = round($score);
        }
        $userMod = new DeliverUser();
        if ($score > 0) {
            if ($userMod->where([['uid', '=', $uid], ['status', '<>', 4]])->inc('score', $score)->update()) {
                (new DeliverScoreLogService())->addRow($uid, 1, $from, $score, $desc, 0, $param);
            } else {
                throw new Exception(L_('添加积分失败！请联系管理员协助解决。'));
            }
        } else {
            throw new Exception(L_('积分数据有误'));
        }
        return true;
    }

    /**
     * 配送员减少积分
     * @author: 张涛
     * @date: 2020/9/12
     */
    public function deductScore($uid, $score, $desc, $from, $param = [])
    {
        if (!cfg('open_deliver_level')) {
            throw new Exception(L_('平台没有开启配送员等级配置'));
        }

        if (cfg('score_round_two') == 1) {
            $score = sprintf('%.2f', floatval($score));
        } else {
            $score = round($score);
        }
        $userMod = new DeliverUser();
        if ($score > 0) {
            $condition_user['uid'] = $uid;
            $condition_user['status'] = array('neq', 4);
            if ($userMod->where([['uid', '=', $uid], ['status', '<>', 4]])->dec('score', $score)->update()) {
                (new DeliverScoreLogService())->addRow($uid, 2, $from, $score, $desc, 0, $param);
            } else {
                throw new Exception(L_('扣除积分失败！请联系管理员协助解决。'));
            }
        } else {
            throw new Exception(L_('积分数据有误'));
        }
        return true;
    }

    /**
     * 接单中/休息中
     * @author: 张涛
     * @date: 2020/09/12
     */
    public function saveNotice($uid, $isNotice)
    {
        return (new DeliverUser())->where('uid', '=', $uid)->update(['is_notice' => $isNotice]);
    }


    /**
     * 获取订单详情
     * @author: 张涛
     * @date: 2020/09/14
     */
    public function orderDetail($supplyId, $uid)
    {
        $rs = (new DeliverSupplyService())->getOrderDetail($supplyId, $uid);
        /*if ($rs && $rs['uid'] > 0 && $uid > 0 && $uid != $rs['uid']) {
            throw new Exception(L_('非当前配送员订单禁止查看'));
        }*/
        return $rs;
    }


    /**
     * 附近配送员
     * @param $supplyId  配送ID
     * @param array $exceptUid 除去配送员用户uid
     * @author: 张涛
     * @date: 2020/9/10
     */
    public function deliverNearby($supplyId, $exceptUid)
    {
        $supplyMod = new DeliverSupply();
        $supply = $supplyMod->where(['supply_id' => $supplyId])->findOrEmpty()->toArray();
        if (empty($supply)) {
            throw new Exception(L_('配送记录不存在'));
        }

        //增加转单判断
        $area = (new AreaService)->getOne(['area_id' => $supply['city_id']]);
        if ($area['open_transfer'] != 1) {
            return [];
        }

        $checkOrderType = 0;
        if (in_array($supply['item'], [2, 4, 5])) {
            $checkOrderType = 1;
        } else if ($supply['item'] == 3 && in_array($supply['server_type'], [2, 3])) {
            $checkOrderType = 2;
        } else if ($supply['item'] == 3 && $supply['server_type'] == 1) {
            $checkOrderType = 3;
        }

        $userMod = new DeliverUser();
        $thisUser = $userMod->where(['uid' => $exceptUid])->findOrEmpty()->toArray();
        $condition = [
            ['is_notice', '=', 0],
            ['uid', '<>', $exceptUid],
            ['last_time','<>',0],
            ['status', '=', 1],
        ];
        if ($supply['type'] == 0) {
            //平台配送
            $condition[] = ['group', '=', 1];
            $lat = $supply['from_lat'];
            $lng = $supply['from_lnt'];

            $raw = "`order_type` <> '' AND FIND_IN_SET($checkOrderType,`order_type`) AND ((`delivery_range_type`=0 AND ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`lat`*PI()/180)*POW(SIN(({$lng}*PI()/180-`lng`*PI()/180)/2),2)))*1000) <= `range`*1000)";
            $raw .= " OR (`delivery_range_type`=1 AND MBRContains(PolygonFromText(`delivery_range_polygon`),PolygonFromText('Point({$lng} {$lat})'))>0))";
        } else {
            //商家配送
            $condition[] = ['group', '=', 2];
            $raw = "true";
        }
        $users = $userMod->where($condition)->whereRaw($raw)->select()->toArray();
        $rs = [];
        $openDeliverLevel = cfg('open_deliver_level');
        $zeroTime = strtotime(date("Y-m-d"));
        $tm = time();
        foreach ($users as $k => $v) {
            // 开启配送员等级
            if ($openDeliverLevel) {
                $nowLevel = $this->getUserLevel($v['score']);
                if ($nowLevel['order_number'] >= 0) {
                    // 今日接单量
                    $where = [
                        ['uid', '=', $v['uid']],
                        ['start_time', '>', $zeroTime],
                        ['start_time', '<', $tm],
                    ];
                    $todayNum = $supplyMod->where($where)->count();
                    if ($todayNum >= $nowLevel['order_number']) {
                        // 今日接单量已达上限！
                        unset($users[$k]);
                        continue;
                    }
                }
            }


            $meters = get_distance($v['now_lat'], $v['now_lng'], $thisUser['now_lat'], $thisUser['now_lng']);
            if ($meters > 1000) {
                $miles = get_format_number($meters / 1000);
                $unit = 'km';
            } else {
                $miles = $meters;
                $unit = 'm';
            }
            $rs[] = [
                'uid' => $v['uid'],
                'name' => $v['name'],
                'order_count' => $v['num'],
                'miles' => $miles,
                'meters' => $meters,
                'unit' => $unit
            ];
        }
        if ($rs) {
            $rs = array_values($rs);
            $meters = array_column($rs, 'meters');
            array_multisort($meters, SORT_ASC, SORT_NUMERIC, $rs);
        }
        return $rs;
    }

    /**
     * 日订单统计
     * @param $uid
     * @param $date
     * @author: 张涛
     * @date: 2020/9/16
     */
    public function dayReport($uid,$date){
        $beginTime = strtotime($date);
        $endTime = $beginTime + 86400;
        $supplyMod = new DeliverSupply();
        //完成订单
        $where = [
            ['uid', '=', $uid],
            ['status', 'in', '5,6'],
            ['start_time', 'between', [$beginTime, $endTime]]
        ];
        $finishCount = $supplyMod->where($where)->count();

        //配送里程
        $totalMiles = $supplyMod->where($where)->sum('distance');

        //取消订单
        $where = [
            ['uid', '=', $uid],
            ['status', '=', 0],
            ['start_time', 'between', [$beginTime, $endTime]]
        ];
        $cancelCount = $supplyMod->where($where)->count();

        //已抢订单
        $where = [
            ['uid', '=', $uid],
            ['get_type', '=', 0],
            ['start_time', 'between', [$beginTime, $endTime]]
        ];
        $grabCount = $supplyMod->where($where)->count();

        //指派订单
        $where = [
            ['uid', '=', $uid],
            ['get_type', 'IN', [1, 3, 4]],
            ['start_time', 'between', [$beginTime, $endTime]]
        ];
        $assignCount = $supplyMod->where($where)->count();

        //已转订单
        $where = [
            ['transfer_from_uid', '=', $uid],
            ['transfer_status', '=', 0],
            ['start_time', 'between', [$beginTime, $endTime]]
        ];
        $transferCount = $supplyMod->where($where)->count();

        return [
            'finish_count' => $finishCount,
            'cancel_count' => $cancelCount,
            'grab_count' => $grabCount,
            'assign_count' => $assignCount,
            'transfer_count' => $transferCount,
            'total_miles' => $totalMiles
        ];
    }

    /**
     * 月订单统计
     * @param $uid  配送员ID
     * @param $count  最近几个月，4：最近4个月
     * @author: 张涛
     * @date: 2020/9/17
     */
    public function monthReport($uid, $count)
    {
        $rs = [];
        $supplyMod = new DeliverSupply();
        $tm = time();
        $nowUser = (new DeliverUserService())->getOneUser('uid='.$uid);
        if (empty($nowUser)) {
            throw new Exception(L_('配送员不存在'));
        }
        $thisMonthStartTime = strtotime(date('Y-m', $tm) . '-01');
        for ($i = 0; $i <= $count; $i++) {
            if ($i == 0) {
                $start = $thisMonthStartTime;
                $end = $tm;
                $month = L_('本月');
                $dateRange = date('m-d', $start) . L_('至') . date('m-d', $end);
            } else {
                $start = strtotime("-$i month", $thisMonthStartTime);
                $end = strtotime("+1 month", $start);
                $month = date('m月', $start);
                $dateRange = '';
            }
            if ($end < $nowUser['create_time']) {
                break;
            }

            //完成订单
            $where = [
                ['uid', '=', $uid],
                ['status', 'in', '5,6'],
                ['end_time', 'between', [$start, $end]]
            ];
            $finishCount = $supplyMod->where($where)->count();
            //配送里程
            $totalMiles = $supplyMod->where($where)->sum('distance');
            //取消订单
            $where = [
                ['uid', '=', $uid],
                ['status', '=', 0],
                ['start_time', 'between', [$start, $end]]
            ];
            $cancelCount = $supplyMod->where($where)->count();
            $rs[] = [
                'month' => $month,
                'date_range' => $dateRange,
                'finish_count' => $finishCount,
                'cancel_count' => $cancelCount,
                'total_miles' => $totalMiles
            ];
        }
        return $rs;
    }

    /**
     * 获取配送员配送历史订单明细
     * @param $uid
     * @param $status
     * @param $date
     * @author: 张涛
     * @date: 2020/9/17
     */
    public function getOrderLogByDate($uid, $status, $date)
    {
        $beginTime = strtotime($date);
        $endTime = $beginTime + 86400;
        $where = [
            ['uid', '=', $uid],
            ['start_time', 'between', [$beginTime, $endTime]]
        ];
        if ($status == 'finish') {
            $where[] = ['status', 'in', '5,6'];
        } else if ($status == 'cancel') {
            $where[] = ['deliver_status', '=', 0];
        } else if ($status == 'transfer') {
            $where[] = ['transfer_from_uid', '=', $uid];
            $where[] = ['transfer_status', '=', 0];
        } else {
            throw new Exception(L_('非法状态筛选'));
        }
        $orders = (new DeliverSupplyService())->getOrderLists($where,$uid);
        $deliverUserMod = new DeliverUser();
        $nowUser = $deliverUserMod->where('uid','=',$uid)->findOrEmpty()->toArray();
        foreach ($orders as $k => $v) {
            unset($orders[$k]['phone_lists'], $orders[$k]['btns']);
            //重置一下取货距离
            $pickAddress = $v['pick_address'];
            $distance = get_distance($nowUser['now_lat'], $nowUser['now_lng'], $pickAddress['lat'], $pickAddress['lng']);
            $pickAddress['miles'] = $distance >= 1000 ? round($distance / 1000, 2) . 'km' : $distance . 'm';
            $orders[$k]['pick_address'] = $pickAddress;

            if ($status == 'transfer') {
                $transferName = $deliverUserMod->where(['uid' => $v['transfer_to_uid']])->value('name');
                $orders[$k]['transfer_to_user'] = $transferName ?: '';
            } else {
                $orders[$k]['transfer_to_user'] = '';
            }
        }
        return $orders;
    }


    /**
     * 扫码收单订单状态监测
     * @param $uid
     * @param $content
     * @author: 张涛
     * @date: 2020/9/17
     */
    public function scanOrderCheck($uid, $content)
    {
        /*$arr = explode('_', $content);
        if (count($arr) != 2) {
            throw new Exception('二维码内容格式错误');
        }
        list($orderType, $realOrderid) = $arr;*/
        $orderType = 'shop';
        $realOrderid = $content;
        $deliverStatusDesc = ['1' => L_('待接单'), '2' => L_('已接单'), '3' => L_('已到店'), '4' => L_('已取货'), '5' => L_('已送达')];
        $rs = [];
        if ($orderType == 'shop') {
            //商城或者外卖订单
            $order = (new ShopOrderService())->getOrderInfo(['real_orderid' => $realOrderid]);
            if (empty($order)) {
                throw new Exception(L_('业务订单不存在'));
            }
            if ($order['paid'] != 1) {
                throw new Exception(L_('业务订单未支付'));
            }
            if (in_array($order['status'], [4, 5])) {
                throw new Exception(L_('当前订单已退款'));
            }
            $supply = (new DeliverSupplyService())->getOne(['item' => 2, 'order_id' => $order['order_id']]);
        } else {
            throw new Exception(L_('该业务二维码暂未适配'));
        }
        if (empty($supply)) {
            throw new Exception(L_('配送记录不存在'));
        }
        $confirmBtn = ['txt' => L_('确定'), 'action' => '', 'color' => '#343434'];
        $scanBtn = ['txt' => L_('继续扫码'), 'action' => '', 'color' => '#343434'];
        $keepBtn = ['txt' => L_('保持当前状态'), 'action' => '', 'color' => '#343434'];
        $grabBtn = ['txt' => L_('流转为下个状态'), 'action' => 'grab_order', 'color' => '#28A4F8'];
        $reportArriveStoreBtn = ['txt' => L_('流转为下个状态'), 'action' => 'report_arrive_store', 'color' => '#28A4F8'];
        $pickOrderBtn = ['txt' => L_('流转为下个状态'), 'action' => 'pick_order', 'color' => '#28A4F8'];
        $finishOrderBtn = ['txt' => L_('流转为下个状态'), 'action' => 'finish_order', 'color' => '#28A4F8'];
        $transferAndAcceptBtn = ['txt' => L_('确定'), 'action' => 'transfer_and_accept', 'color' => '#28A4F8'];

        $deliverStatusDesc = $deliverStatusDesc[$supply['deliver_status']] ?? L_('未知状态');
        $msg = L_("当前订单为X1状态，你是要继续流转为下个状态还是保持当前状态", ['X1' => $deliverStatusDesc]);
        if ($supply['deliver_status'] == 1) {
            $btn = [$keepBtn, $grabBtn];
        } else if ($supply['deliver_status'] == 2) {
            $btn = [$keepBtn, $reportArriveStoreBtn];
        } else if ($supply['deliver_status'] == 3) {
            $btn = [$keepBtn, $pickOrderBtn];
        } else if ($supply['deliver_status'] == 4) {
            $btn = [$keepBtn, $finishOrderBtn];
        } else if ($supply['deliver_status'] == 5) {
            $btn = [$scanBtn];
        }
        if ($supply['uid'] > 0 && $supply['uid'] != $uid && $supply['deliver_status'] != 5) {
            $msg = L_("操作失败，订单已被别人处理，当前订单为X1状态，", ['X1' => $deliverStatusDesc]);
            $btn = [$scanBtn, $transferAndAcceptBtn];
        }
        $rs = [
            'tip' => $msg,
            'supply_id' => $supply['supply_id'],
            'btns' => $btn
        ];
        return $rs;
    }


    /**
     * 扫码收单流转到下一个状态
     * @author: 张涛
     * @date: 2020/9/17
     */
    public function flowToNextStatus($uid, $supplyId, $action)
    {
        if ($action == 'grab_order') {
            $this->grabOrder($uid, $supplyId);
        } else if ($action == 'report_arrive_store') {
            $this->reportArriveStore($uid, $supplyId);
        } else if ($action == 'pick_order') {
            $this->pickOrder($uid, $supplyId);
        } else if ($action == 'finish_order') {
            $this->finishOrder($uid, $supplyId);
        } else if ($action == 'transfer_and_accept') {
            $supply = (new DeliverSupplyService())->getOne(['supply_id' => $supplyId]);
            $this->transfterOrder($supply['uid'], $supplyId, $uid);
            $this->acceptTransferOrder($uid, $supplyId);
        } else {
            throw new Exception(L_('非法流转状态操作'));
        }
    }

    /**
     *
     * @author: 张涛
     * @date: 2020/9/21
     */
    public function getRoute($uid)
    {
        $supplyMod = new DeliverSupply();
        $userMod = new DeliverUser();
        $supplyLists = $supplyMod->where([['uid', '=', $uid], ['deliver_status', 'IN', [2, 3, 4]]])->select()->toArray();
        $nowUser = $userMod->where('uid', $uid)->findOrEmpty()->toArray();
        if (empty($supplyLists)) {
            throw new Exception(L_('没有可规划线路的配送订单'));
        }
        if (empty($nowUser)) {
            throw new Exception(L_('配送员不存在'));
        }

        //坐标点对应的订单
        $passPoints = [];
        $allPoints = [];
        $allPointsInfo = [];
        $indexSupply = [];
        $tm = time();
        foreach ($supplyLists as $val) {
            if ($val['item'] == 2) {
                $order = (new ShopOrderService())->getOrderInfoForRoutePoint($val['order_id']);
            } else if ($val['item'] == 3) {
                $order = (new ServiceUserPublishService())->getOrderInfoForRoutePoint($val['order_id']);
                $fixExpectTime = strtotime($order['expect_use_time']);
                if ($val['appoint_time'] == 0) {
                    $val['appoint_time'] = $fixExpectTime;
                }
                if ($val['order_out_time'] == 0) {
                    $val['order_out_time'] = $fixExpectTime;
                }
            }

            if (!isset($order) || empty($order)) {
                continue;
            }
            if ($nowUser) {
                $pickDistance = get_distance($nowUser['now_lat'], $nowUser['now_lng'], $val['from_lnt'], $val['from_lat']);
                $order['pick_address']['miles'] = $pickDistance >= 1000 ? round($pickDistance / 1000, 2) . 'km' : $pickDistance . 'm';
            }
            $pickAddress = $order['pick_address'];
            $sendAddress = $order['user_address'];

            //展示x分钟送达等展示
            $sendAddress['time_tips'] = [
                'action' => 'send',
                'fetch_numer' => $val['fetch_number'],
                'left_second' => $val['appoint_time'] - $tm,
                'supply_id' => $val['supply_id']
            ];
            $pickAddress['time_tips'] = [
                'action' => 'pick',
                'fetch_numer' => $val['fetch_number'],
                'left_second' => $val['order_out_time'] - $tm,
                'supply_id' => $val['supply_id']
            ];
            $val['pick_address'] = $pickAddress;
            $val['user_address'] = $sendAddress;


            if ($val['deliver_status'] >= 3) {
                //已到店
                $passPoints[] = 'pick_' . $val['supply_id'];
            }

            $allPoints[] = 'pick_' . $val['supply_id'];
            $allPoints[] = 'send_' . $val['supply_id'];
            $allPointsInfo['pick_' . $val['supply_id']] = ['lng' => $val['from_lnt'], 'lat' => $val['from_lat']];
            $allPointsInfo['send_' . $val['supply_id']] = ['lng' => $val['aim_lnt'], 'lat' => $val['aim_lat']];

            $indexSupply[$val['supply_id']] = $val;
        }

        //配送员当前位置
        $nowLng = $nowUser['now_lng'];
        $nowLat = $nowUser['now_lat'];
        $pointsCount = count($allPoints);
        $removePoint = $passPoints;
        $route = [];
        for ($i = 0; $i < $pointsCount; $i++) {
            $bestDistance = null;
            $nextPoint = [];
            foreach ($allPoints as $v) {
                //送货地址要判断先取件才能送货
                list($type, $supplyId) = explode('_', $v);
                if ($type == 'send' && !in_array('pick_' . $supplyId, $passPoints)) {
                    continue;
                }

                $distance = get_distance($nowLat, $nowLng, $allPointsInfo[$v]['lng'], $allPointsInfo[$v]['lat']);
                if (is_null($bestDistance) || $distance < $bestDistance) {
                    $bestDistance = $distance;
                    $nextPoint = $v;
                }
            }
            if (empty($nextPoint)) {
                break;
            }
            $allPoints = array_diff($allPoints, [$nextPoint]);
            $route[] = $nextPoint;
            $passPoints[] = $nextPoint;
            $nowLat = $allPointsInfo[$nextPoint]['lng'];
            $nowLng = $allPointsInfo[$nextPoint]['lng'];
        }


        $newRoutePoints = [];
        foreach ($route as $v) {
            list($type, $supplyId) = explode('_', $v);
            if (in_array($v, $removePoint)) {
                continue;
            }
            $newRoutePoints[] = $type == 'pick' ? $indexSupply[$supplyId]['pick_address'] : $indexSupply[$supplyId]['user_address'];
        }

        //合并地址节点
        $uniqRoute = [];
        foreach ($newRoutePoints as $v) {
            $removeTimeTip = $v;
            unset($removeTimeTip['time_tips']);
            $lnglat = md5(var_export($removeTimeTip, true));
            if (isset($uniqRoute[$lnglat])) {
                $uniqRoute[$lnglat]['time_tips'][] = $v['time_tips'];
            } else {
                $removeTimeTip['time_tips'][] = $v['time_tips'];
                $uniqRoute[$lnglat] = $removeTimeTip;
            }
        }
        return array_values($uniqRoute);
    }

    /**
     * 获取今日订单完成量
     * @param $uid
     * @author: 张涛
     * @date: 2020/9/22
     */
    public function getTodayFinishCount($uid)
    {
        $beginTime = strtotime(date('Y-m-d'));
        $supplyMod = new DeliverSupply();
        //完成订单
        $where = [
            ['uid', '=', $uid],
            ['deliver_status', 'in', '5,6'],
            ['end_time', '>=', $beginTime]
        ];
        return $supplyMod->where($where)->count();
    }

    /**
     * 获取今日配送费
     * @param $uid
     * @author: 张涛
     * @date: 2020/9/22
     */
    public function getTodayDeliverFee($uid)
    {
        $beginTime = strtotime(date('Y-m-d'));
        $supplyMod = new DeliverSupply();
        //完成订单
        $where = [
            ['uid', '=', $uid],
//            ['deliver_status', 'in', '5,6'],
            ['finish_time', '>', 0],
            ['end_time', '>=', $beginTime]
        ];
        $fee = $supplyMod->where($where)->sum(\think\facade\Db::raw('`deliver_user_fee`+`tip_price`'));
        return get_format_number($fee);
    }

    /**
     * 获取本月好评数
     * @param $uid
     * @author: 张涛
     * @date: 2020/9/22
     */
    public function getMonthHighOpinion($uid)
    {
        $supplyMod = new DeliverSupply();
        $tm = time();
        $thisMonthStartTime = strtotime(date('Y-m', $tm) . '-01');
        $where = [
            ['uid', '=', $uid],
            ['score', '>', 3],
            ['end_time', '>', $thisMonthStartTime]
        ];
        return $supplyMod->where($where)->count();
    }


    /**
     * 上报配送员位置
     * @param $uid
     * @param $lat
     * @param $lng
     * @author: 张涛
     * @date: 2020/9/22
     */
    public function reportLocation($uid, $lat, $lng)
    {
		if(empty((float)$lat) || empty((float)$lng)) {
			fdump_api([$uid, $lat, $lng], 'deliver/reportLocation', 1);
			return;
		}
        $data = [
            'uid' => $uid,
            'lng' => $lng,
            'lat' => $lat,
            'create_time' => time(),
            'create_ip' => \think\facade\Request::ip()
        ];
        \think\facade\Db::name('deliver_user_location_log')->insert($data);
        (new DeliverUser())->where([['uid', '=', $uid], ['status', '<>', DeliverUser::STATUS_DEL]])->update(['now_lng' => $lng, 'now_lat' => $lat, 'last_time' => time()]);
    }

    /**
     * 配送员手机号处理
     * @param $phone
     * @param $countryType
     * @return string
     * @author: 张涛
     * @date: 2020/10/24
     */
    public function getShowPhone($phone, $countryType)
    {
        $showPhone = '';
        if ($phone) {
            if (strlen($phone) == 11) {
                $showPhone = substr($phone, 0, 3) . '****' . substr($phone, 7, 4);
            } else if (strlen($phone) == 10) {
                $showPhone = substr($phone, 0, 2) . '****' . substr($phone, 7, 4);
            } else if (strlen($phone) == 9) {
                $showPhone = substr($phone, 0, 2) . '****' . substr($phone, 7, 3);
            } else if (strlen($phone) == 8) {
                $showPhone = substr($phone, 0, 2) . '***' . substr($phone, 7, 3);
            } else if (strlen($phone) == 7) {
                $showPhone = substr($phone, 0, 2) . '***' . substr($phone, 7, 2);
            } else {
                $showPhone = $phone;
            }
            if ($countryType) {
                $showPhone = '+' . $countryType . ' ' . $phone;
            }
        }
        return $showPhone;
    }

    /**
     * 更新配送员device_token字段
     * @author: 张涛
     * @date: 2020/11/20
     */
    public function updateDeviceToken($where, $deviceToken)
    {
        return (new DeliverUser())->where($where)->update(['device_token' => $deviceToken]);
    }


    /**
     * 我的钱包
     * @param $uid
     * @author: 张涛
     * @date: 2020/12/2
     */
    public function myWallet($uid)
    {
        $user = (new DeliverUser())->where('uid', $uid)->find();
        if (empty($user)) {
            throw new Exception(L_('配送员不存在'));
        }
        //银行卡列表
        $bank_list = (new UserWithdrawAccountList())->field('*')->where([['from', '=', 2], ['type', '=', 0], ['pay_id', '=', $uid]])->select()->toArray();
        foreach ($bank_list as &$item){
            $item['remark_info'] = '【' . $item['remark'] . '】' . $item['account'] . ($item['is_default'] ? '【默认】' : '');
        }
        $select_type = [];
        if(cfg('open_deliver_wallet')){
            if(cfg('company_weixin_pay')){
                $select_type[] =  [
                    'label' => L_('微信'),
                    'value' => 'wechat',
                ];
            }
            if(cfg('company_alipay_pay')){
                $select_type[] =  [
                    'label' => L_('支付宝'),
                    'value' => 'alipay',
                ];
            }
            if(cfg('company_bank_pay')){
                $select_type[] =  [
                    'label' => L_('银行卡'),
                    'value' => 'bank',
                ];
            }
            $open_deliver_wallet = cfg('open_deliver_wallet');
            if($user['user_type'] && $open_deliver_wallet == 1){ //未开启简直配送员提现
                $select_type = [];
            }
            if(!$user['user_type'] && $open_deliver_wallet == 2){ //未开启全职配送员提现
                $select_type = [];
            }
        }
        //获取省份
        $area_list = (new AreaService())->getSelectProvinceAndCity(['area_id'=>0]);
        foreach ($area_list as &$item){
            if(!empty($item['children'])){
                foreach ($item['children'] as &$val){
                    $val['value'] = explode('-', $val['value'])[1];
                }
            }
        }
        return [
            'money' => get_format_number($user->money),
            'tip_money' => get_format_number($user->tip_money),
            'can_withdraw_money' => get_format_number($user->money),
            'service_charge_percent' => cfg('company_pay_user_percent'),
            'bank_list' => $bank_list ?: [],
            'select_type' => $select_type,
            'area_list' => $area_list,
        ];
    }

    /**
     * 添加银行卡
     * @param $params
     * @return bool
     * @throws Exception
     */
    public function createBankCard($params){
        if(empty($params['account'])){
            throw new \think\Exception(L_('银行卡号不能为空'));
        }
        if(empty($params['account_name'])){
            throw new \think\Exception(L_('持卡人姓名不能为空'));
        }
        
        if(!$params['province_id'] && $params['city_id']){
            throw new \think\Exception(L_('请选择开户行省、市信息！'));
        }
        
        $province_info = (new Area())->where(['area_id'=>$params['province_id'],'area_type'=>1,'is_open'=>1])->findOrEmpty()->toArray();
        if(!$province_info){
            throw new \think\Exception(L_('省份有误！'));
        }
        $city_info = (new Area())->where(['area_id'=>$params['city_id'],'area_type'=>2,'is_open'=>1])->findOrEmpty()->toArray();
        if(!$city_info){
            throw new \think\Exception(L_('城市有误！'));
        }
        
        $data_companypay['from']     = 2;
        $data_companypay['type']     = 0;
        $data_companypay['pay_id']   = $params['uid'];
        $data_companypay['account_name'] = $params['account_name'];
        $data_companypay['account'] = $params['account'];
        $data_companypay['is_default'] = $params['is_default'];
        $data_companypay['add_time'] = time();
        $data_companypay['remark']    = $params['remark'];
        $data_companypay['province_id']  = $params['province_id'];
        $data_companypay['city_id']    = $params['city_id'];
        $data_companypay['branch']    = $params['branch'];

        Db::startTrans();
        try {
            if ($data_companypay['is_default']) {
                (new UserWithdrawAccountList())->where(['pay_id' => $params['uid'], 'from' => 2, 'type' => 0])->save(['is_default' => 0]);
            }
            if ($params['bank_id']) {
                (new UserWithdrawAccountList())->where(array('id' => $params['bank_id']))->save($data_companypay);
            } else {
                (new UserWithdrawAccountList())->save($data_companypay);
            }
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 删除银行卡
     * @param $params
     * @return bool
     * @throws Exception
     */
    public function deleteBankCard($params){
        $uid = $params['uid'];
        $bank_id = $params['bank_id'];
        $where = [
            'from' => 2,
            'type' => 0,
            'id'   => $bank_id,
            'pay_id'   => $uid,
        ];
        try {
            (new UserWithdrawAccountList())->where($where)->delete();
        }catch (\Exception $e){
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 收入明细
     * @author: 张涛
     * @date: 2020/12/2
     */
    public function incomeDetail($param)
    {
        $where = [];
        if (isset($param['date']) && $param['date']) {
            $unix = strtotime($param['date']);
            $where[] = ['create_time', 'between', [$unix, $unix + 86400]];
        }
        if (isset($param['type']) && $param['type'] > 0) {
            $where[] = ['type', '=', $param['type']];
        }
        if (isset($param['uid'])) {
            $where[] = ['uid', '=', $param['uid']];
        }
        $income = $expend = 0;
        $moneyMod = new DeliverUserMoneyLog();

        $income = $moneyMod->where($where)->where('income', '=', 1)->where('is_valid', '=', 1)->sum('money');
        $expend = $moneyMod->where($where)->where('income', '=', 2)->where('is_valid', '=', 1)->sum('money');
        $lists = $moneyMod->where($where)->page($param['page'], $param['pageSize'])->order('id DESC')->select()->toArray();
        $retval = [];
        foreach ($lists as $v) {
            $retval[] = [
                'note' => $v['note'],
                'date' => date('Y.m.d H:i', $v['create_time']),
                'income' => $v['income'],
                'money_txt' => $v['is_valid'] == 0 ? $v['money'] : (($v['income'] == 1 ? '+' : '-') . $v['money'])
            ];
        }
        return ['total_income' => $income, 'total_expend' => $expend, 'lists' => $retval];
    }


    /**
     * 申请提现
     * @param $param
     * @author: 张涛
     * @date: 2020/12/3
     */
    public function applyWithdraw($uid, $param)
    {
        $trueName = $param['truename'] ?? '';
        $money = $param['money'] ?? 0;
        $type = $param['type'] ?? '';
        $account = $param['account'] ?? '';
        $bank_id = $param['bank_id'] ?? 0;
        $typeNames = ['alipay' => L_('支付宝'), 'wechat' => L_('微信'), 'bank' => L_('银行卡')];
        if (empty($trueName)) {
            throw new Exception(L_('提现人真实姓名不能为空'));
        }
        if ($money < 0.01) {
            throw new Exception('提现金额不能小于0.01元');
        }
        if (empty($type) || !in_array($type, ['alipay', 'wechat', 'bank'])) {
            throw new Exception(L_('提现方式非法'));
        }
        $deliverUserMod = new DeliverUser();
        $deliverUser = $deliverUserMod->where('uid', $uid)->findOrEmpty()->toArray();
        if (empty($deliverUser)) {
            throw new Exception(L_('配送员不存在'));
        } else if (empty($deliverUser['phone'])) {
            throw new Exception(L_('配送员未绑定手机号'));
        } else if (!$this->isOpenWallet($deliverUser)) {
            throw new Exception(L_('禁止提现'));
        }

        $deliver_minimum_withdrawal_amount = cfg('deliver_minimum_withdrawal_amount') ?: 0;
        if ($deliver_minimum_withdrawal_amount > 0 && $money < $deliver_minimum_withdrawal_amount) {
            throw new Exception(L_('最低提现金额为：X1X2', ['X1' => cfg('deliver_minimum_withdrawal_amount'), 'X2' => cfg('Currency_txt')]));
        }
        $remark = '';
        $user_money_desc = L_("提现至X1", ['X1' => $typeNames[$type]]);
        if($type == 'bank'){
            $bank_info = (new UserWithdrawAccountList())->where(['from' => 2, 'pay_id' => $uid, 'id' => $bank_id])->findOrEmpty()->toArray();
            if (empty($bank_info['account']) || empty($bank_info['account_name']) || empty($bank_info['remark'])) {
                throw new Exception(L_('银行账号不全！'));
            }
            $account = $bank_info['account'];
            $remark = '开户名：' . $bank_info['account_name'] . ',开户行：' . $bank_info['remark'];
            if($bank_info['province_id']){
                $province = (new Area())->where(['area_id' => $bank_info['province_id']])->value('area_name');
                $city = (new Area())->where(['area_id' => $bank_info['city_id']])->value('area_name');

                $remark .= '，' . L_('开户省市：') . $province . $city . '，' . L_('开户网点：') . $bank_info['branch'];
            }

            $user_money_desc .= L_("，账号：X1X2，姓名：X3", [ 'X1' => $bank_info['remark'], 'X2' => $bank_info['account'], 'X3' => $bank_info['account_name']]);
        }else{
            $user = (new \app\common\model\db\User())->where('phone', '=', $deliverUser['phone'])->findOrEmpty()->toArray();
            if (empty($user)) {
                throw new Exception(L_('配送员未关联用户，禁止提现'));
            }
        }
        $user_money_desc .= L_("-审核中");

        if ($money > $deliverUser['money']) {
            throw new Exception(L_('余额不足'));
        }

        // 开启事务
        Db::startTrans();

        $data = [
            'uid' => $uid,
            'truename' => $trueName,
            'money' => $money,
            'type' => $type,
            'account' => $account,
            'apply_time' => time(),
            'status' => 0,
            'tip_money' => $deliverUser['tip_money'] > $money ? $money : $deliverUser['tip_money'],
            'remark' => $remark,
        ];
        $data['log_id'] = $this->useMoney($uid, $money, $user_money_desc, 3, 'withdraw', 0);

        $percent = cfg('company_pay_user_percent');
        
        // 插入提现记录
        $withdrawId = Db::name('deliver_user_withdraw')->insertGetId($data);
        if ($withdrawId > 0) {
            $leftTipPrice = $deliverUser['tip_money'] > $money ? $deliverUser['tip_money'] - $money : 0;

            // 扣除提现金额
            $res = Db::name('deliver_user')->where('uid', $uid)->dec('money', $money)->inc('withdraw_money', $money)->update(['tip_money' => $leftTipPrice]);
            if (!$res) {
                Db::rollback();// 回滚事务
                throw new Exception(L_('申请失败！'));
            }

            if ($type == 'alipay') {
                $withdrawData = [
                    'type' => 'user',
                    'pay_type' => 1,
                    'pay_id' => $user['uid'],
                    'account' => $account,
                    'name' => $trueName,
                    'truename' => $trueName,
                    'remark' => '',
                    'phone' => $deliverUser['phone'],
                    'money' => bcmul($money * ((100 - $percent) / 100), 100),
                    'old_money' => $money * 100,
                    'desc' => "用户提现对账订单|用户ID " . $user['uid'] . " |转账 " . $money . cfg('Currency_txt') . "",
                    'status' => 0,
                    'add_time' => time(),
                    'deliver_withdraw_id' => $withdrawId
                ];
                if ($percent > 0) {
                    $withdrawData['desc'] .= '|手续费 ' . round((($withdrawData['old_money'] - $withdrawData['money']) / 100), 2) . ' 比例 ' . $percent . '%';
                }

                // 插入提现记录
                $withdrawId = Db::name('withdraw_list')->insertGetId($withdrawData);
                if(!$withdrawId){
                    Db::rollback();// 回滚事务
                    throw new Exception(L_('申请失败！'));
                }
                
                if(cfg('open_real_time_withdrawal')){// 开启实时提现立马到账不需审核
                    $param['money'] = round($withdrawData['money']/100,2);
                    $param['account'] = $withdrawData['account'];
                    $param['truename'] = $withdrawData['truename'];
                        
                    $alipayObj = new \withdraw\Alipay();
                    $param['partner_trade_no'] = date('YmdHis').$uid;
                    $result = $alipayObj->withdraw($param);
                    $saveData['third_id'] = $result['pay_fund_order_id'] ?? '';//第三放订单id

                    if($result['error']){
					    Db::rollback();// 回滚事务
                        throw new Exception($result['msg']);
					}

					// 修改状态
                    $withdrawData['status'] = 1;

                    $this->handleWithDrawAfter($withdrawData['deliver_withdraw_id'],1,['reason'=>L_('自动提现')]);
                }

                // 更新提现记录状态
                $res = Db::name('withdraw_list')->where(['id'=>$withdrawId])->save($withdrawData);

            } else if ($type == 'wechat') {
                $withdrawData = [
                    'pay_type' => 'user',
                    'pay_id' => $user['uid'],
                    'openid' => $user['openid'],
                    'nickname' => $trueName,
                    'phone' => $deliverUser['phone'],
                    'money' => round($money * ((100 - $percent) / 100), 2) * 100,
                    'desc' => "用户提现{$money}" . cfg('Currency_txt') . "，用户ID: " . $user['uid'],
                    'status' => 0,
                    'add_time' => time(),
                    'deliver_withdraw_id' => $withdrawId
                ];
                $withdrawData['service_charges'] = $money * 100 - $withdrawData['money'];
                if ($percent > 0) {
                    $withdrawData['desc'] .= '|手续费 ' . get_format_number($withdrawData['service_charges'] / 100) . ' 比例 ' . $percent . '%';
                }

                $payId = (new \app\common\model\service\companypay\CompanypayService())->add($withdrawData);
                if(cfg('open_real_time_withdrawal')){// 开启实时提现立马到账不需审核
                    $param['money'] = $withdrawData['money'];
                    $param['openid'] = $user['openid'];
                    $param['name'] = $trueName;

                    //开启了V3支付并且设置了V3秘钥
                    if(cfg('withdraw_open_apiv3') == 1 && !empty(cfg('withdraw_weixin_apiv3'))){
                        $param['partner_trade_no'] = date('YmdHis').$uid;
                        $param['withdraw_name'] = "骑手提现";
                        try{
                            invoke_cms_model('Pay/weixinV3Pay',['param'=>$param], true);
                        }catch (\Exception $e){
                            Db::rollback();// 回滚事务
                            throw_exception($e->getMessage());
                        }
                    }else{
                        $weixinObj = new \withdraw\Weixin();
                        $param['partner_trade_no'] = date('YmdHis').$uid;
                        $result = $weixinObj->withdraw($param);
                    }

                    if($result['error']){
					    Db::rollback();// 回滚事务
                        throw new Exception($result['msg']);
					}

					// 修改状态
                    $this->handleWithDrawAfter($withdrawData['deliver_withdraw_id'],1,['reason'=>L_('实时提现')]);
                    (new \app\common\model\service\companypay\CompanypayService())->updateThis(['pigcms_id'=>$payId],['status'=>1]);
                }
            } else if ($type == 'bank') {
                $withdrawData = [
                    'type' => 'deliver',
                    'pay_type' => 0,
                    'pay_id' => $uid,
                    'account' => $account,
                    'name' => $trueName,
                    'truename' => $trueName,
                    'remark' => $remark,
                    'phone' => $deliverUser['phone'],
                    'money' => bcmul($money * ((100 - $percent) / 100), 100),
                    'old_money' => $money * 100,
                    'desc' => "用户提现对账订单|配送员ID " . $uid . " |转账 " . $money . cfg('Currency_txt') . "",
                    'status' => 0,
                    'add_time' => time(),
                    'deliver_withdraw_id' => $withdrawId
                ];
                if ($percent > 0) {
                    $withdrawData['desc'] .= '|手续费 ' . round((($withdrawData['old_money'] - $withdrawData['money']) / 100), 2) . ' 比例 ' . $percent . '%';
                }

                // 插入提现记录
                $withdrawId = Db::name('withdraw_list')->insertGetId($withdrawData);
                if(!$withdrawId){
                    Db::rollback();// 回滚事务
                    throw new Exception(L_('申请失败！'));
                }
            }
            
            Db::commit();// 完成事务
        }else{
            Db::rollback();// 回滚事务
            throw new Exception(L_('申请失败！'));
        }
        return true;
    }

    /**
     * 审核提现后回调
     * @param $withdrawId  提现记录ID
     * @param $status  审核状态  1：通过  2：驳回
     * @param $params  额外其他参数
     * @author: 张涛
     * @date: 2020/12/15
     */
    public function handleWithDrawAfter($withdrawId, $status, $params = [])
    {
        if ($withdrawId < 1 || !in_array($status, [1, 2])) {
            return true;
        }
        $drawMod = new DeliverUserWithdraw();
        $deliverUserMod = new DeliverUser();
        $moneyLogMod = new DeliverUserMoneyLog();

        $withdrawRecord = $drawMod->where('id', $withdrawId)->findOrEmpty()->toArray();
        if (empty($withdrawRecord) || $withdrawRecord['status'] != 0) {
            return true;
        }

        //更新提现记录
        $withdrawData = ['status' => $status, 'handle_time' => time()];
        isset($params['admin_id']) && $withdrawData['admin_id'] = $params['admin_id'];
        isset($params['reason']) && $withdrawData['reason'] = $params['reason'];
        $drawMod->where('id', $withdrawId)->update($withdrawData);

        $log = $moneyLogMod->where('id', $withdrawRecord['log_id'])->findOrEmpty()->toArray();
        if ($log) {
            $splitNote = explode('-', $log['note']);
            if ($status == 1) {
                //通过
                $moneyLogMod->where('id', $withdrawRecord['log_id'])->update(['note' => $splitNote[0] ?? $log['note']]);
                $deliverUserMod->where('uid', $withdrawRecord['uid'])->dec('withdraw_money', $withdrawRecord['money'])->update();
            } else {
                //驳回
                $moneyLogMod->where('id', $withdrawRecord['log_id'])->update(['is_valid' => 0, 'note' => ($splitNote[0] ?? $log['note']) . '-提现失败']);
                $deliverUserMod->where('uid', $withdrawRecord['uid'])->dec('withdraw_money', $withdrawRecord['money'])->inc('money', $withdrawRecord['money'])->inc('tip_money', $withdrawRecord['tip_money'])->update();
            }
        }
    }

    /**
     * 判断是否绑定了微信
     * @param $param
     * @author: 张涛
     * @date: 2020/12/3
     */
    public function isBindWechat($uid)
    {
        $user = (new DeliverUser())->where('uid', $uid)->find();
        if (empty($user)) {
            throw new Exception(L_('配送员不存在'));
        }
        if (empty($user['phone'])) {
            throw new Exception(L_('配送员未绑定手机号'));
        }

        $openid = (new \app\common\model\db\User())->where('phone', '=', $user['phone'])->value('openid');
        return $openid ? true : false;
    }

    /**
     * 判断是否关联平台用户
     * @param $uid
     * @author: 张涛
     * @date: 2020/12/16
     */
    public function isBindUser($uid)
    {
        $user = (new DeliverUser())->where('uid', $uid)->find();
        if (empty($user)) {
            throw new Exception(L_('配送员不存在'));
        }
        if (empty($user['phone'])) {
            throw new Exception(L_('配送员未绑定手机号'));
        }

        $userId = (new \app\common\model\db\User())->where('phone', '=', $user['phone'])->value('uid');
        return $userId > 0 ? true : false;
    }

    /**
     * 增加配送员余额
     * @param $uid  配送员ID
     * @param $money  金额
     * @param $desc  描述
     * @param $tipMoney  小费
     * @param $tipDesc  小费描述
     * @author: 张涛
     * @date: 2020/12/11
     */
    public function addMoney($uid, $businessType, $businessId, $money = 0, $desc = '', $tipMoney = 0, $tipDesc = '')
    {
        $tm = time();
        $totalMoney = $money + $tipMoney;
        if ($totalMoney <= 0) {
            return false;
        }
        $deliverUser = new DeliverUser();
        if ($deliverUser->where('uid', $uid)->inc('money', $totalMoney)->inc('tip_money', $tipMoney)->update()) {
            $log = [];
            if ($money > 0) {
                $log[] = [
                    'uid' => $uid,
                    'income' => 1,
                    'money' => $money,
                    'note' => $desc,
                    'type' => 1,
                    'business_type' => $businessType,
                    'business_id' => $businessId,
                    'create_time' => $tm
                ];
            }
            if ($tipMoney > 0) {
                $log[] = [
                    'uid' => $uid,
                    'income' => 1,
                    'money' => $tipMoney,
                    'note' => $tipDesc,
                    'type' => 2,
                    'business_type' => $businessType,
                    'business_id' => $businessId,
                    'create_time' => $tm
                ];
            }
            (new DeliverUserMoneyLog())->insertAll($log);
            return true;
        } else {
            return false;
        }
    }


    /**
     * 减金额
     * @param $uid
     * @param int $money
     * @param string $desc
     * @param string $businessType
     * @param int $businessId
     * @author: 张涛
     * @date: 2020/12/15
     */
    public function useMoney($uid, $money = 0, $desc = '', $type = 1, $businessType = '', $businessId = 0)
    {
        $log = [
            'uid' => $uid,
            'income' => 2,
            'money' => $money,
            'note' => $desc,
            'type' => $type,
            'business_type' => $businessType,
            'business_id' => $businessId,
            'create_time' => time()
        ];
        return (new DeliverUserMoneyLog())->insertGetId($log);
    }

    /**
     * 检查身份证号是否在使用
     * @param $cardNumber
     * @author: 张涛
     * @date: 2020/12/14
     */
    public function checkCardNumberInUse($cardNumber)
    {
        $id = (new DeliverUser())->where([['card_number', '=', $cardNumber], ['status', '<>', 4]])->value('uid');
        return $id > 0 ? true : false;
    }


    /**
     * 登出
     * @param $uid
     * @return DeliverUser
     * @author: 张涛
     * @date: 2020/12/17
     */
    public function logout($uid)
    {
        return (new DeliverUser())->where('uid', $uid)->update(['last_time' => 0]);
    }

    /**
     * 根据deviceid登出
     * @author: 张涛
     * @date: 2021/04/16
     */
    public function setLogoutByDeviceId($deviceId, $expectUid = 0)
    {
        $where = [['device_id', '=', $deviceId]];
        if ($expectUid) {
            $where[] = ['uid', '<>', $expectUid];
        }
        (new DeliverUser())->where($where)->update(['last_time' => 0]);
    }

    /**
     * 判断是否有配送范围内的配送员
     * @param $lat
     * @param $lng
     * @param $horseman_type //1-平台配送员，2-商家配送员
     * @param $mer_id //商家id（$horseman_type为2时必传）
     * @return mixed
     * @author: 张涛
     * @date: 2021/01/05
     */
    public function isPositionInDeliverRange($lat, $lng, $horseman_type = 1, $mer_id = 0)
    {
        $ver = isMysqlVer8();
        $userMod = new DeliverUser();
        $where = "`group`={$horseman_type} AND `status`=1 AND `is_notice`=0 AND `last_time`<>0";
        if($horseman_type==2){
            $where .= " AND `mer_id` = {$mer_id}";
        }else{
            $where .= " AND ((`delivery_range_type`=0 AND ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`lat`*PI()/180)*POW(SIN(({$lng}*PI()/180-`lng`*PI()/180)/2),2)))*1000) <= `range`*1000)";
            if ($ver) {
                $where .= " OR (`delivery_range_type`=1 AND MBRContains(ST_PolygonFromText(`delivery_range_polygon`),ST_PolygonFromText('Point({$lng} {$lat})'))>0))";
            } else {
                $where .= " OR (`delivery_range_type`=1 AND MBRContains(PolygonFromText(`delivery_range_polygon`),PolygonFromText('Point({$lng} {$lat})'))>0))";
            }
        }
        //问题：FUNCTION PolygonFromText does not exist
        //原因：在mysql8.0+ 之后所有空间数据的操作函数的命名统一在前面加ST，废弃原来操作空间数据的函数名
        //解决：PolygonFromText ==> ST_PolygonFromText
        $list = $userMod->whereRaw($where)->findOrEmpty()->toArray();
        return $list ? true : false;
    }

    /**
     * 帮我送语音发单完善信息
     *
     * @param array $params
     * @return void
     * @date: 2021/08/26
     */
    public function perfectInfo(array $params)
    {
        $uid = $params['uid'] ?? 0;
        $supplyId = $params['supply_id'] ?? 0;
        $long = $params['long'] ?? 0;
        $lat = $params['lat'] ?? 0;
        $address = $params['address'] ?? '';
        $detail = $params['detail'] ?? '';
        $name = $params['name'] ?? '';
        $phone = $params['phone'] ?? '';
        $goodsCategory = $params['goods_category'] ?? '';
        $weight = $params['weight'] ?? '';

        $supplyMod = new DeliverSupply();
        $servicePubMod = new ServiceUserPublish();
        $giveMod = new ServiceUserPublishGive();

        $thisSupply = $supplyMod->where('supply_id', $supplyId)->where('uid', $uid)->where('item', 3)->find();
        if (empty($thisSupply)) {
            throw new Exception(L_('配送单不存在'));
        }
        if ($thisSupply->aim_site) {
            throw new Exception(L_('配送单已完善信息'));
        }
        $thisServicePublish = $servicePubMod->where('publish_id', $thisSupply->order_id)->find();
        if (empty($thisServicePublish)) {
            throw new Exception(L_('跑腿单不存在'));
        }
        $thisGiveOrder = $giveMod->where('publish_id', $thisSupply->order_id)->find();
        if (empty($thisGiveOrder)) {
            throw new Exception(L_('帮我送订单不存在'));
        }

        //更新跑腿单
        include_once app()->getRootPath() . '../cms/Lib/ORG/ticket.class.php';
        $reqDomain = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        if ($reqDomain != cfg('site_url')) {
            cfg('site_url', $reqDomain);
        }
        $api = cfg('site_url') . '/plugin.php?plugin=paotui&c=Paotui&a=save_voice_order';
        $req = [
            'cid' => $thisServicePublish->cid,
            'publish_id' => $thisSupply->order_id,
            'end_adress_detail' => $address . ' ' . $detail,
            'end_adress_lng' => $long,
            'end_adress_lat' => $lat,
            'end_adress_name' => $name,
            'end_adress_phone' => $phone,
            'goods_catgory' => $goodsCategory,
            'weight' => $weight,
            'Device-Id' => 'from_deliver',
            'ticket' => \Ticket::create($thisSupply->uid, 'from_deliver', true)['ticket'] ?? ''
        ];
        $result = \net\Http::curlPostOwnWithHeader($api, $req, [], 30);
        $result = json_decode($result, true);
        if ($result['errorCode'] != 0) {
            throw new Exception($result['errorMsg']);
        }

        $thisGiveOrder = $giveMod->where('publish_id', $thisSupply->order_id)->find();
        $nowUser = (new DeliverUserService())->getOneUser('uid=' . $thisSupply->uid);
        //更新配送单
        $thisSupply->aim_site = $address . ' ' . $detail;
        $thisSupply->aim_lnt = $long;
        $thisSupply->aim_lat = $lat;
        $thisSupply->distance = getDistance($thisSupply['from_lat'], $thisSupply['from_lnt'], $lat, $long);
        $thisSupply->freight_charge = get_format_number($thisGiveOrder->basic_distance_price + $thisGiveOrder->weight_price + $thisGiveOrder->distance_price);//计算基础配送费
        $thisSupply->deliver_user_fee = $thisSupply->freight_charge - ($thisSupply->freight_charge * ($nowUser['take_percent'] / 100));//配送员所得费用（扣除平台抽成）
        $thisSupply->save();
        return $result['result'];
    }

    /**
     * 实名认证
     * @author: 汪晨
     * @date: 2021/09/16
     */
    public function deliverRealAuthe($uid)
    {
        // 获取配送员信息
        $deliverUserFind = (new DeliverUser())->where(['uid'=>$uid])->field('uid,name,phone,card_number,face_recognition_images')->find();
        if (empty($deliverUserFind)) {
            throw new Exception(L_('配送员不存在'));
        }
        // 判断是否已认证
        if($deliverUserFind['face_recognition_images']){
            $deliverUserFind['is_face'] = 1;
        }else{
            $deliverUserFind['is_face'] = 0;
            $deliverUserFind['face_recognition_images'] = '';
        }
        // 隐藏身份证号
        if(strlen($deliverUserFind['card_number']) == 15){
            $card_number = substr_replace($deliverUserFind['card_number'],"***********",2,11);
        }elseif(strlen($deliverUserFind['card_number']) == 18){
            $card_number = substr_replace($deliverUserFind['card_number'],"**************",2,14);
        }else{
            $number = strlen($deliverUserFind['card_number']);
            if($number > 4){
                $hide_number = $number - 4;
                $asterisk = '';
                for($i=0;$i<$hide_number;$i++){
                    $asterisk = $asterisk.'*';
                }
                $card_number = substr_replace($deliverUserFind['card_number'],$asterisk,2,$hide_number);
            }else{
                $card_number = $deliverUserFind['card_number'];
            }
        }
        $deliverUserFind['card_number'] = $card_number;
        return $deliverUserFind;
    }

    /**
     * 人脸注册、编辑、查找
     * @param array $param
     * @param $type 1=注册，2=编辑，3=登陆查找，4=删除
     * @param $uid 用户uid（注册、编辑时必传）
     * @param $img 查找图片地址(登陆必传)
     */
    public function faceOperate($param)
    {
        switch ($param['type']) {
            case 1:
                $res = invoke_cms_model('House_face_img/deliver_baidu_face_add', ['uid' => $param['uid']]);
                break;
            case 2:
                $res = invoke_cms_model('House_face_img/deliver_baidu_face_edit', ['uid' => $param['uid']]);
                break;
            case 3:
                $res = invoke_cms_model('House_face_img/deliver_baidu_face_search', ['img_url' => $param['img']]);
                break;
            case 4:
                $res = invoke_cms_model('House_face_img/deliver_baidu_face_del', ['uid' => $param['uid']]);
                break;
        }
        fdump(['param' => $param, 'res' => $res], 'deliver_face_log', 1);
        return $res['retval'];
    }

    /**
     * 实名认证人脸识别图片
     * @author: 汪晨
     * @date: 2021/09/16
     */
    public function deliverRealAutheImg($param)
    {
        // 人脸识别图片
        $deliverUserFind = (new DeliverUser())->where(['uid'=>$param['uid']])->save(['face_recognition_images'=>$param['face_recognition_images']]);
        return $deliverUserFind;
    }

    /**
     * 检测注销
     */
    public function logoffCheck($uid)
    {
        $deliverUserMod = new DeliverUser();
        $where = [
            'uid'   =>  $uid
        ];
        $deliverUser = $deliverUserMod->getOne($where, 'uid,group,name,openid,is_notice,phone,status,phone_country_type,user_type,money');
        if(!$deliverUser){
            throw new \think\Exception('用户不存在！');
        }
        if($deliverUser->status != 1){
            throw new \think\Exception('用户不存在或已被禁用：'.$deliverUser->uid);
        }
        $msg = [];
        $ckeck = true;
        //检测余额
        if ($this->isOpenWallet($deliverUser->toArray())){
            if($deliverUser->money > 0){
                $msg[] = [
                    'status'    =>  0,
                    'title'     =>  '账号余额未结算',
                    'msg'       =>  '钱包里尚有预约未消费或者未提现'
                ];
                $ckeck = false;
            }else{
                $msg[] = [
                    'status'    =>  1,
                    'title'     =>  '账号余额未结算',
                    'msg'       =>  '无'
                ];
            }
        }


        //检测订单
        $currentOrderCount = $this->getCurrentCount($uid);
        if($currentOrderCount > 0){
            $msg[] = [
                'status'    =>  0,
                'title'     =>  '订单未完成',
                'msg'       =>  '账号尚有订单未完成'
            ];
            $ckeck = false;
        }else{
            $msg[] = [
                'status'    =>  1,
                'title'     =>  '订单未完成',
                'msg'       =>  '无'
            ];
        }
  
        $content = '<p>注销前请认真阅读以下重要提醒。账号注销后，您将无法再使用该账号，包括但不限于: </p><p>1.无法登录、使用账号，并移除该账号下所有登录方式；</p><p>2.账号以留存的信息将完全清空且无法找回；</p><p>3.移除该账号下的实名认证信息；</p><p>4.该账号下的个人资料和历史信息都将无法找回；</p><p>5.账号中所有的资产及权益被清。</p>';
        return [
            'check'     =>  $ckeck,
            'list'      =>  $msg,
            'content'   =>  $content
        ];

    }


    /**
     * 注销账号
     */
    public function logoffUser($uid)
    {
        $check = $this->logoffCheck($uid);
        if($check['check']){
            (new DeliverUser())->where('uid', $uid)->update(['status'=>4]);
        }else{
            throw new \think\Exception('该账号无法注销！');
        }
        return true;
    }


    /**
     * 检查是否多设备登录
     *
     * @param int $uid
     * @param string $currentDeviceId
     * @return void
     * @author: zt
     * @date: 2023/03/20
     */
    public function detectMultiDevicesLogin($uid, $currentDeviceId)
    {
        $thisUser = (new DeliverUser())->where(['uid' => $uid])->find();

        if ($thisUser && $thisUser->device_id != $currentDeviceId) {
            //多设备登录了
            return true;
        }
        return false;
    }
}
