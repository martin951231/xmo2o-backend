<?php


namespace app\common\model\service;


class GoodsScoreService
{
    /**
     * @param $goods_price //商品价格
     * @param $business_type //业务类型
     * @param false $has_spec //true-多规格
     * @return array
     */
    public function getGoodsScore($goods_price, $business_type, $has_spec = false, $goods_score = 0)
    {
        $score = 0;
        $score_txt = '';
        if (cfg('product_score_display') != 1) {
            $goods_price = 0;
        }
        if ($goods_price > 0) {
            //平台积分设置
            $score_type = cfg('open_score_get_percent') ?: 0; //开启积分获取百分比，1-按百分比计算
            $score_get_percent = cfg('score_get_percent') ?: 0; //积分百分比单位%
            $user_score_get = cfg('user_score_get') ?: 0; //消费一元获得积分数
            switch ($business_type) {
                case 'appoint':
                    if ($goods_score > 0) {
                        $score_type = 0;
                        $user_score_get = $goods_score;
                    }
                    break;
                case 'mall':
                    if ($goods_score) {
                        if (strpos($goods_score, '%')) {
                            $score_get_percent = str_replace('%', '', $goods_score);
                            $score_type = 1;
                        } else {
                            $user_score_get = $goods_score;
                        }
                    }
                    break;
                case 'group':
                    $group_score = cfg('group_score') ?: 0;
                    if ($goods_score > 0) {
                        $score_type = 0;
                        $user_score_get = $goods_score;
                    } elseif ($group_score > 0) {
                        $score_type = 0;
                        $user_score_get = $group_score;
                    }
                    break;
                case 'dining':
                    $meal_score = cfg('meal_score') ?: 0;
                    if ($meal_score > 0) {
                        $score_type = 0;
                        $user_score_get = $meal_score;
                    }
                    break;
                default:
                    break;
            }
            if ($score_type) { //按百分比计算
                $score = getFormatNumber($goods_price * $score_get_percent / 100);
            } else {
                $score = getFormatNumber($goods_price * $user_score_get);
            }
            if (cfg('score_round_two') != 1) { //没有开启积分保留两位小数的只保留整数
                $score = round($score);
            }
        }
        $score = (float)$score;
        if ($score > 0) {
            $score_txt = ($has_spec ? '最少' : '') . '可得' . cfg('score_name');
        }
        return ['score' => $score, 'score_txt' => $score_txt];
    }
}