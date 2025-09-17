<?php
/**
 * 微信分账执行
 * author：hengtingmei
 * date：2021-09-24
 */
class plan_weixin_profit_sharing extends plan_base{
	public function runTask(){
		if(!C('config.open_weixin_profit_sharing')){
			return true;
		}

		// 查询待分账列表
        $where = [
            'is_withdraw' =>  1,
            'count' => ['lt', 50]
        ];
        $list = M('Weixin_profit_sharing_record')->where($where)->limit(10)->select();

        // 没有待分账信息
		if(empty($list)){			
	        $where = [
	            'is_withdraw' =>  1,
	        ];
	        $list = M('Weixin_profit_sharing_record')->where($where)->limit(10)->select();
	        // 没有待分账信息
			if(empty($list)){
				return true;
			}
		}

		foreach ($list as $info) {
			//防止计划任务并发分账同一个订单
			$key = 'weixin_fenzhang_record_'.$info['id'];
			if(S($key)){
				continue;
			}
			S($key,1,15);

			//判断当前商家的账期是否到了
			if($info['is_v20']){
				$result = invoke_v20_service('\app\merchant\model\service\MerchantMoneyListService/weixinProfitSharing',['info' => $info]);
				$res = $result['data']['retval'] ?? ['error' => 1];
			}else{
				$res = D('Merchant_money_list')->weixin_profit_sharing($info);
			}
			if($res['error'] == 0){// 分账成功
				// 更新
				$data = [
					'last_time' => time(),
					'is_withdraw' => 2
				];
        	 	M('Weixin_profit_sharing_record')->where(['id' => $info['id']])->data($data)->save();
				S($key,null);
			}
		}
		return true;
	}

}