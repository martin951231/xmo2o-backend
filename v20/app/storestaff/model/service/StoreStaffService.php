<?php
/**
 * 店员
 * Author: hengtingmei
 * Date Time: 2020/12/09 16:08
 */

namespace app\storestaff\model\service;
use app\common\model\service\AppPushMsgService;
use app\common\model\service\config\AppapiAppConfigService;
use app\common\model\service\send_message\WebPushMsgService;
use app\foodshop\model\service\store\MerchantStoreFoodshopService;
use app\merchant\model\db\CardNewDepositGoodsBindGoods;
use app\merchant\model\db\CardNewDepositGoodsBindUser;
use app\merchant\model\db\CardNewDepositGoodsVerification;
use app\merchant\model\service\MerchantService;
use app\merchant\model\service\MerchantStoreService;
use app\merchant\model\service\storeImageService;
use app\merchant\model\service\storestaff\MenuService;
use app\merchant\model\service\storestaff\MerchantStoreStaffService;
use token\Token;
use app\merchant\model\db\MerchantStoreStaff;
use app\shop\model\service\order\ShopOrderService;
class StoreStaffService {
    public $merchantStoreStaffService = null;
    public function __construct()
    {
        $this->merchantStoreStaffService = new MerchantStoreStaffService();
    }


    /**
     * 获取店员首页信息
     * @param $staffUser array 店员信息
     * @return array
     */
    public function getIndexInfo($param,$staffUser){
        if(empty($staffUser)){
            throw new \think\Exception("未登录", 1002);
        }

        $returnArr = [];
        // 店员信息
        $returnArr['staff_name'] = $staffUser['name'];
        $returnArr['can_refund_dinging_order'] = $staffUser['can_refund_dinging_order'];// 店员中心是否可以操作新版餐饮整单退款  1：是  0：否
        $returnArr['show_scenic_order'] = $staffUser['show_scenic_order'];// 是否可以查看景区订单列表  1：是  0：否

        $returnArr['staff_logo'] = cfg('system_admin_logo') ? replace_file_domain(cfg('system_admin_logo')) : cfg('site_url')."/tpl/System/Static/images/pigcms_logo.png";

        // 店铺信息
        $where = [
            'store_id' => $staffUser['store_id']
        ];
        $merchentStore = (new MerchantStoreService())->getOne($where);
        $returnArr['store_name'] = $merchentStore['name'];
        // 店铺对应商家信息
        $returnArr['merchant_phone'] ='';
        if (isset($merchentStore['mer_id']) && $merchentStore['mer_id']) {
            $where_merchant = [
                'mer_id' => $merchentStore['mer_id']
            ];
            $merchent = (new MerchantService())->getOne($where_merchant, 'mer_id, menus');
            if (isset($merchent['menus']) && $merchent['menus']) {
                $menus = explode(',', $merchent['menus']);
                if (in_array(60, $menus)) {
                    $merchentStore['have_appoint'] = 1;
                }
            }
            $returnArr['merchant_phone'] = $merchent['phone'];
            $merchentStore['is_open_scenic'] = $merchent['is_open_scenic'];//判断商家是否开启了景区功能
        }
        // 餐饮店铺信息
        $merchentStoreFoodshop = (new MerchantStoreFoodshopService())->getOne($where);

        $returnArr['share_table_type'] = 0;

        $returnArr['need_perfect_meal_store'] = true;//是否需要完善店铺信息
        if($merchentStoreFoodshop){
            $returnArr['need_perfect_meal_store'] = false;

            // 餐饮拼桌模式 拼桌方式：1-多人点餐 2-拼桌 3-一桌一单
            $returnArr['share_table_type'] = $merchentStoreFoodshop['share_table_type'];
        }

        // 是否验店员商家密码强度
        // 判断密码是否是123456
        $returnArr['update_password'] = false;
        if(cfg('pwd_verify') && md5('123456') == $staffUser['password']){
            $returnArr['update_password'] = true;
        }

        // 菜单列表
        $menuList = (new MenuService())->getMenuList($merchentStore, $staffUser);
        $topMenuList = (new MenuService())->getAppTopMenuList($merchentStore, $staffUser);
        $returnArr['top_memu_list'] = $topMenuList;
        $returnArr['memu_list'] = $menuList;
        $returnArr['store_id'] = $staffUser['store_id'];
        $returnArr['time'] = time(); // 当前时间戳


        if($staffUser){
            // 更新店员数据
            $saveData = [];
            $phoneBrand = $param['phone_brand'] ?? '';
            $appVersion = $param['app_version'] ?? '';
            $appVersionName = $param['app_version_name'] ?? '';
            if($phoneBrand){
                $saveData['phone_brand'] = $phoneBrand;
            }
            if($appVersion){
                $saveData['app_version'] = $appVersion;
            }
            if($appVersionName){
                $saveData['app_version_name'] = strval($appVersionName);
            }

            $where = [
                'id' => $staffUser['id']
            ];
            (new MerchantStoreStaffService())->updateThis($where,$saveData);
        }
        return $returnArr;
    }

