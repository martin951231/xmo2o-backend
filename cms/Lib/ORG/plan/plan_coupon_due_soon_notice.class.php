<?php
/**
 * 优惠券即将过期提醒
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/11/28 16:40
 */

class plan_coupon_due_soon_notice extends plan_base
{
    public function runTask() {

        //优惠券在领取后有效期大于48h的  在优惠券过期前一天 上午10点提醒

        $where = [];
        $where['is_use'] = 0; // 0正常

        //平台优惠券
        $system_coupon_list = M('System_coupon_hadpull')->alias('s')
            ->join(C('DB_PREFIX') . 'system_coupon AS sc ON s.coupon_id = sc.coupon_id', 'LEFT')
            ->join(C('DB_PREFIX') . 'user AS u ON s.uid = u.uid', 'LEFT')
            ->field('u.openid,s.receive_time,sc.valid_time,sc.start_time,sc.end_time')
            ->where($where)
            ->select();

        foreach($system_coupon_list as $s_k => &$s_v) {
            if ($s_v['valid_time']) {
                if ($s_v['receive_time'] < $s_v['start_time']) {
                    $end_time = $s_v['start_time'] + $s_v['valid_time'] * 86400;
                } else {
                    $s_v['start_time'] = $s_v['receive_time'];
                    $end_time = $s_v['receive_time'] + $s_v['valid_time'] * 86400;
                }
                $s_v['end_time'] = $end_time > $s_v['end_time'] ? $s_v['end_time'] : $end_time;
            }

            //优惠券在领取后有效期大于48h的  在优惠券过期前一天 上午10点提醒
            if(
				$s_v['end_time'] < $_SERVER['REQUEST_TIME'] || 
				$s_v['end_time'] > $s_v['receive_time'] + 86400*2  ||  
				date('Ymd', $s_v['end_time'] - 86400) != date('Ymd', $_SERVER['REQUEST_TIME'])
			) {
                unset($system_coupon_list[$s_k]);
            }
			
			$this->keepThread();
        }


        //商家优惠券
        $merchant_coupon_list = M('Card_new_coupon_hadpull')->alias('h')
            ->join(C('DB_PREFIX') . 'card_new_coupon AS c ON h.coupon_id = c.coupon_id')
            ->join(C('DB_PREFIX') . 'user AS u ON h.uid = u.uid')
            ->field('u.openid,h.receive_time,c.end_time')
            ->where($where)
            ->select();

        foreach($merchant_coupon_list as $k => &$v) {
            //优惠券在领取后有效期大于48h的  在优惠券过期前一天 上午10点提醒
            if(
				$v['end_time'] < $_SERVER['REQUEST_TIME'] || 
				$v['end_time'] > $v['receive_time'] + 86400*2 ||
				date('Ymd', $s_v['end_time'] - 86400) != date('Ymd', $_SERVER['REQUEST_TIME'])
			) {
                unset($merchant_coupon_list[$k]);
            }
			
			$this->keepThread();
        }

        if(!empty($system_coupon_list)) { //平台优惠券

            foreach ($system_coupon_list as $value) {
                $this->keepThread();

                $href = C('config.site_url') . D('House_village')->base_url . 'pages/coupon/myCoupon';

                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));

                $model->sendTempMsg('OPENTM400166399',
                    array(
                        'href' => $href,
                        'wecha_id' => $value['openid'],
                        'first' => '你的优惠券即将过期，请及时使用',
                        'keyword1' => '优惠券即将过期提醒',
                        'keyword2' =>'即将过期',
                        'keyword3' => date('Y.m.d H:i',$_SERVER['REQUEST_TIME']),
                        'remark' => '点击查看优惠券信息！'
                    )
                );
            }

        }

        if(!empty($merchant_coupon_list)) { //商家优惠券
            foreach ($merchant_coupon_list as $value) {
                $this->keepThread();

                $href = C('config.site_url') . D('House_village')->base_url . 'pages/coupon/myCoupon?mer_id=' . $value['mer_id'];

                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));

                $model->sendTempMsg('OPENTM400166399',
                    array(
                        'href' => $href,
                        'wecha_id' => $value['openid'],
                        'first' => '你的优惠券即将过期，请及时使用',
                        'keyword1' => '优惠券即将过期提醒',
                        'keyword2' =>'即将过期',
                        'keyword3' => date('Y.m.d H:i',$_SERVER['REQUEST_TIME']),
                        'remark' => '点击查看优惠券信息！'
                    )
                );
            }
        }

        return true;

    }
}
?>