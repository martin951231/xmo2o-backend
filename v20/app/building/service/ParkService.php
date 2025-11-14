<?php

namespace app\building\service;

use app\api\model\service\building\BuildingApiService;
use app\building\model\ShoppingMallBenefits;
use app\building\model\ShoppingMallCars;
use app\building\model\ShoppingMallPark;
use Carbon\Carbon;
use think\facade\Db;

//停车管理
class ParkService
{
    //最大绑定车辆数
    const LIMIT_CAR_NUM = 5;

    //权益生效时间
    const BENEFITS_EFFECT_HOURS = 24;

    protected $carMod;

    protected $parkMod;

    public function __construct()
    {
        $this->carMod = new ShoppingMallCars();
        $this->parkMod = new ShoppingMallPark();
    }

    //添加车辆
    public function addCar($uid, $params)
    {
        $carNumber = $params['car_number'] ?? '';
        if (empty($carNumber) || !is_valid_car_Numer($carNumber)) {
            throw new \Exception('请输入正确的车牌号');
        }

        $carCount = $this->carMod->where('uid', $uid)->count();
        if ($carCount >= self::LIMIT_CAR_NUM) {
            throw new \Exception('当前用户已绑定最大车辆数');
        }
        $car = $this->carMod->where('uid', $uid)->where('car_number', $carNumber)->find();
        if (!empty($car)) {
            throw new \Exception('该车辆已绑定，请勿重复绑定');
        }
        $data = [
            'uid' => $uid,
            'car_number' => $carNumber,
            'is_bind' => 0,
        ];
        return $this->carMod->insertGetId($data);
    }

    //编辑车辆
    public function editCar($uid, $params)
    {
        $id = $params['id'] ?? 0;
        $carNumber = $params['car_number'] ?? '';
        $isBind = $params['is_bind'] ?? 0;
        if (empty($id) || empty($carNumber)) {
            throw new \Exception('请输入车辆信息');
        }
        if (!is_valid_car_Numer($carNumber)) {
            throw new \Exception('请输入正确的车牌号');
        }

        $findCar = $this->carMod->where('uid', $uid)->where('id', '<>', $id)->where('car_number', $carNumber)->find();
        if ($findCar) {
            throw new \Exception('该车辆已绑定，请勿重复绑定');
        }

        $data = [
            'car_number' => $carNumber,
            'is_bind' => $isBind,
        ];
        if ($isBind) {
            //当前车辆绑定，需要解除其他车辆绑定
            $this->carMod->where('uid', $uid)->where('is_bind', 1)->update(['is_bind' => 0]);
            $data['benefits_effect_time'] = time() + self::BENEFITS_EFFECT_HOURS * 60 * 60;
            $this->carMod->where('uid', $uid)->where('id', $id)->update($data);
        } else {
            //只是修改车辆信息
            $data['benefits_effect_time'] = 0;
            $this->carMod->where('uid', $uid)->where('id', $id)->update($data);
        }
        return true;
    }

    //删除车辆
    public function delCarByCarNumber($uid, $carNumber)
    {
        $car = $this->carMod->where('uid', $uid)->where('car_number', $carNumber)->find();
        if (empty($car)) {
            throw new \Exception('该车辆不存在');
        }
        $this->carMod->where('uid', $uid)->where('id', $car->id)->delete();
        return true;
    }

    //获取我的车辆
    public function getMyCars($uid)
    {
        $lists = $this->carMod->where('uid', $uid)->withoutField('uid')->select();
        return $lists;
    }


    //获取商场所有停车场
    public function getParkByMallId($mallId, $fields = '*')
    {
        $lists = $this->parkMod->where('mall_id', $mallId)->field($fields)->select();
        return $lists;
    }

    //获取停车场信息
    public function getParkInfo($parkId)
    {
        $park = $this->parkMod->where('id', $parkId)->find();
        if (empty($park)) {
            throw new \Exception('停车场不存在');
        }

        $info = [];
        if ($park->park_type == 1) {
            $path = '/work_park/api/park/info';
            $businessData = [
                'merchant_id' => $park->merchant_id,
                'api_key' => $park->api_key,
            ];
            $data = (new BuildingApiService())->invoke($path, $businessData);
            $info = [
                'park_name' => $data['info']['park_name'] ?? '',
                'park_space_total' => $data['info']['park_space_total'] ?? 0,
                'presence_car_total' => $data['info']['presence_car_total'] ?? 0,
            ];
            $info['park_left_total'] = $info['park_space_total'] >= $info['presence_car_total'] ? intval($info['park_space_total'] - $info['presence_car_total']) : 0;
        } else {
            //TODO 其他停车场暂无对接
        }
        return $info;
    }

