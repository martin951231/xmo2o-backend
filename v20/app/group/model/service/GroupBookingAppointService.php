<?php
/**
 * 场次预约service
 */

namespace app\group\model\service;

use app\group\model\db\GroupBookingAppointCombine;
use app\group\model\db\GroupBookingAppointRule;
use app\group\model\db\GroupBookingAppointRuleCombine;
use app\group\model\db\GroupBookingAppointRuleDetail;
use app\group\model\service\order\GroupBookingAppointOrderService;
use redis\Redis;
use think\Exception;

class GroupBookingAppointService
{
    /**
     * 获取场次
     * @param $groupId
     * @author: 张涛
     * @date: 2021/05/06
     */
    public function getRuleByGroupId($groupId, $hasPriceCalendar = true)
    {
        if (empty($groupId)) {
            throw new Exception(L_('团购ID参数有误'), 1001);
        }
        $rules = (new GroupBookingAppointRule())->withoutField('is_del')->where('group_id', $groupId)->where('is_del', 0)->select()->toArray();

        if ($hasPriceCalendar) {
            foreach ($rules as $k => $v) {
                $rules[$k]['price_calendar'] = $this->getPriceCalendarByRuleId($v['rule_id']);
            }
        }
        return $rules;
    }

    /**
     * 获取套餐
     * @param $groupId
     * @author: 张涛
     * @date: 2021/05/06
     */
    public function getCombineByGroupId($groupId)
    {
        if (empty($groupId)) {
            throw new Exception(L_('团购ID参数有误'), 1001);
        }
        $combine = (new GroupBookingAppointCombine())->withoutField('is_del')->where('group_id', $groupId)->where('is_del', 0)->select()->toArray();
        return $combine;
    }

    /**
     * 获取价格日历
     * @param $groupId
     * @author: 张涛
     * @date: 2021/05/06
     */
    public function getPriceCalendarByRuleId($ruleId, $day = null)
    {
        if (empty($ruleId)) {
            throw new Exception(L_('场次ID参数有误'), 1001);
        }
        $where = [['rule_id', '=', $ruleId]];
        $day && $where[] = ['day', '=', $day];
        return (new GroupBookingAppointRuleDetail())->where($where)->select()->toArray();
    }

    /**
     * 获取套餐场次组合
     * @param $groupId
     * @author: 张涛
     * @date: 2021/05/06
     */
    public function getRuleCombineByGroupId($groupId)
    {
        if (empty($groupId)) {
            throw new Exception(L_('团购ID参数有误'), 1001);
        }
        $combine = (new GroupBookingAppointRuleCombine())->withoutField('is_del')->where('group_id', $groupId)->where('is_del', 0)->select()->toArray();

        $combineMod = new GroupBookingAppointCombine();
        $ruleMod = new GroupBookingAppointRule();
        $combineNames = $combineMod->where('group_id',$groupId)->column('name','combine_id');
        $rules = $ruleMod->where('group_id',$groupId)->column('*','rule_id');

        foreach ($combine as &$c) {
            $c['combine_name'] = $combineNames[$c['combine_id']] ?? '';
            $c['rule_name'] = isset($rules[$c['rule_id']]) ? self::formateRuleHourRange($rules[$c['rule_id']]['start_time'], $rules[$c['rule_id']]['end_time'], $rules[$c['rule_id']]['use_hours']) : '';
        }
        return $combine;
    }

    /**
     * 根据场次查找套餐组合
     * @author: 张涛
     * @date: 2021/05/22
     */
    public function getRuleCombineByRuleId($ruleId)
    {
        if (empty($ruleId)) {
            throw new Exception(L_('参数有误'), 1001);
        }
        $combine = (new GroupBookingAppointRuleCombine())->withoutField('is_del')->where('rule_id', $ruleId)->where('is_del', 0)->select()->toArray();
        foreach ($combine as &$c) {
            $c['combine_name'] = '套餐名';
            $c['rule_name'] = '场次名';
        }
        return $combine;
    }

    /**
     * 获取场次
     * @param $ruleId
     * @author: 衡婷妹
     * @date: 2021/05/21
     */
    public function getRuleByRuleId($ruleId)
    {
        if (empty($ruleId)) {
            throw new Exception(L_('场次ID参数有误'), 1001);
        }
        $rule = (new GroupBookingAppointRule())->withoutField('is_del')->where('rule_id', $ruleId)->where('is_del', 0)->find()->toArray();
        $rule['price_calendar'] = $this->getPriceCalendarByRuleId($ruleId);
        
        return $rule;
    }  
    