    /**
     * 修改密码
     * @param $param array 登录信息
     * @return array
     */
    public function editPassword($param, $staffUser){
        if(!$staffUser) {
            throw new \think\Exception(L_("帐号不存在！"), 1003);
        }

        // 原密码
        $oldPassword = $param['old_password'] ?? '';

        // 修改后的密码
        $password = $param['password'] ?? '';

        if($staffUser['password'] != md5(123456) && $staffUser['password'] != md5($oldPassword)) {
            throw new \think\Exception(L_("输入的原密码错误"), 1003);
        }

        if(strlen($password) < 6){
            throw new \think\Exception(L_("密码长度不得小于6位数"), 1003);
        }

        // 保存店员信息
        $data = [];
        $data['password'] = md5($password);
        $where = [
            'id' => $staffUser['id']
        ];
        if(!$this->merchantStoreStaffService->updateThis($where, $data)){
            throw new \think\Exception(L_("密码保存失败,请重试！"), 1003);
        }
        return true;
    }

    /**
     * desc: 登录成功后修改店员登录信息
     * return :array
     * Author: hengtingmei
     * Date Time: 2020/12/09 16:37
     */
    public function updateLoginInfo($staffUser){
        if (!$staffUser) {
            return false;
        }

        // 设备id
        $deviceId = request()->param('Device-Id') ?? '';
        $client = request()->param('client') ?? '';
        $appVersion= request()->param('app_version','','intval') ?? '';
        $appVersionName = request()->param('app_version_name','','strval') ?? '';
        $phoneBrand = request()->param('phone_brand','','strval') ?? '';

        if($deviceId!= 'packapp' && $deviceId != '020000000000'){
            $where = [
                ['device_id', '=', $deviceId],
                ['client', '<>', '0'],
            ];
            $saveData = [
                'device_id'=>'',
                'device_token'=>'',
                'last_time'=>'0',
            ];
            $this->merchantStoreStaffService->updateThis($where, $saveData);
        }

        //保存店员登录信息
        $where = [
            'id' =>   $staffUser['id']
        ];
        $dataStoreStaff['last_time'] = time();
        $dataStoreStaff['device_id'] = $deviceId;
        if(isset($client)){
            $dataStoreStaff['client'] = $client;
        }

        if($deviceId){
            $dataStoreStaff['is_app_native'] = 1;//app原生首页
        }

        $deviceToken = request()->param('device_token');//友盟token，若没有极成则返回空字符串
        $jpushDeviceToken = request()->param('jpush_device_token');//极光token，若没有极成则返回空字符串
        if($deviceToken !== null){
            $dataStoreStaff['device_token'] = $deviceToken;
        }
        if($jpushDeviceToken !== null){
            $dataStoreStaff['jpush_device_token'] = $jpushDeviceToken;
        }

        $dataStoreStaff['app_version'] = $appVersion;
        $dataStoreStaff['app_version_name'] = $appVersionName;
        $dataStoreStaff['phone_brand'] = $phoneBrand;
        $dataStoreStaff['web_session'] = hash('sha256',generate_password(32).'_'.$staffUser['id'],false);
        $save = $this->merchantStoreStaffService->updateThis($where,$dataStoreStaff);

        if ($save === FALSE) {
            return false;
        }

        /* 登录历史记录 */
        $dataLog = array(
            'uid' 		=> $staffUser['id'],
            'client' 	=> $client,
            'device_id' => $deviceId,
            'app_version' => $appVersion,
            'app_version_name' => $appVersionName,
            'add_time' 	=> time(),
            'add_ip' 	=> request()->ip(),
            'type' 		=> '0',
        );
        if(!isset($client)){
            $dataLog['client'] = $client;
        }
        (new StoreStaffLoginLogService())->add($dataLog);

        $this->checkNotice($staffUser);
        return true;
    }

