<?php

/**
 * 店铺service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/27 11:58
 */

namespace app\merchant\model\service\storestaff;

use app\foodshop\model\service\store\MerchantStoreFoodshopService;
use app\merchant\model\db\MerchantStoreStaff as MerchantStoreStaffModel;
use app\merchant\model\service\MerchantStoreService;
use net\Http;
use token\Token;

class MerchantStoreStaffService
{
    public $merchantStoreStaffModel = null;
    public function __construct()
    {
        $this->merchantStoreStaffModel = new MerchantStoreStaffModel();
    }

    /**
     * 获取店员首页信息
     * @param $staffUser array 店员信息
     * @return array
     */
    public function getIndexInfo($staffUser)
    {
        if (empty($staffUser)) {
            throw new \think\Exception("未登录", 1002);
        }

        $returnArr = [];
        // 店员信息
        $returnArr['staff_name'] = $staffUser['name'];
        $returnArr['staff_logo'] = cfg('system_admin_logo') ? replace_file_domain(cfg('system_admin_logo')) : cfg('site_url') . "/tpl/System/Static/images/pigcms_logo.png";

        // 店铺信息
        $where = [
            'store_id' => $staffUser['store_id']
        ];
        $merchentStore = (new MerchantStore())->getOne($where);
        $returnArr['store_name'] = $merchentStore['name'];
        // 店铺对应商家信息
        if (isset($merchentStore['mer_id']) && $merchentStore['mer_id']) {
            $where_merchant = [
                'mer_id' => $merchentStore['mer_id']
            ];
            $merchent = (new Merchant())->getOne($where_merchant, 'mer_id, menus');
            if (isset($merchent['menus']) && $merchent['menus']) {
                $menus = explode(',', $merchent['menus']);
                if (in_array(60, $menus)) {
                    $merchentStore['have_appoint'] = 1;
                }
            }
        }



        // 餐饮店铺信息
        $merchentStoreFoodshop = (new MerchantStoreFoodshopService())->getOne($where);

        $returnArr['share_table_type'] = 0;

        $returnArr['need_perfect_meal_store'] = true; //是否需要完善店铺信息
        if ($merchentStoreFoodshop) {
            $returnArr['need_perfect_meal_store'] = false;

            // 餐饮拼桌模式 拼桌方式：1-多人点餐 2-拼桌 3-一桌一单
            $returnArr['share_table_type'] = $merchentStoreFoodshop['share_table_type'];
        }

        // 菜单列表
        $menuList = (new MenuService())->getMenuList($merchentStore, $staffUser);
        $returnArr['memu_list'] = $menuList;
        $returnArr['store_id'] = $staffUser['store_id'];
        return $returnArr;
    }

    /**
     * 获取店员信息
     * @param $staffUser array 店员信息
     * @return array
     */
    public function getStaffInfo($staffUser)
    {
        if (empty($staffUser)) {
            throw new \think\Exception("未登录", 1002);
        }

        $returnArr = [];

        // 店员信息
        $returnArr['staff_name'] = $staffUser['name'];
        $returnArr['staff_logo'] = cfg('system_admin_logo') ? replace_file_domain(cfg('system_admin_logo')) : cfg('site_url') . "/tpl/System/Static/images/pigcms_logo.png";       
        $returnArr['can_refund_dinging_order'] = $staffUser['can_refund_dinging_order'];// 店员中心是否可以操作新版餐饮整单退款  1：是  0：否     
        $returnArr['show_scenic_order'] = $staffUser['show_scenic_order'];// 店员中心是否显示景区订单  1：是  0：否


        // 店铺信息
        $where = [
            'store_id' => $staffUser['store_id']
        ];
        $merchentStore = (new MerchantStoreService())->getOne($where);
        $merchentStoreFoodshop = (new MerchantStoreFoodshopService())->getOne($where);
        $returnArr['store_name'] = $merchentStore['name'];
        $returnArr['store_id'] = $merchentStore['store_id'];

        // 餐饮拼桌模式 拼桌方式：1-多人点餐 2-拼桌 3-一桌一单
        $returnArr['share_table_type'] = $merchentStoreFoodshop['share_table_type'] ?? 1;


        return $returnArr;
    }

    /**
     * 店员app轮询获取语音
     * @param $deviceId
     * @param $appType
     * @author: 衡婷妹
     * @date: 2020/9/16
     */
    public function getstaffNewOrderVoice($msg)
    {
        //$voice_return = json_decode($this->voicBaidu(), true);
        //$voice_access_token = $voice_return['access_token'];
        //$voice_mp3 = 'http://tsn.baidu.com/text2audio?tex=' . $msg . '&lan=zh&tok=' . $voice_access_token . '&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5';
        $voice_mp3 = text2audio($msg);
        return $voice_mp3;
    }

