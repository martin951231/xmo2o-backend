<?php
/**
 * 商家service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/27 11:58
 */

namespace app\merchant\model\service;
use app\common\model\db\MerchantStoreShop;
use app\common\model\db\ShopOrder;
use app\common\model\db\SystemOrder;
use app\common\model\db\UserAdress;
use app\common\model\service\admin_user\AdminUserService;
use app\common\model\service\AreaService;
use app\common\model\service\weixin\TemplateNewsService;
use app\merchant\model\db\Merchant;
use app\merchant\model\db\Merchant as MerchantModel;
use app\merchant\model\db\MerchantAccountCustomMenu;
use app\merchant\model\db\MerchantAddressEditSetting;
use app\merchant\model\db\MerchantCustomMenu;
use app\merchant\model\db\NewMerchantMenu;
use app\merchant\model\db\OrderAddressChangeRecord;
use think\facade\Db;
use think\helper\Arr;

class MerchantService {
    public $merchantModel = null;
    public function __construct()
    {
        $this->merchantModel = new MerchantModel();
    }

    /**
     * 组装前端数据
     * @param $param array 登录信息
     * @return array
     */
    public function formatMerchantUserData($merchantUser){
        if (!$merchantUser) {
            return [];
        }

        $returnArr = [];
        $returnArr['name'] = $merchantUser['name'];
        //获取区域名称用于停车场定位
        if($merchantUser['area_id']){
            $area = (new AreaService())->getOne(['area_id'=>$merchantUser['area_id']]);
        }elseif($merchantUser['city_id']){
            $area = (new AreaService())->getOne(['area_id'=>$merchantUser['city_id']]);
        }
        $returnArr['area_name'] = $area['area_name']??'';
        $returnArr['miaomaiyun_switch'] = $merchantUser['miaomaiyun_switch'];
        $returnArr['role'] = [];
        $returnArr['role']['id'] = 'admin';
        $returnArr['role']['name'] = '管理员';
        $returnArr['role']['permissions'][] = [
            'permissionId' => 'system', // 菜单的权限标识
            // 'actions' => null, // 该菜单下的所有按钮集合,可不传
            'actionEntitySet' => [ // 本用户能看见和操作的按钮
                [
                     'action' => 'query',
                     'describe' => '查询',
                     'defaultCheck' => true
                ],
                [
                     'action' => 'add',
                     'describe' => '新增',
                     'defaultCheck' => false
                ],
                [
                     'action' => 'delete',
                     'describe' => '删除',
                     'defaultCheck' => false
                ],
                [
                     'action' => 'edit',
                     'describe' => '修改',
                     'defaultCheck' => false
                ],
                [
                     'action' => 'enable',
                     'describe' => '是否禁用',
                     'defaultCheck' => false
                ],
            ]
        ];
        if(!empty($merchantUser['last_ip'])){
            // $IpLocation = new IpLocation();
            // $last_location = $IpLocation->getlocation($merchantUser['last_ip']);
            // $merchantUser['last']['country'] = mb_convert_encoding($last_location['country'],'UTF-8','GBK');
            // $merchantUser['last']['area'] = mb_convert_encoding($last_location['area'],'UTF-8','GBK');
        }
       
        return $returnArr;
    }

	/**
     * 增加积分
     * @param $orderInfo array 订单信息
     * @param $systemTake float 平台获得金额
     * @param $score float 积分
     * @param $desc string 描述
     * @return array
     */
    public function addSystemScore($orderInfo, $systemTake, $score, $desc){

		$condition['mer_id'] = $orderInfo['mer_id'];
		if($this->merchantModel->where($condition)->inc('system_score',$score)->update()){ 
			$data['mer_id'] = $orderInfo['mer_id'];
	        $data['uid'] = $orderInfo['uid'];
	        $data['money'] = $systemTake;
	        $data['order_id'] = $orderInfo['order_id'];
	        $data['order_type'] = $orderInfo['order_type'];
	        $data['score'] = $score;
	        $data['desc'] = $desc;
	        $data['type'] = 1;// 增加
	        $data['time'] = time();
	       	if (!(new MerchantScoreListService())->add($data)) {
	            return false;
	       	}
		}else{
	        return false;
		}
        return true;
    }

	/**
     * 增加平台采购备用金
     * @param $orderInfo array 订单信息
     * @param $systemTake float 平台获得金额
     * @param $score float 积分
     * @param $desc string 描述
     * @return array
     */
    public function addReserveFund($orderInfo, $money, $desc){
        $condition['mer_id'] = $orderInfo['mer_id'];
        if($this->merchantModel->where($condition)->inc('reserve_fund',$money)->update()){ 
            $data['mer_id'] = $orderInfo['mer_id'];
            $data['uid'] = $orderInfo['uid'];
            $data['money'] = $money;
            $data['order_id'] = $orderInfo['order_id'];
            $data['order_type'] = $orderInfo['order_type'];
            $data['desc'] = $desc;
            $data['type'] = 1;// 增加
            $data['time'] = time();
            if (!(new MerchantReserveFundService())->add($data)) {
	            return false;
	       	}
		}else{
	        return false;
		}
        return true;
    }
    