    /**
    * 获取某个场次的某个套餐信息
    * @param $ruleId
    * @param $combineId
     * @author: 衡婷妹
     * @date: 2021/05/21
    */
   public function getRuleCombineDetail($ruleId, $combineId)
   {
        if (empty($ruleId) || empty($combineId)) {
            throw new Exception(L_('套餐ID参数有误'), 1001);
        }
        $where = [
            'combine_id' => $combineId,
            'rule_id' => $ruleId,
            'is_del' => 0,
        ];
       $combine = (new GroupBookingAppointRuleCombine())->withoutField('is_del')->where($where)->find()->toArray();

       $combineInfo = $this->getCombineByCombineId($combineId);
       $combine['name'] = $combineInfo['name'];
       $combine['intro'] = $combineInfo['intro'];
       $combine['price'] = $combineInfo['price'];

       return $combine;
   }

   /**
    * 根据套餐id获取套餐
    * @param $combineId
    * @author: 衡婷妹
    * @date: 2021/05/21
    */
   public function getCombineByCombineId($combineId)
   {
       if (empty($combineId)) {
           throw new Exception(L_('套餐ID参数有误'), 1001);
       }
       $combine = (new GroupBookingAppointCombine())->withoutField('is_del')->where('combine_id', $combineId)->where('is_del', 0)->find()->toArray();
       return $combine;
   }    
   
   /**
   * 根据场次id获取套餐
   * @param $ruleId
   * @author: 衡婷妹
   * @date: 2021/05/21
   */
    public function getCombineByRuleId($ruleId)
    {
        if (empty($ruleId)) {
            throw new Exception(L_('场次ID参数有误'), 1001);
        }
        $combineList = (new GroupBookingAppointRuleCombine())->withoutField('is_del')->where('rule_id', $ruleId)->where('is_del', 0)->select()->toArray();

        foreach($combineList as &$_combine){
            $combineInfo = $this->getCombineByCombineId($_combine['combine_id']);
            $_combine['name'] = $combineInfo['name'];
            $_combine['intro'] = $combineInfo['intro'];
            $_combine['price'] = get_format_number($combineInfo['price']);
        }

        return $combineList;
    }

    /**
     * 根据日期获取场次的售卖信息
     * @param $ruleId
     * @param $date 日期 eg:2021-05-21
     * @author: 衡婷妹
     * @date: 2021/05/21
     */
    public function getRulePrice($ruleId,$date)
    {
        if (empty($ruleId) || empty($date)) {
            throw new Exception(L_('参数有误'), 1001);
        }
        $where = [
            'rule_id' => $ruleId,
            'day' => $date,
        ];
        $rule = (new GroupBookingAppointRuleDetail())->where($where)->findOrEmpty()->toArray();
        
        return $rule;
    }


    /**
     * 根据场次ID获取场次详情
     * @author: 张涛
     * @date: 2021/05/22
     */
    public function getRuleInfo($groupId, $day)
    {
        $tm = time();
        //获取商品
        $group = (new GroupService())->getOne(['group_id'=>$groupId]);
        //获取场次
        $rule = $this->getRuleByGroupId($groupId, false);
        $return = [];
        $appointOrderService = new GroupBookingAppointOrderService();

        foreach ($rule as $key => $value) {
            $priceCalendar = $this->getPriceCalendarByRuleId($value['rule_id'], $day);
            //设置了价格日历，但是不售卖，则该场次不返回
            if ($priceCalendar && $priceCalendar[0]['is_sale'] != 1) {
                unset($rule[$key]);
            }
            $sellPrice = get_format_number(isset($priceCalendar[0]['price']) ? $priceCalendar[0]['price'] : $value['default_price']);
            $ruleName = self::formateRuleHourRange($value['start_time'], $value['end_time'], $value['use_hours']);

            //获取场次套餐
            $combine = $this->getRuleCombineByRuleId($value['rule_id']);

            //获取到达时间点
            $startUnix = strtotime($day) + $value['start_time'];
            $thisHalfUnix = ($tm % 1800 == 0) ? $tm : ($tm - ($tm % 1800) + 1800);
            $beginTime = max($startUnix, $thisHalfUnix);
            $endTime = strtotime($day) + $value['end_time'];
            $stepSecond = 1800;
            $timeLists = [];
            for ($beginTime; $beginTime < $endTime; $beginTime += $stepSecond) {
                $isEnoughHour =  ($endTime - $beginTime) >= $value['use_hours'] * 3600 ? true : false;

                $timeLists[] = [
                    'time' => date('H:i', $beginTime),
                    'datetime' => date('Y-m-d H:i:s', $beginTime),
                    'date' => date('Y-m-d', $beginTime),
                    'enough_hour_txt' => $isEnoughHour ? '' : L_('不足X1小时按照X2小时计费', ['X1' => $value['use_hours'], 'X2' => $value['use_hours']])
                ];
            }
            //取消文案
            $cancelTxt = '';
            if ($group['cancel_type'] == 0) {
                $cancelTxt = L_('不可取消');
            } else if ($group['cancel_type'] == 1) {
                //到期前取消
                $cancelTxt = L_('X1前可随时退款，逾期不可退', date('m-d H:i', $endTime-$group['cancel_hours']*3600));
            } else if ($group['cancel_type'] == 2) {
                $gv['cancel_txt'] = L_('随时可取消');
            } else if ($group['cancel_type'] == 3) {
                //开场前取消
                $cancelTxt = L_('X1前可随时退款，逾期不可退', date('m-d H:i', $startUnix-$group['cancel_hours']*3600));
            }

            $saleCount = $appointOrderService->getDaySaleCountByRuleId($value['rule_id'],$day);
            $return[] = [
                'rule_id' => $value['rule_id'],
                'rule_name' => $ruleName,
                'price' => $sellPrice,
                'has_combine' => $combine ? true : false,
                'sale_status' => $saleCount >= $value['count'] ? 0 : 1,
                'cancel_txt'=>$cancelTxt,
                'use_hours'=>$value['use_hours'],
                'time_select'=>$timeLists,
            ];
        }
        return $return;
    }