    /**
     * desc: 登录成功后修改店员登录信息
     * return :array
     * Author: hengtingmei
     * Date Time: 2020/12/09 16:37
     */
    public function checkLogin(){
        $token = Token::getToken();
        $tokenInfo = Token::checkToken($token);
        $staffUser = [];
        if($tokenInfo['memberId']){
            $staffUser = $this->merchantStoreStaffService->getStaffById($tokenInfo['memberId']);
        }

        // 设备id
        $deviceId = request()->param('Device-Id') ?? '';
        $appVersion= request()->param('app_version','','intval') ?? '';

        $returnArr = [
            'status' => 0
        ];

        if($deviceId && $staffUser){
            if($staffUser['device_id'] != $deviceId){
                // 强制下线
                $returnArr['status'] = 1;
                $returnArr['operate_type'] = 'logout';
            }

            // 更新店员信息
            $where = [
                'id' => $staffUser['id']
            ];
            $data = [
                'last_time'=>time(),
                'use_lang'=>cfg('system_lang')
            ];
            $this->merchantStoreStaffService->updateThis($where, $data);

            // 更新店铺信息
            $where = [
                'store_id' => $staffUser['store_id']
            ];
            $data = [
                'staff_last_time'=>time(),
            ];
            (new MerchantStoreService())->updateThis($where, $data);
        }

        $this->checkNotice($staffUser);

        if($appVersion && $deviceId != 'packapp'){
            $appConfig  =   (new AppapiAppConfigService())->get();
            if(request()->agent==2){//安卓
                if($appConfig['staff_android_vcode'] > $appVersion){
                    // 升级提醒
                    $returnArr['status'] = 1;
                    $returnArr['operate_type'] = 'update';
                    // 强制升级
                    if($appConfig['storestaff_android_must_upgrade']){
                        $returnArr['operate_type'] = 'must_update';
                    }
                }
            }

        }
        return $returnArr;
    }

    /**
     * 退出登录
     * @param $param array 登录信息
     * @return array
     */
    public function logout($staffUser){
        if($staffUser){
            $where = [
                'id' => $staffUser['id']
            ];
            $saveData = [
                'device_id'=>'',
                'device_token'=>'',
                'last_time'=>'0',
                'last_print_time'=>'0',
                'is_app_native'=>'0'
            ];
            $this->merchantStoreStaffService->updateThis($where, $saveData);

            /* 登出历史记录 */
            $dataLog = array(
                'uid' 		=> $staffUser['id'],
                'client' 	=> '0',
                'device_id' => request()->param('Device-Id') ?? '',
                'app_version' => request()->param('app_version','','intval') ?? '',
                'app_version_name' => request()->param('app_version_name','','strval') ?? '',
                'add_time' 	=> time(),
                'add_ip' 	=> request()->ip(),
                'type' 		=> '1',
            );
            (new StoreStaffLoginLogService())->add($dataLog);
        }

        $deviceId = request()->param('Device-Id') ?? '';

        if($deviceId != 'packapp' && $deviceId != '020000000000'){
            $where = [
                ['device_id', '=', $deviceId],
                ['client', '<>', '0'],
            ];
            $saveData = [
                'device_id'=>'',
                'device_token'=>'',
                'last_time'=>'0',
                'is_app_native'=>'0',
                'last_print_time'=>'0'
            ];
            $this->merchantStoreStaffService->updateThis($where, $saveData);
        }

        $this->checkNotice($staffUser);
        return true;
    }