    public function voicBaidu()
    {
        static $return;

        if (empty($return)) {
            $voicBaidu = "https://openapi.baidu.com/oauth/2.0/token?grant_type=client_credentials&client_id=XU7nTWQzS9vn32bckmrhHbTu&client_secret=1375720f23eed9b7b787e728de5dd1c2";
            $return = Http::curlGet($voicBaidu);
        }
        return $return;
    }

    /**
     * 根条件获取店员列表
     * @param $where array  
     * @return array
     */
    public function getStaffListByCondition($where)
    {
        if (empty($where)) {
            return [];
        }

        try {
            $result = $this->merchantStoreStaffModel->getStaffListByCondition($where);
            // var_dump($result);
        } catch (\Exception $e) {
            return [];
        }

        return $result->toArray();
    }

    /**
     * 获取一条店员信数据
     * @param $id int 
     * @return array
     */
    public function getStaffById($id)
    {
        if (!$id) {
            return [];
        }

        $staff = $this->merchantStoreStaffModel->getStaffById($id);
        if (!$staff) {
            return [];
        }

        return $staff->toArray();
    }

    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getOne($where)
    {
        $detail = $this->merchantStoreStaffModel->getOne($where);
        if (!$detail) {
            return [];
        }
        return $detail->toArray();
    }

    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getCount($where)
    {
        $res = $this->merchantStoreStaffModel->getCount($where);
        if (!$res) {
            return 0;
        }
        return $res;
    }

    /**
     * 添加数据
     * @param $where array 条件
     * @return array
     */
    public function add($data)
    {
        if (empty($data)) {
            return false;
        }

        $result = $this->merchantStoreStaffModel->save($data);
        if (!$result) {
            return false;
        }

        return $this->merchantStoreStaffModel->id;
    }


    /**
     * 更新店铺数据
     * @param $where array 条件
     * @param $data array 数据
     * @return array
     */
    public function updateThis($where, $data)
    {
        if (empty($where) || !$data) {
            return false;
        }

        $result = $this->merchantStoreStaffModel->updateThis($where, $data);
        if ($result === false) {
            return false;
        }

        return $result;
    }

    /**
     * @param $where
     * @param $field
     * @param $order
     * @return mixed
     * 获取店员列表
     */
    public function getStaffManagementList($where,$field,$order,$page,$pageSize){
        $list= $this->merchantStoreStaffModel->getSome($where,$field,$order,($page-1)*$pageSize,$pageSize);
        if(!empty($list)){
            foreach ($list as $k=>$v){
                if(empty($v['last_time'])){
                    $list[$k]['last_time']='无';
                }else{
                    $list[$k]['last_time']=date("Y-m-d H:i:s",$v['last_time']);
                }
            }
        }
        $res['list'] = $list;
        $res['pageSize'] = $pageSize;
        $res['count'] = $this->merchantStoreStaffModel->getCount($where);
        $res['page'] = $page;
        return $res;
    }

    /**
     * @param $where
     * @return bool
     * @throws \Exception
     * 删除
     */
    public function getDel($where){
        return $this->merchantStoreStaffModel->getDel($where);
    }
    
    public static function getStaff($account, $storeId, $merId)
    {
        $staff = MerchantStoreStaffModel::where(['store_id' => $storeId, 'username' => $account])->find();
        if(empty($staff)){
            $staff = MerchantStoreStaffModel::create([
                'store_id' => $storeId,
                'token' => $merId,
                'name' => $account,
                'username' => $account,
                'password' => md5($storeId.$account.rand(10,99)),
                'time' => time()
            ]);
        }
        return $staff;
    }

    //发送商城订单消息
    public function sendMallOrderMessage($order)
    {
        $where = [
            ['store_id', '=', $order['store_id']],
            ['is_notice', '=', 0],
            ['last_time', '>', 0],
            ['client', '=', 0],
            ['openid', '<>', ''],
        ];

        $staffs = $this->getStaffListByCondition($where);
        if (empty($staffs)) {
            return;
        }
        foreach ($staffs as $staff) {
            if (empty($staff)) {
                continue;
            }
            $href = sprintf(cfg('site_url') . "/packapp/staff/#/pages/mall/order/orderDetail?order_id=%d", $order['order_id']);
            $msgData = [
                'href' => $href,
                'wecha_id' => $staff['openid'],
                'new_info' => [
                    'tempKey' => 44716,
                    'thing12' => L_("新订单提醒"),
                    'phrase2' => cfg('mall_alias_name_new'),
                    'time13' => date("Y-m-d H:i:s", time()),
                    'thing11' => $order['order_no'],
                ]
            ];
            (new \app\common\model\service\weixin\TemplateNewsService())->sendTempMsg('OPENTM400166399', $msgData, $order['mer_id']);
        }
    }
}
