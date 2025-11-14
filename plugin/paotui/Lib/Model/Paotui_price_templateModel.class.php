<?php

// 跑腿价格模板
class Paotui_price_templateModel extends Model
{
    /**
     * 计算跑腿费用
     *
     * @param int $business
     * @param float $distance
     * @param float $weight
     * @param int $specialDelivery 0普通订单 1专送订单
     * @author: zt
     * @date: 2025/07/15
     * 需同步修改app/paotui/model/service/PriceTemplateService.php calculateFee
     */
    public function calFee($business, $distance, $weight, $specialDelivery = 0, $merId = 0)
    {
        //*************匹配计费模板************* */
        //1、根据business找到对应的计费模板
        if ($business == 3) {
            //商家帮我送需要查询商家绑定的模板
            if (empty($merId)) {
                throw new Exception('跑腿计费模板获取失败');
            }
            $bindMerchant = D('Paotui_price_template_merchant')->where(['mer_id' => $merId])->find();
            $templateId = $specialDelivery == 0 ? $bindMerchant['template_id'] : $bindMerchant['special_delivery_template_id'];
            if (empty($templateId)) {
                throw new Exception('未配置跑腿计费模板！');
            }
        }

        $tempWhere = [
            'bind_business' => $business,
            'status' => 1,
            '_string' => 'delete_time IS null',
            'special_delivery' => $specialDelivery
        ];
        $templateId && $tempWhere['id'] = $templateId;
        $template = $this->where($tempWhere)->find();
        if (!$template) {
            throw new Exception('未配置跑腿计费模板！');
        }

        //2、计算里程费用
        $meter = $template['meter'];  //距离间隔
        $startMeter = $template['start_meter']; //起步距离
        $startPrice = $template['start_price']; //起步价格
        $exceedMilesRule = json_decode($template['exceed_miles_rule'], true); //超出起步距离的价格规则

        $distanceFee = $startPrice;  //默认里程费用为起步价
        $exceedFee = 0;
        //向上取
        if($distance % $meter){
			$distance = $distance - $distance % $meter + $meter;
		}
        if ($distance > $startMeter) {
            //大于起步距离
            $exceedDistance = $distance - $startMeter;
            foreach ($exceedMilesRule as $key => $item) {
                $exceedMeter = $item['meter'];
                $exceedMoney = $item['money'];
                $nextExceedMeter = $exceedMilesRule[$key + 1]['meter'] ?? PHP_INT_MAX;
                if ($exceedDistance >= $exceedMeter && $exceedDistance < $nextExceedMeter) {
                    //向上取整 exceedDistance除以meter
                    $exceedFee = round($exceedMoney * ceil($exceedDistance / $meter), 2);
                    break;
                }
            }
        }
        $distanceFee += $exceedFee;

        //3、计算重量费用
        $weightFee = 0;
        $exceedWeightFee = 0;
        $exceedWeight = 0;
        $exceedWeightRule = json_decode($template['exceed_weight_rule'], true);
        $addPrice = $template['weight_range_add_price'];

        foreach ($exceedWeightRule as $key => $item) {
            $minWeight = $item['min'];
            $maxWeight = $item['max'];
            $money = $item['money'];

            if ($weight >= $minWeight) {
                $weightFee = $money;
                $exceedWeight = $weight > $maxWeight ? $weight - $maxWeight : 0;
            }
        }
        $exceedWeightFee = $exceedWeight * $addPrice;

        //*************匹配增值费用************* */
        //1、根据business找到对应的增值模板、计算增值费用
        $nowTime = date('H:i:s');
        $where = [
            'bind_business' => $business,
            'status' => 1,
            '_string' => 'delete_time IS null',
            'begin_time' => ['lt', $nowTime],
            'end_time' => ['gt', $nowTime],
        ];
        $addedFeeList = M('Paotui_added_fee')->field('id,name,money')->where($where)->select();
        $addFeeName = '';
        $addFee = 0;
        if ($addedFeeList) {
            $addFee = array_sum(array_column($addedFeeList, 'money'));
            $addFeeName = implode('、', array_column($addedFeeList, 'name'));
        }

        return [
            'basic_distance_price' => getFormatNumber($startPrice),  //基础配送费
            'extra_distance_price' => $distanceFee > $startPrice ? round($distanceFee - $startPrice, 2) : 0,
            'basic_weight_price' => getFormatNumber($weightFee),  //基础费用
            'extra_weight_price' => getFormatNumber($exceedWeightFee), //额外重量加价费用
            'added_fee_list' => $addedFeeList,  //增值费用列表
            'added_fee_name' => $addFeeName,
            'added_fee' => getFormatNumber($addFee),
            'price_template_id' => $template['id']
        ];
    }
}