    /**
     * 库存处理
     * @param $combineId int 套餐id
     * @param $num int 修改数量
     * @param $type 操作类型 1-减少库存 2 增加库存
     * @return array
     */
    public function updateStock($ruleId,$combineId, $num, $type=1)
    {
        fdump([$ruleId,$combineId,$num,$type],'updateStock_rule',1);
        if (empty($ruleId) || empty($num)) {
            return false;
        }

        $where = [
            'rule_id' => $ruleId
        ];
        $rule = (new GroupBookingAppointRule())->where($where)->find();

        if($combineId){
            $where = [
                'combine_id' => $combineId,
                'rule_id' => $ruleId
            ];
            if($type == 1){
                $res = (new GroupBookingAppointRuleCombine())->where($where)->inc('sale_count', $num)->update();
            }else{
                $res = (new GroupBookingAppointRuleCombine())->where($where)->dec('sale_count', $num)->update();
            }
            fdump((new GroupBookingAppointRuleCombine())->getLastSql(),'updateStock_rule',1);
        }else{
            $where = [
                'rule_id' => $ruleId
            ];
            if($type == 1){
                $res = (new GroupBookingAppointRule())->where($where)->inc('sale_count', $num)->update();
            }else{
                $res = (new GroupBookingAppointRule())->where($where)->dec('sale_count', $num)->update();
            }
            fdump((new GroupBookingAppointRule())->getLastSql(),'updateStock_rule',1);
        }       

        if($res===false){
            return false;
        }

        // 更新销售总量
        (new GroupService)->updateStockTotal($rule['group_id'], $num, $type);
        return true;
    }

    /**
    * 增加redis
    *
    * @param int $groupId
    * @param array $specificationsInfo 规格信息
    * @return void
    * @author: 衡婷妹
    * @date: 2021/05/11
    */
   public function addRedisStock($groupId, $ruleId, $combineId, $num)
   { 
       if(empty($ruleId) && empty($combineId)){
            return false;
       }
       
       try {
        $redis = new Redis();
        } catch (\Exception $e) {
            return false;
        }

        $key = 'group_'.$groupId.'-r_'.$ruleId.'-c_'.$combineId;
        
        for($i=0; $i<$num; $i++){
            $redis->lpush($key,1);
        }

       return true;
   }

    public static function formateRuleHourRange($startTime, $endTime, $useHours)
    {
        $zeroUnixTime = strtotime(date('Y-m-d'));
        $startHour = date('H:i', $zeroUnixTime + $startTime);
        $endHour = date('H:i', $zeroUnixTime + $endTime);
        $extraDay = floor($endTime / 86400);
        $extraDayPrefix = $extraDay == 0 ? '' : L_('次日');
        $ruleHour = L_('X1-X2内', ['X1' => $startHour, 'X2' => $extraDayPrefix . $endHour]);
        if($useHours > 0){
            $ruleHour .= ',' . L_('任选X1小时', ['X1' => $useHours]);
        }else{
            $ruleHour .= ',' . L_('全部X1小时', ['X1' => intval(($endTime-$startTime)/3600)]);
        }
        return $ruleHour;
    }
}