<?php
/**
 * 商家model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/19 10:38
 */

namespace app\merchant\model\db;
use app\common\model\db\ConfigData;
use think\Model;
class Merchant extends Model {
    
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 根据merId获取商家绑定用户
     * @param $merId
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMerchantUserByMerId($merId) {
        if(empty($merId)) {
            return false;
        }
        
        $where = [
            ['m.mer_id','=', $merId],
            ['u.uid','>', '0'],
        ];
        
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');

        $result = $this ->alias('m')
                ->join($prefix.'user u','u.uid = m.uid')
                ->field('m.mer_id,m.name as mer_name ,m.phone as mer_phone,m.open_money_tempnews,u.uid,u.nickname,u.phone as user_phone,u.openid,m.tianque_mno')
                ->where($where)
                ->find();
        return $result;
    }

    /**
     * 根据merId获取商家
     * @param $merId
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMerchantByMerId($merId) {
        if(empty($merId)) {
             return false;
        }
        
        $where = [
            'mer_id' => $merId
        ];

        $this->name = _view($this->name);
        $result = $this->where($where)->find();
        return $result;
    }
    
    /**
     * 根据id更新数据
     * @param $goodsId
     * @param $data
     * @return array|bool|Model|null
     */
    public function updateByGoodsId($goodsId,$data) {
        if(!$id || $data){
            return false;
        }

        $where = [
            'goods_id' => $goodsId
        ];

        $result = $this->where($where)->update($data);
        return $result;
    }

    public function getMerchantByMerIds($merIds)
    {
        if (empty($merIds)) return [];
        $where = [
            ['mer_id', 'in', $merIds]
        ];

        $this->name = _view($this->name);
        return $this->getSome($where);
    }

    public static function merchantNoAuthMenu(int $merId)
    {
        $noAuth = [];

        $merchantMenus = self::where(array('mer_id' => $merId))->value('menus');

        $merchant = NewMerchantMenu::column('id');

        if (empty($merchantMenus)) {
            return $noAuth;
        }
        $menuIds = [
            5     => 'merchant',// 店铺
            6     => 'meal',// 餐饮
            8     => 'group',// 团购
            35    => 'fans_send',// 粉丝群发
            49    => 'printer',// 打印机
            60    => 'appoint',// 预约
            108   => 'shop',// 外卖
            146   => 'merchant_member',// 会员卡管理
            10010 => 'batch_mall',// 批发市场
            10011 => 'sale_order',// 销售订单
            10012 => 'purchase_order',// 进货订单
            10023 => 'mall',// 商城
            10023 => 'new_mall',// 商城
            10037 => 'integral_mall',// 积分商城
            146 => 'mermber',// 会员管理
            176 => 'coupon',// 优惠券管理
            10009 => 'publish_goods',// 我发布的商品
        ];

        $merchantMenuIds = array_filter(explode(',', $merchantMenus));
        foreach ($menuIds as $k => $v) {
            if (!in_array($k, $merchantMenuIds)) {
                array_push($noAuth, $v);
            }
        }

        if (cfg('is_switch_wholesale_mobile') <= 0) {
            array_push($noAuth, 'batch_mall');//批发市场
        }
        
        if (cfg('is_switch_wholesale_mobile') == 0) {
            $publishMarketGoods = self::where(array('mer_id' => $merId))->value('is_publish_market_goods');
            if (empty($publishMarketGoods)) {
                array_push($noAuth, 'sale_order');//销售订单
            }
            array_push($noAuth, 'purchase_order');//进货订单
            array_push($noAuth, 'publish_goods');//发布的商品
            array_push($noAuth, 'address');//收货地址
        }
        
        if (empty(cfg('merchant_integral_mall_control'))) {
            array_push($noAuth, 'integral_order');//积分商城订单
        }
        
        if (cfg('merchant_integral_mall_control') <= 0) {
            array_push($noAuth, 'integral_mall');//积分商城
        }

        if (empty(cfg('merchant_recommend_wholesale_switch'))) {
            array_push($noAuth, 'merchant_recommend');//我推广的商家
        }

        if (empty(cfg('merchant_buy_points_switch'))) {
            array_push($noAuth, 'merchant_buy_points');//商户平台积分
        }

        if (empty(cfg('site_phone'))) {
            array_push($noAuth, 'site_phone');//联系客服
        }

        if (empty(cfg('open_score_pay_back_mer'))) {
            array_push($noAuth, 'system_score');//平台商户积分
            array_push($noAuth, 'gift_order');//积分商城订单
        }

        $showContract = ConfigData::where(['name' => 'contract_must_sign'])->value('value');
        if (empty($showContract)) {
            array_push($noAuth, 'show_contract');// 是否开启了合同
        }
        if (!cfg('open_admin_code')) {
            array_push($noAuth, 'invite_code');//去绑定业务员
        }

        if (!customization('merchant_settle_in')) {
            array_push($noAuth, 'settlement_payment');//入驻缴费
        }

        if (!cfg('buy_merchant_auth')) {
            array_push($noAuth, 'permission');//权限套餐
        }
        
        $storeMall = app(MerchantStore::class)
            ->getStoreListCount([
                'mer_id' => $merId,
                'have_mall' => 1,
                'status' => 1,
            ]);
        
        if (empty($storeMall)) {
            array_push($noAuth, 'new_mall');//新版商城
        }
        return $noAuth;
    }
}