    /**
     * 增加商家余额
     * @param $merId int 商家id
     * @return array
     */
    public function addMoney($merId, $money) {
        if(empty($merId) || empty($money)){
           return false;
        }

        $where = [
            'mer_id' => $merId
        ];
        
        $result = $this->merchantModel->where($where)->inc('money',$money)->update();
        
        
        return $result; 
    }
    
    
    /**
     * 减少商家余额
     * @param $merId int 商家id
     * @return array
     */
    public function useMoney($merId, $money) {
        if(empty($merId) || empty($money)){
           return false;
        }

        $where = [
            'mer_id' => $merId
        ];
        
        $result = $this->merchantModel->where($where)->dec('money',$money)->update();
        if(!$result) {
            return false;
        }
        
        return $result; 
    }


    /**
     * 入驻成功通知
     * @param $merId int 商家id
     * @return array
     */
    public function registerNotice($merId){

        $where['mer_id'] = $merId;
        $merchant = $this->getOne($where);
        // 商家区域
        $merchantArea = [$merchant['city_id'],$merchant['province_id'],$merchant['area_id']];

        // 管理员列表
        $whereAdmin['openid']=array('<>','');
        $whereAdmin['mer_reg_notice']=1;
        $adminList = (new AdminUserService())->getList($whereAdmin);

        $sendTo = [];
        
        foreach ($adminList as $v) {
            if ($v['level'] == 2 || $v['level'] == 0) {
                $sendTo[] = $v;
            } else if (in_array($v['area_id'], $merchantArea)) {
                $sendTo[] = $v;
            }
        }

        $where = [
           [ 'area_id' , 'in', implode(',',$merchantArea)]
        ];
        $areaList = (new AreaService())->getAreaListByCondition($where);
        $areaList = array_column($areaList, 'area_name', 'area_id');
        $address = '';
        foreach ( $merchantArea as $v) {
            $address .= $areaList[$v] ?? '';
        }

        foreach ($sendTo as $s) {
            $msgDataWx = [
                'wecha_id' => $s['openid'],
                'first' => L_('您有一个新的商家入驻申请'),
                'keyword1' => $merchant['name'],
                'keyword2' => $address,
                'keyword3' => date('Y-m-d H:i:s', $merchant['reg_time']),
                'keyword4' => "{$merchant['account']} {$merchant['phone']}",
                'remark' => L_('请登录PC端系统后台及时查看'),
            ];
            $res = (new TemplateNewsService())->sendTempMsg('OPENTM405733036', $msgDataWx);
        }
    }

	/**
     * 获取商家绑定用户
     * @param $merId int 商家id
     * @return array
     */
    public function getMerchantUserByMerId($merId){
        if(empty($merId)){
            return [];
        }

        $merchantUser = $this->merchantModel->getMerchantUserByMerId($merId);
        if(!$merchantUser) {
            return [];
        }
        
        return $merchantUser->toArray(); 
    }

	/**
     * 根据条件获取其他模块店铺列表
     * @param $merId int 商家id
     * @return array
     */
    public function getMerchantByMerId($merId) {
        if(empty($merId)){
           return [];
        }

        $merchant = $this->merchantModel->getMerchantByMerId($merId);
        if(!$merchant) {
            return [];
        }
        
        return $merchant->toArray(); 
    }

    /**
     * 根据条件获取其数量
     * @param $where array $where
     * @return array
     */
    public function getCount($where) {
        if(empty($where)){
            return false;
        }

        $count = $this->merchantModel->getCount($where);
        if(!$count) {
            return 0;
        }

        return $count;
    }

    /**
     * 更新数据
     * @param $merId int 商家id
     * @return array
     */
    public function updateByMerId($merId, $data) {
        if(empty($merId) || empty($data)){
           return false;
        }

        $where = [
            'mer_id' => $merId
        ];
        $result = $this->merchantModel->where($where)->update($data);
        if(!$result) {
            return false;
        }
        
        return $result; 
    }

    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getOne($where){
        $detail = $this->merchantModel->getOne($where);
        // var_dump($this->merchantModel->getLastSql());
        if(!$detail) {
            return [];
        }
        return $detail->toArray();
    }

    /**
     * 根据条件返回多条数据
     * @param $where array 条件
     * @return array
     */
    public function getSome($where, $field = true,$order=true,$page=0,$limit=0){
        $detail = $this->merchantModel->getSome($where,$field,$order,$page,$limit);
        if(!$detail) {
            return [];
        }
        return $detail->toArray();
    }

