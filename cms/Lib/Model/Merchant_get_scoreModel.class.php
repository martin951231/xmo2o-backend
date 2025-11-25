<?php
/*
  * 客户定制商家获得积分记录表
  * 每日释放商家积分
*/
class Merchant_get_scoreModel extends Model
{
	/*
	  * 用户消费后添加积分
	*/
	public function addScoreLog($mer_id, $score_count, $order_info)
	{
		fdump($score_count,'addScoreLog');
		$date = date('Ymd');
		$where['mer_id'] = $mer_id;
		$where['get_date'] = $date;
		$detail = $this->where($where)->find();
		if($detail){
			$res = $this->where($where)->setInc('score_count', $score_count);
		}else{
			$data = [
				'mer_id' => $mer_id,
				'score_count' => $score_count,
				'get_date' => $date,
			];
			$res = $this->add($data);
		}

		$data = [
			'mer_id' => $mer_id,
			'real_orderid' => $order_info['real_orderid'] ?:$order_info['orderid'],
			'order_id' => $order_info['order_id'],
			'order_type' => $order_info['order_type'],
			'score' => $score_count,
			'add_time' => time(),
		];
		fdump($data,'Merchant_get_score_order_log',1);
		M('Merchant_get_score_order_log')->add($data);
		return $res;
	}

	/*
	  * 给商家释放积分
	*/
	public function addMerchantScore($mer_id)
	{
		// 获得180天内的积分记录
		$startTime = date('Ymd',strtotime("-181 day"));
		$endTime = date('Ymd',strtotime("-1 day"));
		$where['mer_id'] = $mer_id;
		$where['get_date'] = ['between',[$startTime, $endTime]];
		$where['score_count'] = ['gt',0];
		$where['_string'] = 'score_get <= score_count';
		$list = $this->where($where)->select();

		$totalScore = 0;// 今日获得积分总数

		$addData = [];// 获得日志数组
		foreach ($list as $key => $value) {
			$s_time = strtotime($value['get_date']);
			$e_time = strtotime($value['get_date'])+86400;
			if(M('Merchant_get_score_log')->where(['get_id' => $value['id'],'add_time'=>['between',[$s_time, $e_time]]])->find()){
					continue;
			}

			// 每天释放积分数
			$score = round($value['score_count']/180, 4);

			// 释放积分总数
			$score_count = 0;

			// 获得已释放的记录
			$sendLogTime = array_column(M('Merchant_get_score_log')->where(['get_id' => $value['id']])->select(), 'add_time');
			foreach($sendLogTime as $k => $v){
				$sendLogTime[$k] =  date('Ymd', $v);
			}

			$time = $startTime;
			while($time <= $endTime){// 查看之前是否有漏掉的

				if(in_array($time, $sendLogTime) || $value['get_date'] >= $time){// 某天已释放过积分或者获得日期大于等于某天的不需要释放积分
					$time = date('Ymd', strtotime($time)+86400);
					continue;
				}
				// 插入释放记录
				$addData[] = [
					'mer_id' => $mer_id,
					'get_id' => $value['id'],
					'score' => $score,
					'get_date' => $value['get_date'],
					'add_time' => strtotime($time),
				];

				// 更新释放金额
				$updateData[] = [
					'id' => $value['id'],
					'score' => $score,
				];

				// 商家获得总积分数
				$totalScore += $score;

				$time = date('Ymd', strtotime($time)+86400);
			}

		}

		// fdump(['list'=>$list,'totalScore'=>$totalScore,'addData'=>$addData,'updateData'=>$updateData], 'addMerchantScore', 1);

		if(M('Merchant')->where(['mer_id'=>$mer_id])->setInc('merchant_score', $totalScore) !== false){
			$res = M('Merchant_get_score_log')->addAll($addData);
			foreach ($updateData as $key => $value) {
				$this->where(['id' => $value['id']])->setInc('score_get', $value['score']);
			}
		}else{
			return false;
		}
		
		return true;
	}
	
}
?>