    //获取停车场积分兑换券
    public function getCouponList($parkId)
    {
        $setting = $this->parkMod->where('id', $parkId)->value('score_coupon_setting');
        return !empty($setting) ? json_decode($setting, true) : [];
    }

    //获取会员权益
    public function getBenefitsList($uid, $mallId)
    {
        $userLevelInfo = (new \app\common\model\service\UserService())->getLevelByUid($uid);
        if (!$userLevelInfo['is_level']) {
            return [];
        }

        $benefits = $this->getLevelBenefits($userLevelInfo['level'], $mallId);
        return $benefits;
    }

    //根据等级获取商城会员权益
    public function getLevelBenefits($level, $mallId)
    {
        $benefits = Db::name('shopping_mall_membership_benefits')->alias('mb')
            ->join('shopping_mall_benefits b', 'b.id=mb.b_id')
            ->where('mb.level', $level)
            ->where('mb.mall_id', $mallId)
            ->field('b.*,mb.coupon_data')
            ->select()->each(function ($item) {
                if ($item['type'] == ShoppingMallBenefits::TYPE_PARK) {
                    $couponData = json_decode($item['coupon_data'], true);
                    if ($couponData) {
                        switch ($couponData['coupon_type']) {
                            case 1:  //时长减免
                                $item['description'] = sprintf('免费停车%s小时', getFormatNumber($couponData['coupon_quota']));
                                break;
                            case 2:  //金额减免
                                $item['description'] = sprintf('停车费减免%s元', getFormatNumber($couponData['coupon_quota']));
                                break;
                            case 3:  //折扣减免
                                $item['description'] = sprintf('停车费%s折', getFormatNumber($couponData['coupon_quota']));
                                break;
                            default:
                                break;
                        }
                        unset($item['coupon_data']);
                    }
                }
                return $item;
            });
        return $benefits;
    }

    //兑换停车券
    public function exchangeCoupon($params)
    {
        $uid = $params['uid'] ?? 0;
        $parkId = $params['park_id'] ?? 0;
        $couponId = $params['coupon_id'] ?? 0;
        $carNumber = $params['car_number'] ?? '';

        if (empty($uid) || empty($parkId) || empty($couponId)) {
            throw new \Exception('参数错误');
        }
        if (empty($carNumber)) {
            throw new \Exception('请输入车牌号');
        }
        $park = $this->parkMod->where('id', $parkId)->find();
        $couponLists = !empty($park->score_coupon_setting) ? json_decode($park->score_coupon_setting, true) : [];
        if (empty($couponLists)) {
            throw new \Exception('停车场未配置积分兑换券');
        }

        $useScoreCount = 0;
        $couponName = '';
        foreach ($couponLists as $coupon) {
            if ($coupon['coupon_id'] == $couponId) {
                $useScoreCount = $coupon['score_count'];
                $couponName = $coupon['coupon_name'];
                break;
            }
        }
        if ($useScoreCount <= 0 || empty($couponName)) {
            throw new \Exception('优惠券已用完或不存在');
        }

        //开始兑换流程
        $scoreAlias = cfg('score_name');
        $user = Db::name('user')->where('uid', $uid)->where('status', 1)->find();
        if (empty($user)) {
            throw new \Exception('用户不存在或已被禁用');
        }
        if ($user['score_count'] < abs($useScoreCount)) {
            throw new \Exception('用户' . $scoreAlias . '不足,当前剩余数量：' . $user['score_count']);
        }

        //兑换优惠券记录兑换日志
        $path = '/work_park/api/merchant/coupon/grant';
        $businessData = [
            'merchant_id' => $park->merchant_id,
            'api_key' => $park->api_key,
            'coupon_id' => $couponId,
            'num' => 1,
            'car_number' => $carNumber,
        ];
        try {
            $grantResult = (new BuildingApiService())->invoke($path, $businessData);
        } catch (\Exception $e) {
            throw new \Exception('优惠券兑换失败（' . $e->getMessage() . '）,请联系商场管理员');
        }
        $log = [
            'mall_id' => $park->mall_id,
            'park_id' => $parkId,
            'uid' => $uid,
            'coupon_id' => $couponId,
            'coupon_name' => $couponName,
            'merchant_id' => $park->merchant_id,
            'num' => 1,
            'car_number' => $carNumber,
            'score_count' => $useScoreCount,
            'type' => 2,
            'exchange_time' => time(),
            'exchange_id' => implode(',', $grantResult['id']),
        ];
        Db::name('shopping_mall_coupon_logs')->insert($log);

        //扣除积分
        $remark = sprintf('%s兑换停车券【%s】,车牌号：%s', $scoreAlias, $couponName, $carNumber);
        $result = (new \app\common\model\service\UserService())->userScore($user['uid'], abs($useScoreCount), $remark);
        if ($result['error_code']) {
            throw new \Exception($result['msg']);
        }
        return $grantResult;
    }