    /**
     * 添加数据
     * @param $where array 条件
     * @return array
     */
    public function add($data) {
        if( empty($data)){
            return false;
        }

        $result = $this->merchantModel->save($data);
        if(!$result) {
            return false;
        }

        return $this->merchantModel->id;
    }

    /**
     * 增加商家粉丝
     * @param string $openid 
     * @param int $merId  商家id
     * @param int $fromMerchant  类型
     * @return array
     */
    public function saverelation($openid, $merId, $fromMerchant)
    {
        if (empty($openid)) return false;

        if ($relation = (new MerchantUserRelationService())->getOne(array('openid' => $openid, 'mer_id' => $merId))) {
            return false;
        }
        
        $relation = array('openid' => $openid, 'mer_id' => $merId, 'dateline' => time(), 'from_merchant' => $fromMerchant);

        (new MerchantUserRelationService())->add($relation);

        $this->merchantModel->where(array('mer_id' => $merId))->inc('fans_count', 1)->update();
        return true;
    }


    /**
     * 根据条件获取其他模块店铺列表
     * @param $merId int 商家id
     * @return array
     */
    public function getMerchantByMerIds($merIds) {
        if(empty($merIds)){
            return [];
        }

        $merchant = $this->merchantModel->getMerchantByMerIds($merIds);
        if(!$merchant) {
            return [];
        }

        return $merchant->toArray();
    }

    public function addressSetting(int $merchantId)
    {
        $addressSetting = MerchantAddressEditSetting::where(['merchant_id' => $merchantId])->find();
        if (empty($addressSetting)) {
            $addressSetting = MerchantAddressEditSetting::create([
                'merchant_id'           => $merchantId,
                'merchant_allow'        => 2,
                'merchant_type'         => 'shop',
                'platform_allow'        => 2,
                'platform_type'         => 'shop',
                'order_status'          => 2,
                'distribution_distance' => 1,
                'has_check'             => 1
            ]);
        }
        $addressSetting['address_edit_allow']                 = cfg('address_edit_allow');
        $addressSetting['address_edit_order_status']          = cfg('address_edit_order_status');
        $addressSetting['address_edit_distribution_distance'] = cfg('address_edit_distribution_distance');

        return $addressSetting;
    }

    public function addressSettingEdit(array $params)
    {
        return MerchantAddressEditSetting::where(['merchant_id' => $params['merchant_id']])
            ->save(Arr::only($params,[
                "merchant_allow",
                "merchant_type",
                "platform_allow",
                "platform_type",
                "order_status",
                "distribution_distance",
                "has_check",
            ]));
    }

    public function addressChangeAddRecord(array $params)
    {
        $record = OrderAddressChangeRecord::where(['type' => $params['type'], 'order_id' => $params['order_id']])->find();

        if (!empty($record)) {
            throw_exception('当前订单已申请变更地址，无法重复操作！');
        }
        $autoCheck = false;
        $address = UserAdress::where(['adress_id' => $params['change_address_id']])->find();
        switch ($params['type']) {
            case 'shop':
                $order = ShopOrder::where(['order_id' => $params['order_id']])
                    ->field(['username', 'userphone', 'address', 'address_id', 'lat', 'lng','store_id', 'mer_id'])
                    ->find();
                $deliverType = MerchantStoreShop::where(['store_id' => $order['store_id']])->value('deliver_type');
                if(!in_array($deliverType, [1, 4])){//配送方式（0平台配送，1商家配送，2客户自提，3平台配送或自提，4商家配送或自提,5快递配送）
                    $autoCheck = true;
                }else{
                    $check = MerchantAddressEditSetting::where(['merchant_id' => $order['mer_id']])->value('has_check');
                    if($check != 1){//开启审核
                        $autoCheck = true;
                    }
                }
                break;
        }
       
        try{
            Db::transaction(function ()use($params, $order, $address) {
                $result = OrderAddressChangeRecord::create([
                    'type'              => $params['type'],
                    'order_id'          => $params['order_id'],
                    'lat'               => $order['lat'],
                    'lng'               => $order['lng'],
                    'user_name'         => $order['username'],
                    'user_phone'        => $order['userphone'],
                    'address'           => $order['address'],
                    'address_id'        => $order['address_id'],
                    'change_lat'        => $address['latitude'],
                    'change_lng'        => $address['longitude'],
                    'change_user_name'  => $address['name'],
                    'change_user_phone' => $address['phone'],
                    'change_address'    => $address['adress'].$address['detail'],
                    'change_address_id' => $address['adress_id'],
                    'create_time'       => date('Y-m-d H:i:s')
                ]);

                if ($result) {
                    //change_address_status '修改地址状态  0 未修改  1 等待商家审核 2 商家拒绝修改 3 骑手接单，修改地址自动撤销'
                    SystemOrder::changeAddressStatus($params['type'], $params['order_id'], 1);
                }
                return true;
            });
            if($autoCheck){
                invoke_cms_model('System_order/checkChangeAddress',[$params['order_id'], 5, $params['type']], true);
            }
        }catch (\Exception $e){
            fdump_api(['addressChangeAddRecordError' => $e->getTraceAsString()], 'address_edit', 1);
            throw_exception($e->getMessage());
        }
        
        return true;
    }