    /**
     * 新订单通知
     * @param $param array 登录信息
     * @return array
     */
    public function orderNotice(array $param,$staffUser){

        // 设备id
        $deviceId = request()->param('Device-Id') ?? '';
        $appType = request()->param('app_type') ?? '';
        $nowTime = request()->param('now_time','intval','0');
        if($appType != 'packapp'){//店员APP
            $appType = ($appType==1 || $appType == 'ios') ? 'ios' : 'android';
            $return = (new AppPushMsgService())->getstaffNewOrderMessage($deviceId,$appType,$staffUser);
        }else{// PC店员
            $return = (new WebPushMsgService())->getstaffOrderMessage($staffUser,$nowTime);
        }
        
        return $return;
    }

    /**
     * 新订单通知
     * @param $param array 登录信息
     * @return array
     */
    public function editNoticeStatus($staffUser){

        $where = [
            'id' => $staffUser['id']
        ];
        $saveData = [
            'is_notice' => $staffUser['is_notice'] ? 0 : 1
        ];
        $res = $this->merchantStoreStaffService->updateThis($where, $saveData);
        if($res === false){

            throw new \think\Exception(L_("修改失败，请稍后重试"), 1003);
        }
        return true;
    }

    /**
     * 获得状态正常的店员账号信息
     * @param $staffUserList 店员列表
     * @author: 衡婷妹
     * @date: 2020/12/11
     */
    public function getNormalStaffUser($staffUserList)
    {
        // 判断店铺状态
        $storeIds = array_column($staffUserList,'store_id');
        $where = [
            ['store_id', 'in', implode(',',$storeIds)],
            ['status','<>', '4']
        ];
        $storeList = (new MerchantStoreService())->getSome($where);
        $storeIdList = array_column($storeList,'store_id');
        if(empty($storeList)){
            throw new \think\Exception(L_("店铺已删除"), 1003);
        }

        // 验证商家信息

        $merIds = array_column($storeList,'mer_id');
        $where = [
            ['mer_id', 'in', implode(',',$merIds)],
            ['status','<>', '4']
        ];
        $merchantIdList = array_column((new MerchantService())->getSome($where),'mer_id');
        if(empty($merchantIdList)){
            throw new \think\Exception(L_("商家已删除"), 1003);
        }

        $userList = [];
        foreach ($staffUserList as $_staff){
            // 店铺已删除
            if(!in_array($_staff['store_id'],$storeIdList)){
                continue;
            }

            $_temp = [];
            $_temp['user']['id'] = $_staff['id'];
            $_temp['user']['store_id'] = $_staff['store_id'];
            $_temp['user']['name'] = $_staff['name'];
            $_temp['user']['username'] = $_staff['username'];
            $_temp['user']['is_notice'] = $_staff['is_notice'];
            $_temp['user']['device_token'] = $_staff['device_token'];
            $_temp['user']['jpush_device_token'] = $_staff['jpush_device_token'];

            $ticket = Token::createToken($_staff['id']);
            $_temp['ticket'] = $ticket;

            // 店铺信息
            foreach ($storeList as $store){

                if($store['store_id'] == $_staff['store_id']){
                    // 商家已删除
                    if(!in_array($store['mer_id'],$merchantIdList)){
                        continue;
                    }
                    $_temp['user']['mer_id'] = $store['mer_id'];

                    // 店铺名称
                    $_temp['user']['store_name'] = $store['name'];
                    // 店铺地址
                    $_temp['user']['store_address'] = $store['adress'];

                    // 店铺图片
                    $images = (new storeImageService())->getAllImageByPath($store['pic_info']);
                    $store['image_list'] = $images;
                    $store['image'] = $images ? array_shift($images) : '';

                    if($store['logo']){
                        $store['logo'] = replace_file_domain($store['logo']);
                    }
                    $_temp['user']['image'] = $store['logo'] ?: $store['image'];
                    break;
                }
            }
            $userList[] =  $_temp;
        }

        if(empty($userList)){
            throw new \think\Exception(L_("账号不存在"), 1003);
        }
        return $userList;
    }

