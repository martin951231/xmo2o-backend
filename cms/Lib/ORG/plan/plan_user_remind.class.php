<?php

// 订阅每日定时提醒用户下单
class plan_user_remind extends plan_base
{
    public function runTask()
    {

		if (!empty(C('DISABLE_OLD_ALL_DB_PLAN')) && C('DISABLE_OLD_ALL_DB_PLAN') == true){
			return ;
		}

        $time = time();
        $w = date('w', $time);
        $subMod = M('User_subscribe');
        $where = [
            'u.openid' => ['NEQ', ''],
            'next_notice_time' => ['LT', $time],
            'sp.is_remind' => 1,
            '_string' => "FIND_IN_SET(" . $w . ",sp.remind_weekend)"
        ];
        $user_subscribe_list = $subMod->alias('us')
            ->join(C('DB_PREFIX') . 'user u ON u.uid=us.uid')
            ->join(C('DB_PREFIX') . 'merchant_store_shop sp ON sp.store_id=us.store_id')
            ->join(C('DB_PREFIX') . 'merchant_store s ON s.store_id=sp.store_id')
            ->field('us.id AS sub_id,u.uid,u.nickname,u.openid,sp.remind_weekend,sp.remind_time,s.store_id,s.name')
            ->where($where)
            ->limit(20)
            ->select();

        foreach ($user_subscribe_list as $v) {
            $this->keepThread();
            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            $href = C('config.site_url') . '/packapp/plat/pages/shop_new/shopDetail/shopDetail?store_id=' . $v['store_id'];

            $newInfo = [
                'tempKey' => '44716',
                'thing12' => date('H:i', $v['remind_time']) . '时间到，欢迎订餐！',
                'phrase2' => '订餐提醒',
                'time13' => date('Y-m-d H:i:s', $time),
                'thing11' => '店铺：' . $v['name'],
            ];

            $model->sendTempMsg('OPENTM405462911',
                array(
                    'href' => $href,
                    'wecha_id' => $v['openid'],
                    'first' => '您订阅的【' . $v['name'] . '】店铺，可以订餐了',
                    'keyword1' => '订餐提醒',
                    'keyword2' => '已发送',
                    'keyword3' => date('H时i分', $time),
                    'keyword4' => $v['nickname'],
                    'remark' => '请点击查看详细信息！',
                    'new_info' => $newInfo
                )
            );
            $nextNoticeTime = strtotime(date('Y-m-d', $time) . ' ' . date('H:i', $v['remind_time'])) + 86400;
            $subMod->where(['id' => $v['sub_id']])->setField('next_notice_time', $nextNoticeTime);
        }
        return true;
    }
}

?>