    public function merchantMenuList(array $params)
    {
        $merchantMenus = Merchant::where([
            'mer_id' => $params['merchant_id']
        ])->value('menus');
        empty($merchantMenus) && throw_exception(L_('未设置菜单！'));
        
        $menuIds = [];
        $customMenu = MerchantAccountCustomMenu::where([
                'merchant_user_id' => $params['merchant_id']
            ])
            ->field('menu_ids')
            ->find();
        !empty($customMenu) && $menuIds = $customMenu->menu_ids;
        
        $menu = function ($item)use($menuIds) {
            $item->image_icon = cfg('site_url') . "/static/merchant_menu/{$item['type']}.png";
            $item->is_select = 0;
            if(!empty($menuIds) && in_array($item->menu_id, $menuIds)){
                $item->is_select = 1;//已选中为首页菜单
            }
        };
        
        $getCustomMenu = function ($category)use($menu, $params){
           return MerchantCustomMenu::where(['category' => $category])
                ->withSearch(['no_auth'], $params)
                ->field(['id as menu_id','name','type','path'])
                ->select()
                ->each($menu);
        };
        $data = [
            [
                'menus' => $getCustomMenu(1),
                'title' => L_('基础功能'),
            ], [
                'menus' => $getCustomMenu(2),
                'title' => L_('工具'),
            ], [
                'menus' =>  $getCustomMenu(3),
                'title' => L_('我的管理'),
            ],
        ];

        return $data;
    }

    public function merchantMenuEdit(array $params)
    {
        $menu = MerchantAccountCustomMenu::where(['merchant_user_id' => $params['merchant_id']])->find();
        if (empty($menu)) {
            $menu = app(MerchantAccountCustomMenu::class);
            
            $menu->merchant_user_id = $params['merchant_id'];
        }
        $menu->menu_ids = $params['menu_ids'];
        
        return $menu->save();
    }

    public function customMenuIndexList(array $params)
    {
        $merchantMenus = Merchant::where([
            'mer_id' => $params['merchant_id']
        ])->value('menus');
        empty($merchantMenus) && throw_exception(L_('未设置菜单！'));
        
        $customMenuIds = MerchantAccountCustomMenu::where([
                'merchant_user_id' => $params['merchant_id']
            ]) 
            ->field('menu_ids')
            ->find();
        if(empty($customMenuIds)){
            return [];
        }
        $menuIds = $customMenuIds->menu_ids;
        $menu = function ($item) {
            $item->image_icon = cfg('site_url') . "/static/merchant_menu/{$item['type']}.png";
        };
       
        $merchantCustomMenu = MerchantCustomMenu::where('id', 'IN', $menuIds)
            ->withSearch(['no_auth'], $params)
            ->field(['id as menu_id','name','type', 'path'])
            ->select()
            ->each($menu)
            ->toArray();
        $data = [];
        foreach ($menuIds as $v){
            foreach ($merchantCustomMenu as $vv){
                if($v == $vv['menu_id']){
                    array_push($data, $vv);
                }
            }
        }

        return $data;
    }
    
    public function merchantMenu($merId)
    {
        $merchant = Merchant::field(true)->where(['mer_id' => $merId])->find();
        if(empty($merchant)){
            throw_exception('数据库中没有查询到该商家的信息！');
        }
        $merchantMenus = explode(',', $merchant['menus']);

        $where = [
            ['status', '=', 1],
            ['show' ,'=', 1], 
            ['id','IN', $merchantMenus]
        ];
        
        $menus = NewMerchantMenu::field(true)
            ->where($where)
            ->order('`sort` DESC,`id` ASC')
            ->select();

        foreach($menus as &$v){
            $v['name'] = str_replace(
                ['团购','订餐','快店','预约'],
                [cfg('group_alias_name'), cfg('meal_alias_name'), cfg('shop_alias_name'), cfg('appoint_alias_name')],
                $v['name']
            );
        }
        
        $list = (new MerchantMenuService())->arrayPidProcess($menus);
        
        return [
            'menu_list' => $list,
            'merchant'  => $merchant
        ];
    }
}