    //调整店铺有没有接单的店员
    protected function checkNotice($staffUser){
        if(empty($staffUser)){
            return false;
        }
        $where = [
            ['store_id', '=', $staffUser['store_id']],
            ['is_notice', '=', '0'],
            ['last_time', '>', '0'],
        ];
        $staffCount = $this->merchantStoreStaffService->getCount($where);

        // 更新店铺数据
        $where = [
            ['store_id', '=', $staffUser['store_id']],
        ];
        $data = [
            'staff_notice_count'=>$staffCount
        ];
        (new MerchantStoreService())->updateThis($where,$data);
        return true;
    }

    // 隐私政策
    public function getPrivacyPolicy(){
        $appConfig = (new AppapiAppConfigService())->get();
        $arr['privacy_policy'] = htmlspecialchars_decode(htmlspecialchars_decode($appConfig['staff_privacy_policy']),ENT_QUOTES);
        $arr['privacy_policy_md5'] = md5($appConfig['staff_privacy_policy'].cfg('register_agreement'));
        // 注册协议
        $arr['register_agreement'] = htmlspecialchars_decode(htmlspecialchars_decode(cfg('register_agreement')),ENT_QUOTES);
        return $arr;
    }

    /**
     * 接单
     * @return void
     * @author: 张涛
     * @date: 2020/11/09
     */
    public function take($orderId, $businessType, $staffId, $pick_addr_ids='')
    {
        switch ($businessType) {
            case 'shop':
                (new ShopOrderService)->takeOrder($orderId, $staffId, $pick_addr_ids);
                break;
        }
    }

    /**
     * 取消订单
     * @return void
     * @author: 张涛
     * @date: 2020/11/09
     */
    public function cancel($orderId, $businessType, $staffId, $pick_addr_ids='')
    {
        switch ($businessType) {
            case 'shop':
                (new ShopOrderService)->cancelOrder($orderId, $staffId, $pick_addr_ids);
                break;
        }
    }

    /**
     * 发货
     * @return void
     * @author: 张涛
     * @date: 2020/11/09
     */
    public function delivery($orderId, $businessType, $staffId, $expressInfo = [])
    {
        switch ($businessType) {
            case 'shop':
                (new ShopOrderService)->deliveryOrder($orderId, $staffId, $expressInfo);
                break;
        }
    }

    /**
     * 录入商品重量
     * @author: 张涛
     * @date: 2020/11/12
     */
    public function writeWeight($orderId, $businessType, $staffId, $weightInfo = [])
    {
        switch ($businessType) {
            case 'shop':
                (new ShopOrderService)->writeWeight($orderId, $staffId, $weightInfo);
                break;
        }
    }

    /**
     * 同意/拒绝售后退款
     * @author: 张涛
     * @date: 2020/11/12
     */
    public function replyRefund($orderId, $businessType, $staffId, $refundInfo = [])
    {
        switch ($businessType) {
            case 'shop':
                (new ShopOrderService)->replyRefund($orderId, $staffId, $refundInfo);
                break;
        }
    }

    /**
     * 预订单处理订单
     * @author: 张涛
     * @date: 2020/11/13
     */
    public function handleBookOrder($orderId, $businessType, $staffId, $pick_addr_ids='')
    {
        switch ($businessType) {
            case 'shop':
                (new ShopOrderService)->handleBookOrder($orderId, $staffId , $pick_addr_ids);
                break;
        }
    }

    /**
     * 完成订单
     * @author: 张涛
     * @date: 2020/11/13
     */
    public function confirmConsume($orderId, $businessType, $staffId, $pick_addr_ids='')
    {
        switch ($businessType) {
            case 'shop':
                (new ShopOrderService)->confirmConsume($orderId, $staffId, $pick_addr_ids);
                break;
        }
    }


