<?php
/**
 * 商家抽成和分佣比率和积分设置
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/5/29 11:27
 */

namespace app\common\model\service;

use app\common\model\db\MerchantPercentRate;

class MerchantPercentRateService
{
    public $merchantPercentRateObj = null;
    public function __construct()
    {
        $this->merchantPercentRateObj = new MerchantPercentRate();
    }

    /**
     * 积分最大使用量
     * User: chenxiang
     * Date: 2020/6/9 14:46
     * @param $mer_id
     * @param $type
     * @param int $money
     * @param array $order_info
     * @return float|int|mixed|string
     */
    public function getMaxScoreUse($mer_id, $type, $money = 0, $order_info = [])
    {
        $user_score_use_percent = cfg('user_score_use_percent');// 抵扣1元所需积分数量
        $where['mer_id'] = $mer_id;
        $now_mer_pr = $this->merchantPercentRateObj->getRateData($where);
        if($now_mer_pr){
            $now_mer_pr = $now_mer_pr->toArray();
        }
        else{
            $now_mer_pr = [];
        }
        //商城的去除配置的限制
        if (((cfg('shop_goods_score_edit') == 1 && $type == 'shop') || $type == 'mall') && $order_info) {
            $use_condition = cfg('user_score_use_condition');

            $user_score_use_percent = cfg('user_score_use_percent');

            if ($order_info['total_money'] < $use_condition) {
                return 0;
            }
            $goods_list = $order_info['order_content'];

            $all_score_max = 0;
            foreach ($goods_list as $v) {
                if ($v['score_max'] > 0) {
                    $score_max_deducte = bcdiv($v['score_max'], $user_score_use_percent, 2);
                    if ($score_max_deducte > $v['discount_price']) {
                        $score_max_deducte = $v['discount_price'];
                        $v['score_max'] = intval(round($score_max_deducte * $user_score_use_percent));
                    }
                    $order_info['total_money'] -= $score_max_deducte;
                    if ($order_info['total_money'] < $use_condition) {
                        $tmp_score_deducte = $use_condition - $order_info['total_money'];
                        $tmp_score_max = intval(round($tmp_score_deducte * $user_score_use_percent));
                        return $all_score_max + $tmp_score_max;
                    }
                    $all_score_max += $v['score_max'] * $v['num'];
                }
            }
            return $all_score_max;
        } else if ($now_mer_pr) {
            $type = $type == 'dining' ? 'meal' : $type;
            if (strpos($now_mer_pr['merchant_' . $type . '_score_max'], '%')) {
                $now_mer_pr['merchant_' . $type . '_score_max'] = $user_score_use_percent * $money * floatval(str_replace('%', '', $now_mer_pr['merchant_' . $type . '_score_max'])) / 100;
            }

            if (strpos($now_mer_pr['merchant_score_max'], '%')) {
                $now_mer_pr['merchant_score_max'] = $user_score_use_percent * $money * floatval(str_replace('%', '', $now_mer_pr['merchant_score_max'])) / 100;
            }

            if (strpos(cfg( $type . '_score_max'), '%')) {
                cfg( $type . '_score_max', $user_score_use_percent * $money * floatval(str_replace('%', '', cfg($type . '_score_max'))) / 100);
            }

            if (strpos(cfg('user_score_max_use'), '%')) {
                cfg('user_score_max_use', $user_score_use_percent * $money * floatval(str_replace('%', '', cfg('user_score_max_use'))) / 100);
            }

            if ($now_mer_pr['merchant_' . $type . '_score_max'] >= 0 && $now_mer_pr['merchant_' . $type . '_score_max'] != '') {
                return $now_mer_pr['merchant_' . $type . '_score_max'];
            } elseif ($now_mer_pr['merchant_score_max'] >= 0 && $now_mer_pr['merchant_score_max'] != '') {
                return $now_mer_pr['merchant_score_max'];
            } elseif (cfg($type . '_score_max') >= 0) {
                return cfg($type . '_score_max');
            } elseif (cfg('user_score_max_use') >= 0) {
                return cfg('user_score_max_use');
            } else {
                return 0;
            }
        } else {
            if (strpos(cfg($type . '_score_max'), '%')) {
                cfg($type . '_score_max', $user_score_use_percent * $money * floatval(str_replace('%', '', cfg($type . '_score_max'))) / 100);
            }

            if (strpos(cfg('user_score_max_use'), '%')) {
                cfg('user_score_max_use', $user_score_use_percent * $money * floatval(str_replace('%', '', cfg('user_score_max_use'))) / 100);
            }

            if (cfg($type . '_score_max') != '' && cfg($type . '_score_max') >= 0) {
                return cfg($type . '_score_max');
            } elseif (cfg('user_score_max_use') >= 0) {
                return cfg('user_score_max_use');
            } else {
                return 0;
            }
        }
    }

    /**
     * 获取用户增加积分的次数
     * User: chenxiang
     * Date: 2020/6/1 18:38
     * @param $score
     * @param $uid
     * @return float|int
     */
    public function getUserAddScoreTimes($score, $uid)
    {
        $now_user = D('User')->get_user($uid);
        $today_md = date('m-d');
        $now_year = date(Y) . '-';

        $today_sec = strtotime($now_year . $today_md);
        if (C('config.vip_day') >= 0 && C('config.vipday_score_times') > 0) {
            if (C('config.vip_day_type') == 1) { // 每周几积分倍数获取形式
                $today_week = date('w');
                if ($today_week == C('config.vip_day')) {
                    $score = $score * C('config.vipday_score_times');
                }
            } elseif (C('config.vip_day_type') == 2) { // 每月几号积分倍数获取形式
                $today_day = date('d');
                if ($today_day > 9) {
                    $today_day = substr($today_day, -1);
                }
                if ($today_day == C('config.vip_day')) {
                    $score = $score * C('config.vipday_score_times');
                }
            }
        }

        if (date('m-d', strtotime($now_user['birthday'])) == $today_md && C('config.birthday_score_times') > 0) {
            $score = $score * C('config.birthday_score_times');
        }

        $spec = M('Special_day_score_times')->order('value DESC')->select();
        foreach ($spec as $v) {
            if ($today_sec >= strtotime($now_year . $v['start']) && $today_sec <= strtotime($now_year . $v['end'])) {
                $score = $score * $v['value'];
                break;
            }
        }
        return $score;
    }
}