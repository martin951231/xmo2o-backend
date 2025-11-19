<?php

namespace app\paotui\model\service;

use app\paotui\model\db\PaotuiRiderCommissionSetting;
use think\facade\Db;

/**
 * 骑手跑腿佣金
 *
 * @author: zt
 * @date: 2025/07/07
 */
class RiderCommissionService
{
    public $riderCommissionMod;

    public function __construct()
    {
        $this->riderCommissionMod = new PaotuiRiderCommissionSetting();
    }

    //保存佣金设置
    public function saveRiderCommissionSetting($params)
    {
        $rule = [
            'deliver_uid' => 'require|integer',
            'commission_type' => 'require|in:1,2',

            //动态佣金配置项
            'dynamics_commission' => 'require|egt:0|elt:100',
            'dynamics_commission_ladder' => 'array',

            //固定佣金配置项
            'fixed_commission_distance_ladder' => 'requireIf:commission_type,2|array',
            'fixed_commission_weight_ladder' => 'requireIf:commission_type,2|array',
            'within_distance' => 'requireIf:commission_type,2|egt:0',
            'within_distance_money' => 'requireIf:commission_type,2|egt:0',
            'exceed_weight' => 'requireIf:commission_type,2|egt:0',
            'exceed_weight_add_money' => 'requireIf:commission_type,2|egt:0',

            'added_services_commission' => 'require|egt:0|elt:100',
            'special_services_commission' => 'require|egt:0|elt:100',
        ];

        $message = [
            'deliver_uid' => '请先选择骑手',
            'commission_type' => '请选择佣金来源',
            'dynamics_commission' => '配送费金额固定抽成参数有误(抽成比例0-100)',
            'fixed_commission_distance_ladder' => '固定佣金配送距离阶梯未设置',
            'fixed_commission_weight_ladder' => '固定佣金配送重量阶梯未设置',
            'within_distance' => '固定佣金起送距离未设置',
            'within_distance_money' => '固定佣金起送金额未设置',
            'exceed_weight' => '固定佣金超出重量未设置',
            'exceed_weight_add_money' => '固定佣金超出重量加价金额未设置',
            'added_services_commission' => '跑腿费金额固定抽成参数有误(抽成比例0-100)',
            'special_services_commission' => '跑腿费金额固定抽成参数有误(抽成比例0-100)',
        ];

        if (validate($rule, $message)->check($params)) {
            $uid = $params['deliver_uid'];

            //先判断骑手是否存在
            $deliverUser = Db::name('deliver_user')->where('uid', $uid)->find();
            if (!$deliverUser) {
                throw new \Exception('骑手不存在');
            }

            $data = [
                'deliver_uid' => $params['deliver_uid'],
                'commission_type' => $params['commission_type'],
                'dynamics_commission' => $params['dynamics_commission'] ?? 0,
                //'dynamics_commission_ladder' => empty($params['dynamics_commission_ladder']) ? '[]' : json_encode($params['dynamics_commission_ladder'], JSON_UNESCAPED_UNICODE),

                //'fixed_commission_distance_ladder' => empty($params['fixed_commission_distance_ladder']) ? '[]' : json_encode($params['fixed_commission_distance_ladder'], JSON_UNESCAPED_UNICODE),
                //'fixed_commission_weight_ladder' => empty($params['fixed_commission_weight_ladder']) ? '[]' : json_encode($params['fixed_commission_weight_ladder'], JSON_UNESCAPED_UNICODE),

                'dynamics_commission_ladder' => $params['dynamics_commission_ladder'] ?? [],
                'fixed_commission_distance_ladder' => $params['fixed_commission_distance_ladder'] ?? [],
                'fixed_commission_weight_ladder' => $params['fixed_commission_weight_ladder'] ?? [],



                'within_distance' => $params['within_distance'] ?? 0,
                'within_distance_money' => $params['within_distance_money'] ?? 0,
                'exceed_weight' => $params['exceed_weight'] ?? 0,
                'exceed_weight_add_money' => $params['exceed_weight_add_money'] ?? 0,

                'added_services_commission' => $params['added_services_commission'] ?? 0,
                'special_services_commission' => $params['special_services_commission'] ?? 0,
            ];

            $old = $this->riderCommissionMod->where('deliver_uid', $uid)->find();
            if ($old) {
                return $this->riderCommissionMod->where('deliver_uid', $uid)->update($data);
            } else {
                return $this->riderCommissionMod->add($data);
            }
        }
        return false;
    }


    //获取骑手配送佣金设置
    public function getRiderCommissionSetting($uid)
    {
        return $this->riderCommissionMod->where('deliver_uid', $uid)->find();
    }
}