    //领取会员权益停车优惠券
    public function getBenefitCoupon($params)
    {
        $uid = $params['uid'] ?? 0;
        $parkId = $params['park_id'] ?? 0;
        $couponId = $params['coupon_id'] ?? 0;
        $carNumber = $params['car_number'] ?? '';

        if (empty($uid) || empty($parkId) || empty($couponId)) {
            throw new \Exception('参数错误');
        }
        if (empty($carNumber)) {
            throw new \Exception('请输入车牌号');
        }
        $park = $this->parkMod->where('id', $parkId)->find();
        if (empty($park)) {
            throw new \Exception('停车场不存在');
        }
        //判断今天已领取过会员权益
        $log = Db::name('shopping_mall_coupon_logs')
            ->where('uid', $uid)
            ->where('type', 1)
            ->where('exchange_time', '>', Carbon::now()->startOfDay()->timestamp)
            ->find();
        if ($log) {
            throw new \Exception('今天已领取过停车券，请明天再来领取');
        }
        $benefits = $this->getLevelParkBenefitCoupon($uid, $carNumber, $park->mall_id);
        $couponIndex = array_column($benefits, 'coupon_name', 'coupon_id');
        if (!isset($couponIndex[$couponId])) {
            throw new \Exception('当前会员权益优惠券不存在');
        }
        $couponName = $couponIndex[$couponId];

        //兑换优惠券记录兑换日志
        $path = '/work_park/api/merchant/coupon/grant';
        $businessData = [
            'merchant_id' => $park->merchant_id,
            'api_key' => $park->api_key,
            'coupon_id' => $couponId,
            'num' => 1,
            'car_number' => $carNumber,
        ];
        try {
            $grantResult = (new BuildingApiService())->invoke($path, $businessData);
        } catch (\Exception $e) {
            throw new \Exception('优惠券兑换失败（' . $e->getMessage() . '）,请联系商场管理员');
        }
        $log = [
            'mall_id' => $park->mall_id,
            'park_id' => $parkId,
            'uid' => $uid,
            'coupon_id' => $couponId,
            'coupon_name' => $couponName,
            'merchant_id' => $park->merchant_id,
            'num' => 1,
            'car_number' => $carNumber,
            'score_count' => 0,
            'type' => 1,
            'exchange_time' => time(),
            'exchange_id' => implode(',', $grantResult['id']),
        ];
        Db::name('shopping_mall_coupon_logs')->insert($log);
        return $grantResult;
    }

    //查询停车缴费信息
    public function getPayInfo($parkId, $carNumber, $uid = 0)
    {
        $park = $this->parkMod->where('id', $parkId)->find();
        if (empty($park)) {
            throw new \Exception('停车场不存在');
        }
        $path = '/work_park/api/car/log/info';
        $businessData = [
            'merchant_id' => $park->merchant_id,
            'api_key' => $park->api_key,
            'car_number' => $carNumber,
        ];
        $data = (new BuildingApiService())->invoke($path, $businessData);
        $data['benefit_coupon'] = $this->getLevelParkBenefitCoupon($uid, $carNumber, $park->mall_id);
        $data['park_notice'] = (isset($data['info']['fee_info']['fee_original']) && $data['info']['fee_info']['fee_original'] > 0) ? '' : '当前车辆无需缴费，可直接离场';
        return $data;
    }

    //查询会员停车权益
    public function getLevelParkBenefitCoupon($uid, $carNumber, $mallId)
    {
        //是否绑定车牌号，且权益已生效
        $car = $this->carMod
            ->where('uid', $uid)
            ->where('car_number', $carNumber)
            ->where('is_bind', 1)
            ->where('benefits_effect_time', '<=', time())
            ->find();
        if (empty($car)) {
            return [];
        }

        //查看商场配置的会员权益
        $coupons = [];
        $benefits =  (new ShoppingMallService())->getUserLevelMallBenefits($mallId, $uid);
        foreach ($benefits as $item) {
            if ($item['type'] == ShoppingMallBenefits::TYPE_PARK && $item['coupon_id'] > 0) {
                $coupons[] = [
                    'coupon_name' => $item['coupon_name'],
                    'coupon_id' => $item['coupon_id'],
                    'info' => '会员免费领取停车券'
                ];
                break;
            }
        }
        return $coupons;
    }
}