    /**
     * 获取新订单数量
     * @author: 张涛
     * @date: 2020/11/19
     */
    public function getNewOrderCount($businessType, $staffId)
    {
        $num = 0;
        switch ($businessType) {
            case 'shop':
                $num = (new ShopOrderService)->getShopNewOrderCount($staffId);
                break;
        }
        return $num;
    }

    /**
     * 获取店员信息
     * @return array
     * @author: 张涛
     * @date: 2020/11/09
     */
    public function getStaffInfoById($staffId)
    {
        return (new MerchantStoreStaff())->where('id', $staffId)->findOrEmpty()->toArray();
    }

    /**
     * 商品寄存核销
     */
    public function doCardDeposit($param)
    {
        $where = [
            'is_used' => 0
        ];
        if (!empty($param['id'])) {
            $where['bind_id'] = $param['id'];
            $data = (new CardNewDepositGoodsBindUser())->where(['id' => $param['id']])->find();
        } else if (!empty($param['code'])) {
            $where['number'] = $param['code'];
            $param['num']    = 1;
            $param['id']     = (new CardNewDepositGoodsBindGoods())->where(['number' => $param['code']])->value('bind_id');
            $data = (new CardNewDepositGoodsBindUser())->where(['id' => $param['id']])->find();
        } else {
            throw new \Exception('参数有误', 1003);
        }
        if (empty($data)) {
            throw new \Exception('商品不存在', 1003);
        }
        if ($data['store_id'] != $param['staffUser']['store_id']) {
            throw new \Exception('非该店铺店员不可以核销该商品', 1003);
        }
        if ($data['num'] - $data['use_num'] < $param['num']) {
            throw new \Exception('剩余商品数量不足', 1003);
        }
        (new CardNewDepositGoodsBindUser())->where(['id' => $param['id']])->update([
            'use_num'  => $data['use_num'] + $param['num'],
            'use_time' => time()
        ]);
        $verification_id = (new CardNewDepositGoodsVerification())->insertGetId([
            'mer_id'   => $data['mer_id'],
            'staff_id' => $param['staffUser']['id'],
            'store_id' => $data['store_id'],
            'bind_id'  => $param['id'],
            'num'      => $param['num'],
            'status'   => 1,
            'use_time' => time()
        ]);
        (new CardNewDepositGoodsBindGoods())->where($where)->limit($param['num'])->update([
            'verification_id' => $verification_id,
            'is_used'  => 1,
            'use_time' => time()
        ]);
        return true;
    }

    /**
     * 获取店员核销列表
     */
    public function getDepositList($param, $staffUser){
        $limit = [
            'page'      => !empty($param['page']) ? $param['page'] : 1,
            'list_rows' => !empty($param['pageSize']) ? $param['pageSize'] : 10
        ];
        $where = [
            ['a.status', '=', 1],
            ['a.store_id', '=', $staffUser['store_id']]
        ];
        if (!empty($param['keyword'])) {
            $where[] = ['b.name', 'like', '%' . $param['keyword'] . '%'];
        }
        $prefix    = config('database.connections.mysql.prefix'); //表前缀
        $returnArr = (new CardNewDepositGoodsVerification())
            ->alias('a')
            ->join($prefix . 'merchant_store_staff b','b.id = a.staff_id')
            ->join($prefix . 'card_new_deposit_goods_bind_user c','c.id = a.bind_id')
            ->join($prefix . 'card_new_deposit_goods d','d.goods_id = c.goods_id')
            ->where($where)
            ->order('use_time desc')
            ->field('a.num,a.use_time,b.name as staff_name,d.name as goods_name')
            ->paginate($limit)
            ->toArray();
        if (!empty($returnArr['data'])) {
            foreach ($returnArr['data'] as $k => $v) {
                $returnArr['data'][$k]['use_time'] = date('Y-m-d H:i:s', $v['use_time']);
                $returnArr['data'][$k]['desc'] = '核销' . $v['goods_name'] . $v['num'] . '份';
            }
        }
        return [
            'total' => $returnArr['total'],
            'list'  => $returnArr['data']
        ];
    }

}

